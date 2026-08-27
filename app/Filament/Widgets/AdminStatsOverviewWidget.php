<?php

namespace App\Filament\Widgets;

use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Total users', User::query()->count())
                ->description(User::query()->whereNull('email_verified_at')->count().' awaiting verification')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('primary'),

            Stat::make('Universities', University::query()->count())
                ->description(University::query()->whereNotNull('website_url')->count().' with official websites')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('success'),

            Stat::make('Degree programs', DegreeProgram::query()->count())
                ->description(DegreeProgram::query()->whereNotNull('last_verified_at')->count().' verified records')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info'),

            Stat::make('Subjects', Subject::query()->count())
                ->description('Available search categories')
                ->descriptionIcon('heroicon-m-tag')
                ->color('warning'),
        ];
    }
}
