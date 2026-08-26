<?php

namespace App\Models;

use App\Support\Avatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class University extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'city', 'region', 'website_url', 'description', 'logo'];

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
