<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A scholarship a student is tracking, with their application status. The
 * matched-but-untracked list is computed live on
 * App\Filament\Pages\MyScholarships; this table only holds the ones the
 * student has actively added.
 */
class ScholarshipTracker extends Model
{
    protected $table = 'scholarship_tracker';

    protected $fillable = ['user_id', 'kind', 'ref', 'label', 'status', 'deadline_at', 'notes'];

    public const KIND_REGIONAL = 'regional';

    public const KIND_NATIONAL = 'national';

    /** status key => label */
    public const STATUSES = [
        'interested' => 'Interested',
        'preparing' => 'Preparing application',
        'submitted' => 'Application submitted',
        'awarded' => 'Awarded',
        'rejected' => 'Not awarded',
        'declined' => 'Declined',
    ];

    /** Filament badge colour per status. */
    public const STATUS_COLORS = [
        'interested' => 'gray',
        'preparing' => 'info',
        'submitted' => 'warning',
        'awarded' => 'success',
        'rejected' => 'danger',
        'declined' => 'gray',
    ];

    protected function casts(): array
    {
        return [
            'deadline_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
