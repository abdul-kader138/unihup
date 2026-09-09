<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One student's state on one journey-checklist step (see
 * App\Support\JourneyTemplate). A step with no row is "pending".
 */
class JourneyProgress extends Model
{
    protected $table = 'journey_progress';

    protected $fillable = ['user_id', 'step_key', 'state', 'completed_at'];

    public const STATE_PENDING = 'pending';

    public const STATE_DONE = 'done';

    public const STATE_SKIPPED = 'skipped';

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
