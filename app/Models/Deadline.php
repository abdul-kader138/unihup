<?php

namespace App\Models;

use App\Support\ItalianRegions;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

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
    public static function relevantTo(User $user, bool $upcomingOnly = false, ?Collection $shortlistItems = null): Collection
    {
        // Callers that already hold the shortlist (e.g. MyJourney renders it
        // four different ways) can pass it in to skip the reload.
        $items = $shortlistItems ?? $user->shortlistItems()
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
                // Region strings can't be canonicalised in SQL, so the match
                // stays in PHP — but only id + region need to come back.
                $scholarshipIds = RegionalScholarship::query()
                    ->get(['id', 'region'])
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

        $deadlines = $query->orderBy('due_at')->get();

        // One batched lookup per scope type so a list render never hits
        // scopeName()'s per-row fallback query.
        self::warmScopeNames($deadlines);

        return $deadlines;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * Resolve the scope label for a batch of deadlines in three queries
     * (one per scoped type) and prime the request-local cache scopeName()
     * reads from.
     *
     * @param  iterable<int, Deadline>  $deadlines
     */
    public static function warmScopeNames(iterable $deadlines): void
    {
        $idsByType = [];

        foreach ($deadlines as $deadline) {
            if ($deadline->scope_id === null) {
                continue;
            }

            $idsByType[$deadline->scope_type][$deadline->scope_id] = true;
        }

        $loaders = [
            self::SCOPE_UNIVERSITY => fn (array $ids) => University::query()->whereKey($ids)->get()
                ->mapWithKeys(fn (University $u) => [$u->id => $u->display_name]),
            self::SCOPE_PROGRAM => fn (array $ids) => DegreeProgram::query()->whereKey($ids)->get(['id', 'name'])
                ->mapWithKeys(fn (DegreeProgram $p) => [$p->id => $p->name]),
            self::SCOPE_SCHOLARSHIP => fn (array $ids) => RegionalScholarship::query()->whereKey($ids)->get(['id', 'body_name'])
                ->mapWithKeys(fn (RegionalScholarship $s) => [$s->id => $s->body_name]),
        ];

        foreach ($loaders as $type => $loader) {
            if (empty($idsByType[$type])) {
                continue;
            }

            foreach ($loader(array_keys($idsByType[$type])) as $id => $name) {
                self::scopeNameCache()->forever("{$type}:{$id}", $name);
            }
        }
    }

    /** Human label for what this deadline is attached to. */
    public function scopeName(): string
    {
        return match ($this->scope_type) {
            self::SCOPE_GLOBAL => 'All applicants',
            self::SCOPE_ADMISSION_TEST => 'Admission-test applicants',
            self::SCOPE_UNIVERSITY => $this->cachedScopeName(
                fn () => University::find($this->scope_id)?->display_name, 'A university'),
            self::SCOPE_PROGRAM => $this->cachedScopeName(
                fn () => DegreeProgram::query()->whereKey($this->scope_id)->value('name'), 'A program'),
            self::SCOPE_SCHOLARSHIP => $this->cachedScopeName(
                fn () => RegionalScholarship::find($this->scope_id)?->body_name, 'A scholarship body'),
            default => ucfirst((string) $this->scope_type),
        };
    }

    /**
     * Read one scope label from the request-local cache, falling back to a
     * single-row query only when warmScopeNames() has not primed it.
     */
    private function cachedScopeName(callable $loader, string $default): string
    {
        return self::scopeNameCache()->rememberForever(
            "{$this->scope_type}:{$this->scope_id}",
            fn () => $loader() ?? $default,
        );
    }

    /**
     * The `array` store is rebuilt with the container each request (and each
     * test), so scope labels never leak between them the way a static array
     * would.
     */
    private static function scopeNameCache(): CacheRepository
    {
        return Cache::store('array');
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
