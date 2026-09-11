<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per shortlist item that has ever triggered a stalled-progress
 * nudge — the idempotency/cooldown ledger for
 * App\Console\Commands\NotifyStalledProgress. sent_at is updated (not
 * re-inserted) on each re-fire, so a still-stalled item is only nudged again
 * after the cooldown window has passed.
 */
class StalledProgressNudgeLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'shortlist_item_id', 'sent_at'];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shortlistItem(): BelongsTo
    {
        return $this->belongsTo(ShortlistItem::class);
    }
}
