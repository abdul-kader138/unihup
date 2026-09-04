<?php

namespace App\Models;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

#[Fillable(['wa_contact_id', 'wa_contact_name', 'user_id', 'assigned_to', 'status', 'last_inbound_at', 'last_outbound_at'])]
class WhatsAppConversation extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CLOSED = 'closed';

    protected $table = 'whatsapp_conversations';

    protected function casts(): array
    {
        return [
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
        ];
    }

    /**
     * WhatsApp only allows free-form (non-template) messages within 24h of the
     * customer's last inbound message. Outside that window staff must send an
     * approved template to re-open the thread.
     */
    public function withinServiceWindow(): bool
    {
        return $this->last_inbound_at !== null
            && $this->last_inbound_at->gt(now()->subDay());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'conversation_id');
    }

    /**
     * Notify whoever should see a new message in this conversation — the
     * assignee if there is one, otherwise every inbox-capable staff member.
     * Shared by App\Jobs\ProcessInboundWhatsAppJob and
     * App\Filament\Pages\SupportChat so both paths (a real WhatsApp message
     * and a portal-typed one) alert staff the same way.
     */
    public function notifyStaff(string $preview): void
    {
        $recipients = $this->assignee ? collect([$this->assignee]) : self::staffRecipients();

        if ($recipients->isEmpty()) {
            return;
        }

        $name = $this->user?->name ?: ($this->wa_contact_name ?: ($this->wa_contact_id ?: 'a user'));

        FilamentNotification::make()
            ->title("New message from {$name}")
            ->icon('heroicon-o-chat-bubble-left-right')
            ->body(Str::limit($preview, 120))
            ->sendToDatabase($recipients);
    }

    /** Everyone allowed into the inbox: the page permission holders + super admins. */
    public static function staffRecipients(): Collection
    {
        $superAdmin = (string) config('filament-shield.super_admin.name', 'super_admin');
        $staff = collect();

        foreach ([fn () => User::role($superAdmin)->get(), fn () => User::permission('page_WhatsAppInbox')->get()] as $resolve) {
            try {
                $staff = $staff->merge($resolve());
            } catch (Throwable) {
                // role / permission not created yet (php artisan shield:generate)
            }
        }

        return $staff->unique('id')->values();
    }
}
