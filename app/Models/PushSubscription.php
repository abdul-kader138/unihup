<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One browser push subscription for a user (a device + browser pair). Written
 * by App\Http\Controllers\PushSubscriptionController from the Push API
 * subscription object; read by App\Support\WebPushSender when a notification
 * goes out. Dead endpoints (HTTP 404/410 from the push service) are pruned
 * on send.
 */
class PushSubscription extends Model
{
    protected $fillable = [
        'user_id', 'endpoint', 'endpoint_hash', 'public_key', 'auth_token', 'content_encoding',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashFor(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }
}
