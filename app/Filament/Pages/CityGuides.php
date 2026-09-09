<?php

namespace App\Filament\Pages;

use App\Models\CityGuide;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Student-facing "what it's like to live here" guides for university cities.
 * Open to every panel user; no HasPageShield. `?city=` opens straight to one.
 */
class CityGuides extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'City Guides';

    protected static ?string $title = 'City Guides';

    // Not 'city-guides' — that route name belongs to the admin-only
    // CityGuideResource, and Filament silently drops one of the two on a
    // collision (same trap as App\Filament\Pages\FindUniversities).
    protected static ?string $slug = 'city-guide';

    protected static ?string $navigationGroup = 'Guides';

    protected static ?int $navigationSort = 40;

    protected static string $view = 'filament.pages.city-guides';

    public ?string $openCity = null;

    public function mount(): void
    {
        $this->openCity = request()->string('city')->toString() ?: null;
    }

    /**
     * @return Collection<int, CityGuide>
     */
    public function getGuides(): Collection
    {
        return CityGuide::published()->orderBy('city')->get();
    }

    public function openSlug(): ?string
    {
        return $this->openCity ? Str::slug($this->openCity) : null;
    }
}
