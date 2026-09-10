<?php

namespace App\Models;

use App\Support\ApplicationSteps;
use App\Support\EligibilityEngine;
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

    protected $fillable = ['user_id', 'degree_program_id', 'status', 'tier', 'sort_order', 'notes'];

    /**
     * How likely this program is to admit the student, from their point of
     * view — the classic shortlist framing. Order = display order.
     */
    public const TIERS = [
        'reach' => 'Reach',
        'target' => 'Target',
        'safety' => 'Safety',
    ];

    public const TIER_COLORS = [
        'reach' => 'danger',
        'target' => 'warning',
        'safety' => 'success',
    ];

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

    public function tierLabel(): ?string
    {
        return $this->tier ? (self::TIERS[$this->tier] ?? $this->tier) : null;
    }

    /**
     * A first guess at reach/target/safety from the eligibility read plus
     * whether the program is restricted-access. The student can override it.
     */
    public function suggestedTier(?User $user = null): string
    {
        $program = $this->degreeProgram;
        $user ??= $this->user;

        if ($program === null || $user === null) {
            return 'target';
        }

        $verdict = EligibilityEngine::assess($program, $user)['verdict'];

        return match (true) {
            $verdict === EligibilityEngine::INELIGIBLE => 'reach',
            $verdict === EligibilityEngine::CHECK && $program->admission_type === 'restricted' => 'reach',
            $verdict === EligibilityEngine::CHECK => 'target',
            $program->admission_type === 'restricted' => 'target',
            default => 'safety',
        };
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
