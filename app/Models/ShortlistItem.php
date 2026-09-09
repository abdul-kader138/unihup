<?php

namespace App\Models;

use App\Support\ApplicationSteps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One program a student has saved to their personal list, plus where they
 * are with it. This is the spine of the "admission journey" workspace —
 * My Applications, Compare, and (later) the deadline hub all read from here.
 */
class ShortlistItem extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'degree_program_id', 'status', 'notes'];

    /**
     * Status label per key. Order matters — this is also the order shown in
     * the My Applications status dropdown and grouped counts.
     */
    public const STATUSES = [
        'researching' => 'Researching',
        'applying' => 'Preparing application',
        'submitted' => 'Application submitted',
        'admitted' => 'Admitted',
        'waitlisted' => 'Waitlisted',
        'rejected' => 'Not admitted',
        'enrolled' => 'Enrolled',
        'withdrawn' => 'Withdrawn',
    ];

    /** Filament badge colour per status. */
    public const STATUS_COLORS = [
        'researching' => 'gray',
        'applying' => 'info',
        'submitted' => 'warning',
        'admitted' => 'success',
        'waitlisted' => 'warning',
        'rejected' => 'danger',
        'enrolled' => 'success',
        'withdrawn' => 'gray',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function degreeProgram(): BelongsTo
    {
        return $this->belongsTo(DegreeProgram::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function applicationProgress(): HasMany
    {
        return $this->hasMany(ApplicationProgress::class);
    }

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(StudentDocument::class, 'application_documents')
            ->withPivot('status')
            ->withTimestamps();
    }

    /**
     * How far through the per-program application checklist this item is.
     *
     * @return array{done: int, total: int, percent: int}
     */
    public function applicationChecklist(bool $isEuCitizen): array
    {
        $steps = ApplicationSteps::forItem($this, $isEuCitizen);
        $done = $this->applicationProgress
            ->where('done', true)
            ->pluck('step_key')
            ->intersect(array_column($steps, 'key'))
            ->count();

        $total = count($steps);

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
        ];
    }
}
