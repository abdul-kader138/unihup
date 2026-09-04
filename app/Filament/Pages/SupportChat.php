<?php

namespace App\Filament\Pages;

use App\Events\WhatsAppMessageCreated;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppClient;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;

/**
 * The in-panel chat every signed-in user gets, for talking to the site
 * owner. Open to anyone who can reach the panel — no HasPageShield /
 * canAccess override, same as FindUniversities.
 *
 * If the user has opted into WhatsApp (App\Filament\Auth\EditProfile's
 * "WhatsApp Support" section), staff replies also go out over the Cloud API
 * to their phone (App\Jobs\SendWhatsAppMessageJob, dispatched from
 * WhatsAppInbox), and a message they send from their own WhatsApp app lands
 * in this same conversation (App\Jobs\ProcessInboundWhatsAppJob). A line
 * typed here, though, is portal-only — WhatsApp has no API for the customer
 * side — so it only ever reaches staff, in the WhatsApp Inbox.
 */
class SupportChat extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Support Chat';

    protected static ?string $title = 'Support Chat';

    protected static ?string $slug = 'support-chat';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.support-chat';

    public string $draft = '';

    public int $conversationId = 0;

    // How many of the most recent messages are shown. Kept small and grown
    // on demand (loadEarlierMessages()) rather than loading a whole
    // conversation's history on every poll — some threads run for months.
    public int $messagesToShow = 30;

    public function mount(): void
    {
        $this->conversationId = $this->resolveConversation()->id;
    }

    /** @return array<string, string> */
    public function getListeners(): array
    {
        return [
            "echo-private:whatsapp.conversation.{$this->conversationId},.message.created" => '$refresh',
        ];
    }

    /** Slow poll when Reverb is live (a backstop), brisk poll when it's the only channel. */
    #[Computed]
    public function pollInterval(): string
    {
        return config('broadcasting.default') === 'null' ? '4s' : '30s';
    }

    #[Computed]
    public function conversation(): WhatsAppConversation
    {
        return WhatsAppConversation::findOrFail($this->conversationId);
    }

    /** The most recent $messagesToShow messages, oldest first. */
    #[Computed]
    public function messages(): Collection
    {
        return WhatsAppMessage::where('conversation_id', $this->conversationId)
            ->latest('created_at')
            ->limit($this->messagesToShow)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Cheap proxy instead of a separate COUNT query: if the last fetch came
     * back full, there's *probably* more before it. Worst case a "Load
     * earlier" click that finds nothing new — far cheaper than counting the
     * whole thread on every poll.
     */
    #[Computed]
    public function hasMoreMessages(): bool
    {
        return $this->messages->count() >= $this->messagesToShow;
    }

    public function loadEarlierMessages(): void
    {
        $this->messagesToShow += 30;
        unset($this->messages, $this->hasMoreMessages);
    }

    public function send(): void
    {
        $body = trim($this->draft);

        if ($body === '') {
            return;
        }

        // 20 messages/minute per user — generous for a real conversation,
        // enough to stop a runaway client-side loop or a scripted flood from
        // hammering the DB and the staff notification fan-out.
        $key = 'support-chat-send:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            Notification::make()
                ->warning()
                ->title('Sending too fast')
                ->body('Please wait a moment before sending more messages.')
                ->send();

            return;
        }

        RateLimiter::hit($key, 60);

        $conversation = $this->conversation;

        $message = $conversation->messages()->create([
            'direction' => WhatsAppMessage::DIRECTION_IN,
            'type' => 'text',
            'body' => $body,
            'status' => WhatsAppMessage::STATUS_DELIVERED,
        ]);

        $conversation->forceFill(['last_inbound_at' => now()])->save();

        WhatsAppMessageCreated::dispatch($message);
        $conversation->notifyStaff($body);
        WhatsAppInbox::forgetBadgeCache();

        $this->draft = '';
        unset($this->conversation, $this->messages, $this->hasMoreMessages);
    }

    /**
     * Find (or create) the single conversation for this user, matching on
     * user_id first and WhatsApp number second — mirrors
     * ProcessInboundWhatsAppJob::conversationFor() so a portal-only thread
     * and one created from a real inbound message never end up split.
     */
    protected function resolveConversation(): WhatsAppConversation
    {
        /** @var User $user */
        $user = auth()->user();

        // Queried directly rather than via $user->whatsappConversation: the
        // auth guard can hand back the same cached User instance across
        // multiple requests/components in one process, and a hasOne relation
        // accessed before any conversation existed caches that "null" result
        // on the instance forever — which would create a fresh conversation
        // every time instead of reusing the first one.
        if ($existing = WhatsAppConversation::where('user_id', $user->id)->first()) {
            return $existing;
        }

        $normalized = $user->whatsapp_number ? WhatsAppClient::normalizeNumber($user->whatsapp_number) : null;

        if ($normalized) {
            $byNumber = WhatsAppConversation::where('wa_contact_id', $normalized)->first();

            if ($byNumber) {
                if ($byNumber->user_id === null) {
                    $byNumber->update(['user_id' => $user->id]);
                }

                return $byNumber;
            }
        }

        return WhatsAppConversation::create([
            'wa_contact_id' => $normalized,
            'wa_contact_name' => $user->name,
            'user_id' => $user->id,
            'status' => WhatsAppConversation::STATUS_OPEN,
        ]);
    }
}
