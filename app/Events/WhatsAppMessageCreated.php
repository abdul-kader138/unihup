<?php

namespace App\Events;

use App\Models\WhatsAppMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired the moment a whatsapp_messages row is written — a customer's portal
 * message (SupportChat), a staff reply (WhatsAppInbox), or an inbound Meta
 * webhook (ProcessInboundWhatsAppJob). Drives the live update on both pages.
 *
 * ShouldBroadcastNow: sent synchronously so the message appears instantly.
 * With BROADCAST_CONNECTION=null (this app's default; see phpunit.xml for
 * the same in tests) this is a silent no-op, so dispatching it is always
 * safe even when Reverb isn't running.
 */
class WhatsAppMessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public WhatsAppMessage $message) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('whatsapp.conversation.'.$this->message->conversation_id),
            new PrivateChannel('whatsapp.inbox'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.created';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'direction' => $this->message->direction,
        ];
    }
}
