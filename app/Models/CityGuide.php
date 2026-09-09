<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Curated "what it's like to live here" page for a university city —
 * housing, cost of living, transport, student life, safety. Staff-managed
 * (CityGuideResource); students read it on App\Filament\Pages\CityGuides and
 * from the program detail modal.
 */
class CityGuide extends Model
{
    protected $fillable = [
        'city', 'slug', 'region', 'intro', 'housing', 'cost_of_living',
        'transport', 'student_life', 'safety', 'useful_links',
        'last_verified_at', 'is_published',
    ];

    /** Prose sections in display order: property => label. */
    public const SECTIONS = [
        'housing' => 'Housing',
        'cost_of_living' => 'Cost of living',
        'transport' => 'Getting around',
        'student_life' => 'Student life',
        'safety' => 'Safety & practicalities',
    ];

    protected function casts(): array
    {
        return [
            'useful_links' => 'array',
            'last_verified_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CityGuide $guide) {
            if (blank($guide->slug) || $guide->isDirty('city')) {
                $guide->slug = Str::slug($guide->city);
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Match a free-text city name (English or Italian) to a published guide.
     */
    public static function forCity(?string $city): ?self
    {
        if (blank($city)) {
            return null;
        }

        return static::published()->where('slug', Str::slug($city))->first();
    }

    /**
     * @return array<int, array{label: string, body: string}>
     */
    public function filledSections(): array
    {
        $out = [];

        foreach (self::SECTIONS as $key => $label) {
            if (filled($this->{$key})) {
                $out[] = ['label' => $label, 'body' => $this->{$key}];
            }
        }

        return $out;
    }
}
