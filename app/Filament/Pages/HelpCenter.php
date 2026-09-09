<?php

namespace App\Filament\Pages;

use App\Models\FaqEntry;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Searchable student Help Center backed by App\Models\FaqEntry. Open to
 * every panel user; no HasPageShield. Linked from Support Chat so students
 * check here before opening a thread.
 */
class HelpCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationLabel = 'Help Center';

    protected static ?string $title = 'Help Center';

    protected static ?string $slug = 'help-center';

    protected static ?string $navigationGroup = 'Guides';

    protected static ?int $navigationSort = 45;

    protected static string $view = 'filament.pages.help-center';

    public string $search = '';

    public ?string $category = null;

    public function mount(): void
    {
        $this->search = request()->string('q')->toString();
    }

    /**
     * @return array<int, string>
     */
    public function getCategories(): array
    {
        return FaqEntry::published()
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }

    /**
     * @return Collection<string, Collection<int, FaqEntry>>
     */
    public function getGroupedEntries(): Collection
    {
        return FaqEntry::published()
            ->search($this->search)
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->orderBy('category')
            ->orderBy('sort')
            ->get()
            ->groupBy('category');
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->category = null;
    }
}
