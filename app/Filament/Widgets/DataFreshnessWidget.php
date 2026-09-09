<?php

namespace App\Filament\Widgets;

use App\Models\CityGuide;
use App\Models\Deadline;
use App\Models\DegreeProgram;
use App\Models\LinkCheckResult;
use App\Models\RegionalScholarship;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * Staff dashboard widget: how stale the curated catalog is, and how many
 * external links are currently broken. Numbers link to the relevant list.
 */
class DataFreshnessWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $cutoff = now()->subDays(90);

        $stalePrograms = DegreeProgram::query()
            ->where(fn ($q) => $q->whereNull('last_verified_at')->orWhere('last_verified_at', '<', $cutoff))
            ->count();

        $staleScholarships = RegionalScholarship::query()
            ->where(fn ($q) => $q->whereNull('last_verified_at')->orWhere('last_verified_at', '<', $cutoff))
            ->count();

        $staleContent = Deadline::query()
            ->where(fn ($q) => $q->whereNull('last_verified_at')->orWhere('last_verified_at', '<', $cutoff))
            ->count()
            + CityGuide::query()
                ->where(fn ($q) => $q->whereNull('last_verified_at')->orWhere('last_verified_at', '<', $cutoff))
                ->count();

        $brokenLinks = LinkCheckResult::query()->broken()->count();
        $lastCheck = LinkCheckResult::query()->max('checked_at');

        return [
            Stat::make('Programs to re-verify', $stalePrograms)
                ->description('Not verified in 90+ days')
                ->descriptionIcon('heroicon-m-clock')
                ->color($stalePrograms > 0 ? 'warning' : 'success'),

            Stat::make('Scholarships to re-verify', $staleScholarships)
                ->description('Regional bodies, 90+ days')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($staleScholarships > 0 ? 'warning' : 'success'),

            Stat::make('Deadlines / guides stale', $staleContent)
                ->description('Curated dates + city guides, 90+ days')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($staleContent > 0 ? 'warning' : 'success'),

            Stat::make('Broken links', $brokenLinks)
                ->description($lastCheck ? 'Last checked '.Carbon::parse($lastCheck)->diffForHumans() : 'Never run')
                ->descriptionIcon('heroicon-m-link-slash')
                ->color($brokenLinks > 0 ? 'danger' : 'success'),
        ];
    }
}
