<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Thin wrapper over the WhatsApp Business Cloud API (graph.facebook.com).
 *
 * Only the pieces the student <-> staff support chat needs: send a free-form
 * text message, send an approved template (to re-open a thread past the 24h
 * service window), and pull down media a customer sent us. Everything is
 * synchronous HTTP — callers are expected to run sends from a queued job.
 */
class WhatsAppClient
{
    /**
     * @param  array{api_version:string,phone_number_id:?string,waba_id:?string,access_token:?string,app_secret:?string,verify_token:?string,media_disk:string}  $config
     */
    public function __construct(private readonly array $config) {}

    public static function fromConfig(): self
    {
        return new self(config('services.whatsapp'));
    }

    /** Whether enough is configured to actually talk to Meta. */
    public function configured(): bool
    {
        return filled($this->config['phone_number_id']) && filled($this->config['access_token']);
    }

    /**
     * Send a plain-text message. Only valid inside the 24h customer service
     * window; outside it Meta rejects the send and you must use sendTemplate().
     *
     * @return string the Meta message id (wamid...)
     */
    public function sendText(string $to, string $body, bool $previewUrl = false): string
    {
        $response = $this->request()->post($this->messagesUrl(), [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::normalizeNumber($to),
            'type' => 'text',
            'text' => ['preview_url' => $previewUrl, 'body' => $body],
        ]);

        return $this->extractMessageId($response);
    }

    /**
     * Send a pre-approved message template.
     *
     * @param  array<int,array<string,mixed>>  $components  raw Meta "components" array (header/body params); [] for a template with no variables
     * @return string the Meta message id (wamid...)
     */
    public function sendTemplate(string $to, string $name, string $languageCode = 'en', array $components = []): string
    {
        $template = [
            'name' => $name,
            'language' => ['code' => $languageCode],
        ];

        if ($components !== []) {
            $template['components'] = $components;
        }

        $response = $this->request()->post($this->messagesUrl(), [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => self::normalizeNumber($to),
            'type' => 'template',
            'template' => $template,
        ]);

        return $this->extractMessageId($response);
    }

    /** Best-effort "blue tick" — mark an inbound message as read. */
    public function markRead(string $waMessageId): void
    {
        $this->request()->post($this->messagesUrl(), [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $waMessageId,
        ]);
    }

    /**
     * Download media the customer sent (two-step: resolve the id to a
     * short-lived URL, then fetch the bytes with the same bearer token) and
     * store it on the configured disk.
     *
     * @return array{path:string,mime:?string}
     */
    public function storeMedia(string $mediaId, string $directory = 'whatsapp/media'): array
    {
        $meta = $this->request()->get($this->graphUrl($mediaId))->throw()->json();

        $url = $meta['url'] ?? null;
        if (! is_string($url) || $url === '') {
            throw new WhatsAppException("Media {$mediaId} has no download URL", $meta ?? []);
        }

        $mime = $meta['mime_type'] ?? null;
        $binary = $this->request()->get($url)->throw()->body();

        $extension = $mime ? (Str::after($mime, '/') ?: 'bin') : 'bin';
        $path = trim($directory, '/')."/{$mediaId}.{$extension}";
        Storage::disk($this->config['media_disk'])->put($path, $binary);

        return ['path' => $path, 'mime' => $mime];
    }

    /** Strip everything but digits — Meta wants the E.164 number without '+'. */
    public static function normalizeNumber(string $number): string
    {
        return preg_replace('/\D+/', '', $number) ?? '';
    }

    private function request(): PendingRequest
    {
        return Http::withToken($this->config['access_token'])
            ->acceptJson()
            ->timeout(15)
            ->retry(2, 200, throw: false);
    }

    private function messagesUrl(): string
    {
        return $this->graphUrl("{$this->config['phone_number_id']}/messages");
    }

    private function graphUrl(string $path): string
    {
        return "https://graph.facebook.com/{$this->config['api_version']}/{$path}";
    }

    private function extractMessageId(Response $response): string
    {
        if ($response->failed()) {
            $body = $response->json() ?? [];
            $message = $body['error']['message'] ?? $response->body();

            throw new WhatsAppException("WhatsApp API error: {$message}", $body, $response->status());
        }

        $id = $response->json('messages.0.id');
        if (! is_string($id) || $id === '') {
            throw new WhatsAppException('WhatsApp API returned no message id', $response->json() ?? []);
        }

        return $id;
    }
}
