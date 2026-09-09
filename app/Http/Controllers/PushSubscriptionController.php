<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stores / removes a browser Push API subscription for the signed-in user.
 * The browser sends the object returned by
 * `PushManager.subscribe().toJSON()` — endpoint + keys.p256dh + keys.auth.
 * See public/js/push.js.
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:50'],
        ]);

        $endpoint = $data['endpoint'];

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashFor($endpoint)],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $endpoint,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? null,
            ],
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $endpoint = (string) $request->input('endpoint');

        if ($endpoint !== '') {
            PushSubscription::query()
                ->where('user_id', $request->user()->id)
                ->where('endpoint_hash', PushSubscription::hashFor($endpoint))
                ->delete();
        }

        return response()->json(['ok' => true]);
    }
}
