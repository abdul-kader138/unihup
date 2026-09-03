<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppClient;
use Carbon\Carbon;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

/**
 * Digests one webhook payload from Meta. A `changes[].value` entry can carry
 *  - `messages`  : new inbound messages from a customer
 *  - `statuses`  : delivery / read / failed receipts for our outbound messages
 *  - `contacts`  : the sender's WhatsApp profile name
 * all keyed by the customer's phone number (`wa_id`). Everything is matched
 * on Meta's message id so a retried webhook is a no-op.
 */
class ProcessInboundWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** Media message types we know how to download. */
    private const MEDIA_TYPES = ['image', 'document', 'audio', 'video', 'sticker'];

    public function __construct(public readonly array $payload) {}

    public function handle(WhatsAppClient $client): void
    {
        foreach (Arr::get($this->payload, 'entry', []) as $entry) {
            foreach (Arr::get($entry, 'changes', []) as $change) {
                $value = $change['value'] ?? [];

                $contactName = Arr::get($value, 'contacts.0.profile.name');

                foreach ($value['messages'] ?? [] as $message) {
                    $this->handleInboundMessage($client, $message, $contactName);
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    $this->handleStatus($status);
                }
            }
        }
    }

    private function handleInboundMessage(WhatsAppClient $client, array $message, ?string $contactName): void
    {
        $waMessageId = $message['id'] ?? null;
        $from = $message['from'] ?? null;

        if (! $waMessageId || ! $from) {
            return;
        }

        if (WhatsAppMessage::where('wa_message_id', $waMessageId)->exists()) {
            return; // retried webhook
        }

        $conversation = $this->conversationFor($from, $contactName);

        $type = $message['type'] ?? 'text';
        $body = null;
        $mediaPath = null;
        $mediaMime = null;

        if ($type === 'text') {
            $body = Arr::get($message, 'text.body');
        } elseif (in_array($type, self::MEDIA_TYPES, true)) {
            $body = Arr::get($message, "{$type}.caption");
            $mediaId = Arr::get($message, "{$type}.id");

            if ($mediaId && $client->configured()) {
                try {
                    $stored = $client->storeMedia($mediaId);
                    $mediaPath = $stored['path'];
                    $mediaMime = $stored['mime'];
                } catch (Throwable $e) {
                    $body = trim(($body ? $body."\n" : '')."[media {$mediaId} could not be downloaded]");
                }
            }
        } else {
            // location, contacts, reaction, system, unsupported…
            $body = '['.$type.' message]';
        }

        $conversation->messages()->create([
            'direction' => WhatsAppMessage::DIRECTION_IN,
            'wa_message_id' => $waMessageId,
            'type' => $type,
            'body' => $body,
            'media_path' => $mediaPath,
            'media_mime' => $mediaMime,
            'status' => WhatsAppMessage::STATUS_DELIVERED,
        ]);

        $sentAt = isset($message['timestamp'])
            ? Carbon::createFromTimestamp((int) $message['timestamp'])
            : now();

        $conversation->forceFill([
            'last_inbound_at' => $sentAt,
            'wa_contact_name' => $conversation->wa_contact_name ?: $contactName,
            // A customer reply re-opens a closed thread.
            'status' => $conversation->status === WhatsAppConversation::STATUS_CLOSED
                ? WhatsAppConversation::STATUS_OPEN
                : $conversation->status,
        ])->save();

        if ($client->configured()) {
            try {
                $client->markRead($waMessageId);
            } catch (Throwable) {
                // best effort
            }
        }

        $this->notifyStaff($conversation, $body ?: Str::headline($type));
    }

    private function handleStatus(array $status): void
    {
        $waMessageId = $status['id'] ?? null;
        $state = $status['status'] ?? null;

        if (! $waMessageId || ! $state) {
            return;
        }

        $message = WhatsAppMessage::where('wa_message_id', $waMessageId)->first();
        if ($message === null) {
            return;
        }

        $attributes = ['status' => $state];

        if ($state === WhatsAppMessage::STATUS_DELIVERED && $message->delivered_at === null) {
            $attributes['delivered_at'] = now();
        }
        if ($state === WhatsAppMessage::STATUS_READ && $message->read_at === null) {
            $attributes['read_at'] = now();
            $attributes['delivered_at'] = $message->delivered_at ?? now();
        }
        if ($state === WhatsAppMessage::STATUS_FAILED) {
            $attributes['error'] = Arr::get($status, 'errors.0.title', 'Delivery failed');
        }

        $message->update($attributes);
    }

    private function conversationFor(string $waId, ?string $contactName): WhatsAppConversation
    {
        $normalized = WhatsAppClient::normalizeNumber($waId);

        $conversation = WhatsAppConversation::firstOrNew(['wa_contact_id' => $normalized]);

        if (! $conversation->exists) {
            $conversation->status = WhatsAppConversation::STATUS_OPEN;
            $conversation->wa_contact_name = $contactName;
        }

        if ($conversation->user_id === null) {
            $conversation->user_id = User::query()
                ->whereNotNull('whatsapp_number')
                ->get(['id', 'whatsapp_number'])
                ->first(fn (User $u) => WhatsAppClient::normalizeNumber((string) $u->whatsapp_number) === $normalized)
                ?->id;
        }

        $conversation->save();

        return $conversation;
    }

    private function notifyStaff(WhatsAppConversation $conversation, string $preview): void
    {
        $recipients = $conversation->assignee
            ? collect([$conversation->assignee])
            : $this->inboxStaff();

        if ($recipients->isEmpty()) {
            return;
        }

        $name = $conversation->wa_contact_name ?: $conversation->wa_contact_id;

        FilamentNotification::make()
            ->title("WhatsApp message from {$name}")
            ->icon('heroicon-o-chat-bubble-left-right')
            ->body(Str::limit($preview, 120))
            ->sendToDatabase($recipients);
    }

    /** Everyone allowed into the inbox: the page permission holders + super admins. */
    private function inboxStaff()
    {
        $superAdmin = (string) config('filament-shield.super_admin.name', 'super_admin');
        $staff = collect();

        foreach ([fn () => User::role($superAdmin)->get(), fn () => User::permission('page_WhatsAppInbox')->get()] as $resolve) {
            try {
                $staff = $staff->merge($resolve());
            } catch (Throwable) {
                // role / permission not created yet (php artisan shield:generate)
            }
        }

        return $staff->unique('id')->values();
    }
}
