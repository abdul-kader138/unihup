<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInboundWhatsAppJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * The single endpoint Meta calls for the WhatsApp Business Cloud API.
 *
 *  - GET  = the one-time subscription handshake. Meta sends hub.mode /
 *           hub.verify_token / hub.challenge; we echo the challenge back as
 *           plain text if the token matches WHATSAPP_VERIFY_TOKEN.
 *  - POST = inbound messages and status callbacks. Authenticated by the
 *           X-Hub-Signature-256 header (HMAC-SHA256 of the raw body keyed
 *           with the app secret). We verify, hand the payload to a queued
 *           job, and return 200 immediately — Meta retries with backoff on
 *           any non-2xx, so the request itself must stay cheap.
 */
class WhatsAppWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if ($request->isMethod('get')) {
            return $this->verify($request);
        }

        if (! $this->signatureValid($request)) {
            Log::warning('WhatsApp webhook: bad or missing X-Hub-Signature-256');

            return response('invalid signature', Response::HTTP_FORBIDDEN);
        }

        $payload = $request->json()->all();

        if (($payload['object'] ?? null) === 'whatsapp_business_account') {
            ProcessInboundWhatsAppJob::dispatch($payload);
        }

        return response('', Response::HTTP_OK);
    }

    private function verify(Request $request): Response
    {
        $expected = (string) config('services.whatsapp.verify_token');

        if (
            $request->query('hub_mode') === 'subscribe'
            && $expected !== ''
            && hash_equals($expected, (string) $request->query('hub_verify_token'))
        ) {
            return response((string) $request->query('hub_challenge'), Response::HTTP_OK)
                ->header('Content-Type', 'text/plain');
        }

        return response('verification failed', Response::HTTP_FORBIDDEN);
    }

    private function signatureValid(Request $request): bool
    {
        $secret = (string) config('services.whatsapp.app_secret');

        // No secret configured (local/dev) — accept, so ngrok testing works
        // without wiring the app secret. Always set it in production.
        if ($secret === '') {
            return true;
        }

        $header = (string) $request->header('X-Hub-Signature-256', '');
        if (! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $header);
    }
}
