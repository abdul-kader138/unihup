<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per reminder actually sent — the idempotency ledger for
 * App\Console\Commands\SendDeadlineReminders, so a re-run (or a second run
 * the same day) never double-notifies.
 */
class DeadlineReminderLog extends Model
{
    public $timestamps = false;

    protected $table = 'deadline_reminder_log';

    protected $fillable = ['user_id', 'deadline_id', 'offset_days', 'channel', 'sent_at'];

    public const CHANNEL_MAIL = 'mail';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'offset_days' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deadline(): BelongsTo
    {
        return $this->belongsTo(Deadline::class);
    }
}
