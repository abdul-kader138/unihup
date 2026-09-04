<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessageJob;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendWhatsAppMessageJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression test: a conversation with no wa_contact_id (a Support Chat
     * user with no WhatsApp number) used to reach
     * WhatsAppClient::normalizeNumber(null), which is a TypeError, not a
     * catchable WhatsAppException — the job would fail 3 times and the
     * message would stay stuck at "queued" forever. It must fail cleanly
     * instead.
     */
    public function test_a_message_with_no_whatsapp_number_fails_cleanly_instead_of_throwing(): void
    {
        config([
            'services.whatsapp.phone_number_id' => 'test-id',
            'services.whatsapp.access_token' => 'test-token',
        ]);

        $conversation = WhatsAppConversation::create(['status' => WhatsAppConversation::STATUS_OPEN]);
        $message = $conversation->messages()->create([
            'direction' => WhatsAppMessage::DIRECTION_OUT,
            'type' => 'text',
            'body' => 'hello',
            'status' => WhatsAppMessage::STATUS_QUEUED,
        ]);

        (new SendWhatsAppMessageJob($message))->handle(WhatsAppClient::fromConfig());

        $message->refresh();
        $this->assertSame(WhatsAppMessage::STATUS_FAILED, $message->status);
        $this->assertSame('Conversation has no WhatsApp number', $message->error);
    }
}
