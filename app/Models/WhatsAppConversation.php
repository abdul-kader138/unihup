<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
