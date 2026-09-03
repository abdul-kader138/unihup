<?php

namespace Tests\Unit;

use App\Services\WhatsApp\WhatsAppClient;
use App\Services\WhatsApp\WhatsAppException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppClientTest extends TestCase
{
    private function client(): WhatsAppClient
    {
        return new WhatsAppClient([
            'api_version' => 'v22.0',
            'phone_number_id' => '111222333',
            'waba_id' => 'waba1',
            'access_token' => 'tok_abc',
            'app_secret' => 'secret',
            'verify_token' => 'verify',
            'media_disk' => 'local',
        ]);
    }

    public function test_configured_requires_phone_id_and_token(): void
    {
        $this->assertTrue($this->client()->configured());

        $bare = new WhatsAppClient([
            'api_version' => 'v22.0', 'phone_number_id' => null, 'waba_id' => null,
            'access_token' => null, 'app_secret' => null, 'verify_token' => null, 'media_disk' => 'local',
        ]);
        $this->assertFalse($bare->configured());
    }

    public function test_send_text_posts_to_the_phone_number_id_and_returns_message_id(): void
    {
        Http::fake([
            'graph.facebook.com/v22.0/111222333/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.TEST123']],
            ]),
        ]);

        $id = $this->client()->sendText('+39 333 123 4567', 'Ciao');

        $this->assertSame('wamid.TEST123', $id);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v22.0/111222333/messages'
                && $request->hasHeader('Authorization', 'Bearer tok_abc')
                && $request['to'] === '393331234567'
                && $request['type'] === 'text'
                && $request['text']['body'] === 'Ciao';
        });
    }

    public function test_send_text_throws_with_meta_error_detail_on_failure(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Message failed to send because more than 24 hours have passed', 'code' => 131047],
            ], 400),
        ]);

        try {
            $this->client()->sendText('393331234567', 'too late');
            $this->fail('expected WhatsAppException');
        } catch (WhatsAppException $e) {
            $this->assertStringContainsString('24 hours', $e->getMessage());
            $this->assertSame(131047, $e->context['error']['code']);
        }
    }

    public function test_send_template_includes_components_only_when_present(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.T']]]),
        ]);

        $this->client()->sendTemplate('393331234567', 'support_reply_reopen', 'it');

        Http::assertSent(function ($request) {
            return $request['type'] === 'template'
                && $request['template']['name'] === 'support_reply_reopen'
                && $request['template']['language']['code'] === 'it'
                && ! array_key_exists('components', $request['template']);
        });
    }
}
