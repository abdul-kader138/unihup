<?php

namespace App\Jobs;

use App\Models\User;
use App\Support\WebPushSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fan out one browser push notification to all of a user's subscriptions.
 * A no-op when web push is not configured (see App\Support\WebPushSender).
 */
class SendWebPushNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array{title: string, body?: string, url?: string, tag?: string}  $payload
     */
    public function __construct(
        public User $user,
        public array $payload,
    ) {}

    public function handle(): void
    {
        WebPushSender::sendToUser($this->user, $this->payload);
    }
}
