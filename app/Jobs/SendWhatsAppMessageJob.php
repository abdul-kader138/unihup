<?php

namespace App\Jobs;

use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Delivers one outbound WhatsApp message row to Meta. The row is created
 * first (status = queued) by whatever triggered the send — the staff reply
 * box, or a system template — so the inbox thread shows it immediately and
 * this job just fills in the Meta message id / status afterwards.
 *
 * For a template send, pass the approved template name + language; `body` on
 * the row is then only the human-readable preview shown in the thread.
 */
class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly WhatsAppMessage $message,
        public readonly ?string $templateName = null,
        public readonly string $templateLanguage = 'en',
    ) {}

    public function handle(WhatsAppClient $client): void
    {
        $message = $this->message->fresh();

        if ($message === null || $message->wa_message_id !== null) {
            return; // already sent, or the row was deleted
        }

        if (! $client->configured()) {
            $message->update(['status' => WhatsAppMessage::STATUS_FAILED, 'error' => 'WhatsApp is not configured']);

            return;
        }

        $to = $message->conversation->wa_contact_id;

        // A portal-only conversation (a Support Chat user with no WhatsApp
        // number on file) has no address to send to. WhatsAppInbox::sendReply()
        // already skips dispatching this job in that case — this is a
        // defense-in-depth guard so a null recipient never reaches
        // WhatsAppClient::normalizeNumber(), which requires a string.
        if ($to === null) {
            $message->update(['status' => WhatsAppMessage::STATUS_FAILED, 'error' => 'Conversation has no WhatsApp number']);

            return;
        }

        try {
            $waId = $this->templateName !== null
                ? $client->sendTemplate($to, $this->templateName, $this->templateLanguage)
                : $client->sendText($to, (string) $message->body);
        } catch (WhatsAppException $e) {
            $message->update([
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);

            activity('whatsapp')
                ->withProperties(['conversation_id' => $message->conversation_id, 'error' => $e->context])
                ->log("WhatsApp send failed: {$e->getMessage()}");

            return;
        }

        $message->update([
            'wa_message_id' => $waId,
            'status' => WhatsAppMessage::STATUS_SENT,
            'error' => null,
        ]);

        $message->conversation->update(['last_outbound_at' => now()]);
    }
}
