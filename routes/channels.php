<?php

use App\Filament\Pages\WhatsAppInbox;
use App\Models\User;
use App\Models\WhatsAppConversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Registered from bootstrap/app.php (withRouting: channels). The
| /broadcasting/auth endpoint runs through the web + auth middleware, so
| $user is the logged-in panel user. Used by App\Filament\Pages\SupportChat
| and App\Filament\Pages\WhatsAppInbox for live message updates over Reverb.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// One customer conversation. Its own user may listen; so may any staff
// member who can open the inbox (same gate as WhatsAppInbox::canAccess()).
Broadcast::channel('whatsapp.conversation.{conversationId}', function (User $user, int $conversationId) {
    if (WhatsAppInbox::canAccess()) {
        return true;
    }

    return WhatsAppConversation::whereKey($conversationId)
        ->where('user_id', $user->id)
        ->exists();
});

// The staff inbox firehose — every new inbound/outbound message, for the
// conversation-list badge and live thread refresh.
Broadcast::channel('whatsapp.inbox', fn (User $user) => WhatsAppInbox::canAccess());
