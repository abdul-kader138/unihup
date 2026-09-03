<?php

namespace Tests\Feature;

use App\Jobs\ProcessInboundWhatsAppJob;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.whatsapp.verify_token' => 'verify-me',
            'services.whatsapp.app_secret' => 'app-secret',
        ]);
    }

    private function sign(array $payload): array
    {
        $body = json_encode($payload);

        return ['X-Hub-Signature-256' => 'sha256='.hash_hmac('sha256', $body, 'app-secret')];
    }

    private function inboundTextPayload(string $from = '393331234567', string $text = 'Ciao', string $id = 'wamid.AAA'): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'contacts' => [['profile' => ['name' => 'Mario Rossi'], 'wa_id' => $from]],
                        'messages' => [[
                            'from' => $from,
                            'id' => $id,
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => $text],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    public function test_get_handshake_echoes_challenge_when_token_matches(): void
    {
        $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=verify-me&hub_challenge=12345')
            ->assertOk()
            ->assertSee('12345');

        $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=12345')
            ->assertForbidden();
    }

    public function test_post_without_valid_signature_is_rejected(): void
    {
        Queue::fake();

        $this->postJson('/webhooks/whatsapp', $this->inboundTextPayload())
            ->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_valid_post_queues_the_processing_job(): void
    {
        Queue::fake();

        $payload = $this->inboundTextPayload();

        $this->postJson('/webhooks/whatsapp', $payload, $this->sign($payload))
            ->assertOk();

        Queue::assertPushed(ProcessInboundWhatsAppJob::class);
    }

    public function test_inbound_text_creates_a_conversation_and_message_and_links_the_user(): void
    {
        $user = User::factory()->create(['whatsapp_number' => '+39 333 123 4567']);

        $payload = $this->inboundTextPayload(text: 'Hello there');
        $this->postJson('/webhooks/whatsapp', $payload, $this->sign($payload))->assertOk();

        $conversation = WhatsAppConversation::firstOrFail();
        $this->assertSame('393331234567', $conversation->wa_contact_id);
        $this->assertSame('Mario Rossi', $conversation->wa_contact_name);
        $this->assertSame($user->id, $conversation->user_id);
        $this->assertNotNull($conversation->last_inbound_at);

        $message = $conversation->messages()->firstOrFail();
        $this->assertSame(WhatsAppMessage::DIRECTION_IN, $message->direction);
        $this->assertSame('Hello there', $message->body);
    }

    public function test_a_retried_webhook_does_not_duplicate_the_message(): void
    {
        $payload = $this->inboundTextPayload(id: 'wamid.DUP');

        $this->postJson('/webhooks/whatsapp', $payload, $this->sign($payload))->assertOk();
        $this->postJson('/webhooks/whatsapp', $payload, $this->sign($payload))->assertOk();

        $this->assertSame(1, WhatsAppMessage::where('wa_message_id', 'wamid.DUP')->count());
    }

    public function test_status_callback_updates_the_matching_outbound_message(): void
    {
        $conversation = WhatsAppConversation::create(['wa_contact_id' => '393331234567', 'status' => 'open']);
        $message = $conversation->messages()->create([
            'direction' => WhatsAppMessage::DIRECTION_OUT,
            'wa_message_id' => 'wamid.OUT1',
            'type' => 'text',
            'body' => 'hi',
            'status' => WhatsAppMessage::STATUS_SENT,
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [['changes' => [['value' => [
                'statuses' => [[
                    'id' => 'wamid.OUT1',
                    'status' => 'read',
                    'timestamp' => (string) now()->timestamp,
                ]],
            ]]]]],
        ];

        $this->postJson('/webhooks/whatsapp', $payload, $this->sign($payload))->assertOk();

        $message->refresh();
        $this->assertSame(WhatsAppMessage::STATUS_READ, $message->status);
        $this->assertNotNull($message->read_at);
        $this->assertNotNull($message->delivered_at);
    }
}
