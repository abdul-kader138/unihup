<?php

namespace App\Filament\Pages;

use App\Models\Deadline;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * The student's merged deadline timeline — every deadline relevant to their
 * shortlist and situation (Deadline::relevantTo), split into overdue /
 * upcoming, with an .ics export. Open to every panel user; no HasPageShield.
 */
class MyDeadlines extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'My Deadlines';

    protected static ?string $title = 'My Deadlines';

    protected static ?string $slug = 'my-deadlines';

    protected static ?string $navigationGroup = 'My Journey';

    protected static ?int $navigationSort = 25;

    protected static string $view = 'filament.pages.my-deadlines';

    /**
     * @return array{
     *     overdue: Collection<int, Deadline>,
     *     upcoming: Collection<int, Deadline>,
     *     has_shortlist: bool,
     * }
     */
    public function getDeadlineData(): array
    {
        $all = Deadline::relevantTo(auth()->user());

        return [
            'overdue' => $all->filter(fn (Deadline $d) => $d->isPast())->values(),
            'upcoming' => $all->reject(fn (Deadline $d) => $d->isPast())->values(),
            'has_shortlist' => auth()->user()->shortlistItems()->exists(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Add to calendar (.ics)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('deadlines.ics'), shouldOpenInNewTab: true),
        ];
    }
}
