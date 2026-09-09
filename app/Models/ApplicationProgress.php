<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One checkbox state on the per-program application checklist
 * (App\Support\ApplicationSteps), scoped to a ShortlistItem.
 */
class ApplicationProgress extends Model
{
    protected $table = 'application_progress';

    protected $fillable = ['shortlist_item_id', 'step_key', 'done', 'completed_at'];

    protected function casts(): array
    {
        return [
            'done' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function shortlistItem(): BelongsTo
    {
        return $this->belongsTo(ShortlistItem::class);
    }
}
