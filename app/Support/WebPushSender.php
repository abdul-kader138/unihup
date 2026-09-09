<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Sends a browser push notification to every subscription a user has, using
 * the VAPID keys in config/webpush.php. If those keys are blank the whole
 * thing is a graceful no-op — subscriptions still store, nothing is
 * delivered — so the app runs fine without push configured. Endpoints the
 * push service reports as gone (404/410) are deleted as we go.
 */
final class WebPushSender
{
    public static function configured(): bool
    {
        return filled(config('webpush.public_key')) && filled(config('webpush.private_key'));
    }

    /**
     * @param  array{title: string, body?: string, url?: string, tag?: string}  $payload
     * @return int number of subscriptions the push service accepted
     */
    public static function sendToUser(User $user, array $payload): int
    {
        if (! self::configured()) {
            return 0;
        }

        $subscriptions = $user->pushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $body = json_encode([
            'title' => $payload['title'],
            'body' => $payload['body'] ?? '',
            'url' => $payload['url'] ?? '/',
            'tag' => $payload['tag'] ?? 'unihup',
            'icon' => config('webpush.icon'),
            'badge' => config('webpush.badge'),
        ], JSON_THROW_ON_ERROR);

        try {
            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => (string) config('webpush.subject'),
                    'publicKey' => (string) config('webpush.public_key'),
                    'privateKey' => (string) config('webpush.private_key'),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('WebPush init failed: '.$e->getMessage());

            return 0;
        }

        $byEndpoint = [];

        foreach ($subscriptions as $subscription) {
            $byEndpoint[$subscription->endpoint] = $subscription;

            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding ?: 'aesgcm',
                ]),
                $body,
            );
        }

        $accepted = 0;

        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getEndpoint();

            if ($report->isSuccess()) {
                $accepted++;

                continue;
            }

            // 404 / 410 mean the subscription is dead — drop it.
            if ($report->isSubscriptionExpired() && isset($byEndpoint[$endpoint])) {
                $byEndpoint[$endpoint]->delete();

                continue;
            }

            Log::info('WebPush delivery failed', [
                'endpoint_host' => parse_url($endpoint, PHP_URL_HOST),
                'reason' => $report->getReason(),
            ]);
        }

        return $accepted;
    }
}
