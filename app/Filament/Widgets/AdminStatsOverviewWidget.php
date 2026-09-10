<?php

namespace App\Filament\Widgets;

use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class AdminStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Eight COUNT()s that barely move minute to minute — cache the whole
        // block so a dashboard refresh is one cache read, not eight scans.
        $c = Cache::remember('admin.stats.overview', now()->addMinutes(10), fn () => [
            'users' => User::query()->count(),
            'users_unverified' => User::query()->whereNull('email_verified_at')->count(),
            'universities' => University::query()->count(),
            'universities_with_site' => University::query()->whereNotNull('website_url')->count(),
            'programs' => DegreeProgram::query()->count(),
            'programs_verified' => DegreeProgram::query()->whereNotNull('last_verified_at')->count(),
            'subjects' => Subject::query()->count(),
        ]);

        return [
            Stat::make('Total users', $c['users'])
                ->description($c['users_unverified'].' awaiting verification')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('primary'),

            Stat::make('Universities', $c['universities'])
                ->description($c['universities_with_site'].' with official websites')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('success'),

            Stat::make('Degree programs', $c['programs'])
                ->description($c['programs_verified'].' verified records')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Subjects', $c['subjects'])
                ->description('Available search categories')
                ->descriptionIcon('heroicon-m-tag')
                ->color('warning'),
        ];
    }
}
