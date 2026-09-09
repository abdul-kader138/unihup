<?php

namespace App\Models;

use App\Support\ItalianRegions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * A date an applicant needs to hit — curated by staff (DeadlineResource) or
 * seeded from the well-known national ones. Each deadline attaches to a
 * scope: everyone (global), a university, a specific program, a regional
 * scholarship body, or the admission-test track. The student-facing
 * App\Filament\Pages\MyDeadlines and the reminder command only ever show a
 * student the deadlines relevant to their own shortlist and situation
 * (relevantTo()).
 */
class Deadline extends Model
{
    protected $fillable = [
        'scope_type', 'scope_id', 'title', 'description', 'category',
        'due_at', 'due_precision', 'cycle_label', 'url', 'source_url',
        'last_verified_at', 'is_active',
    ];

    public const SCOPE_GLOBAL = 'global';

    public const SCOPE_UNIVERSITY = 'university';

    public const SCOPE_PROGRAM = 'degree_program';

    public const SCOPE_SCHOLARSHIP = 'regional_scholarship';

    public const SCOPE_ADMISSION_TEST = 'admission_test';

    public const SCOPE_TYPES = [
        self::SCOPE_GLOBAL => 'All applicants',
        self::SCOPE_UNIVERSITY => 'A university',
        self::SCOPE_PROGRAM => 'A degree program',
        self::SCOPE_SCHOLARSHIP => 'A regional scholarship body',
        self::SCOPE_ADMISSION_TEST => 'Admission-test applicants',
    ];

    public const CATEGORIES = [
        'pre_enrolment' => 'Pre-enrolment (Universitaly)',
        'application' => 'University application',
        'test_registration' => 'Admission test registration',
        'test_sitting' => 'Admission test date',
        'scholarship' => 'Scholarship / DSU',
        'visa' => 'Visa / immigration',
        'enrolment' => 'Enrolment / matriculation',
        'other' => 'Other',
    ];

    public const CATEGORY_COLORS = [
        'pre_enrolment' => 'info',
        'application' => 'primary',
        'test_registration' => 'warning',
        'test_sitting' => 'warning',
        'scholarship' => 'success',
        'visa' => 'danger',
        'enrolment' => 'gray',
        'other' => 'gray',
    ];

    public const PRECISIONS = [
        'day' => 'Exact day',
        'month' => 'Month only',
        'window' => 'Around this date',
    ];

    /** Days-before offsets a reminder is sent at. */
    public const REMINDER_OFFSETS = [14, 3, 1];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'last_verified_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('due_at', '>=', now()->startOfDay());
    }

    /**
     * The deadlines that matter to this student: global + admission-test (if a
     * shortlisted program is restricted) + anything scoped to a university,
     * program or scholarship region on their shortlist. Sorted by due date.
     *
     * @return Collection<int, Deadline>
     */
    public static function relevantTo(User $user, bool $upcomingOnly = false): Collection
    {
        $items = $user->shortlistItems()
            ->with('degreeProgram:id,university_id,admission_type')
            ->get();

        $programIds = $items->pluck('degree_program_id')->filter()->unique()->values();
        $universityIds = $items->pluck('degreeProgram.university_id')->filter()->unique()->values();
        $hasRestricted = $items->contains(fn ($i) => $i->degreeProgram?->admission_type === 'restricted');

        $scholarshipIds = collect();

        if ($universityIds->isNotEmpty()) {
            $regionKeys = University::whereIn('id', $universityIds)
                ->pluck('region')
                ->map(fn ($r) => ItalianRegions::canonicalize($r))
                ->filter()
                ->unique();

            if ($regionKeys->isNotEmpty()) {
                $scholarshipIds = RegionalScholarship::all()
                    ->filter(fn ($s) => $regionKeys->contains(ItalianRegions::canonicalize($s->region)))
                    ->pluck('id');
            }
        }

        $query = static::query()->active()
            ->where(function (Builder $q) use ($programIds, $universityIds, $scholarshipIds, $hasRestricted) {
                $q->where('scope_type', self::SCOPE_GLOBAL);

                if ($hasRestricted) {
                    $q->orWhere('scope_type', self::SCOPE_ADMISSION_TEST);
                }

                if ($universityIds->isNotEmpty()) {
                    $q->orWhere(fn (Builder $s) => $s->where('scope_type', self::SCOPE_UNIVERSITY)->whereIn('scope_id', $universityIds));
                }

                if ($programIds->isNotEmpty()) {
                    $q->orWhere(fn (Builder $s) => $s->where('scope_type', self::SCOPE_PROGRAM)->whereIn('scope_id', $programIds));
                }

                if ($scholarshipIds->isNotEmpty()) {
                    $q->orWhere(fn (Builder $s) => $s->where('scope_type', self::SCOPE_SCHOLARSHIP)->whereIn('scope_id', $scholarshipIds));
                }
            });

        if ($upcomingOnly) {
            $query->upcoming();
        }

        return $query->orderBy('due_at')->get();
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /** Human label for what this deadline is attached to. */
    public function scopeName(): string
    {
        return match ($this->scope_type) {
            self::SCOPE_GLOBAL => 'All applicants',
            self::SCOPE_ADMISSION_TEST => 'Admission-test applicants',
            self::SCOPE_UNIVERSITY => University::find($this->scope_id)?->display_name ?? 'A university',
            self::SCOPE_PROGRAM => DegreeProgram::with('university')->find($this->scope_id)?->name ?? 'A program',
            self::SCOPE_SCHOLARSHIP => RegionalScholarship::find($this->scope_id)?->body_name ?? 'A scholarship body',
            default => ucfirst((string) $this->scope_type),
        };
    }

    public function daysUntil(): int
    {
        return (int) round(now()->startOfDay()->diffInDays($this->due_at->copy()->startOfDay(), false));
    }

    public function isPast(): bool
    {
        return $this->due_at->isBefore(now()->startOfDay());
    }
}
