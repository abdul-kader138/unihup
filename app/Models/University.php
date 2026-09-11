<?php

namespace App\Models;

use App\Support\Avatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;

class University extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'canonical_name', 'slug', 'city', 'region', 'website_url', 'description', 'logo'];

    protected function casts(): array
    {
        return [
            'latest_ranking_overall_score' => 'float',
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->canonical_name ?: $this->name;
    }

    /**
     * Name (both forms) + place, for Scout-backed admin search.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'canonical_name' => $this->canonical_name,
            'city' => $this->city,
            'region' => $this->region,
        ];
    }

    public function degreePrograms(): HasMany
    {
        return $this->hasMany(DegreeProgram::class);
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(UniversityRanking::class);
    }

    /** The most recent CENSIS ranking row, if this university has one. */
    public function latestRanking(): ?UniversityRanking
    {
        return $this->relationLoaded('rankings')
            ? $this->rankings->sortByDesc('edition')->first()
            : $this->rankings()->orderByDesc('edition')->first();
    }

    /**
     * Copy this university's most recent CENSIS row into its denormalised
     * latest_ranking_* columns (see the matching migration). Cheap when the
     * `rankings` relation is already loaded.
     */
    public function refreshLatestRankingColumns(): void
    {
        $ranking = $this->latestRanking();

        $this->forceFill([
            'latest_ranking_position' => $ranking?->position,
            'latest_ranking_category' => $ranking?->category,
            'latest_ranking_edition' => $ranking?->edition,
            'latest_ranking_overall_score' => $ranking?->overall_score,
        ])->save();
    }

    /** Re-sync the denormalised ranking columns for every university. */
    public static function syncAllLatestRankingColumns(): void
    {
        static::query()->with('rankings')->chunkById(200, function ($universities) {
            $universities->each->refreshLatestRankingColumns();
        });
    }

    /** True when this university has a denormalised CENSIS standing. */
    public function hasRanking(): bool
    {
        return $this->latest_ranking_position !== null;
    }

    /** e.g. "#3 among Large state universities · score 84.8 · 2025/2026". */
    public function rankingSummary(): ?string
    {
        if (! $this->hasRanking()) {
            return null;
        }

        $category = UniversityRanking::CATEGORIES[$this->latest_ranking_category] ?? $this->latest_ranking_category;

        return "#{$this->latest_ranking_position} among {$category} · score {$this->latest_ranking_overall_score} · {$this->latest_ranking_edition}";
    }

    /** Every ShortlistItem across this university's degree programs. */
    public function shortlistItems(): HasManyThrough
    {
        return $this->hasManyThrough(ShortlistItem::class, DegreeProgram::class, 'university_id', 'degree_program_id');
    }

    /**
     * Copy this university's live shortlist count into the denormalised
     * shortlist_items_count column (see the matching migration).
     */
    public function refreshShortlistCount(): void
    {
        $this->update(['shortlist_items_count' => $this->shortlistItems()->count()]);
    }

    /** Re-sync the denormalised shortlist count for every university. */
    public static function syncAllShortlistCounts(): void
    {
        static::query()->chunkById(200, function ($universities) {
            $universities->each->refreshShortlistCount();
        });
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }

    /**
     * A real logo (manually uploaded, or fetched by
     * App\Services\Universities\Enrichers\UniversityLogoEnricher) when one
     * exists, otherwise a deterministic initials placeholder — so the UI
     * never shows a broken image, even for the many universities nothing
     * has fetched a logo for yet.
     */
    public function getDisplayLogoUrlAttribute(): string
    {
        return $this->logo_url ?? Avatar::initialsDataUri($this->name);
    }
}
