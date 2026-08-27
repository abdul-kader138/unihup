<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdminStatsOverviewWidget;
use App\Filament\Widgets\RecentUsersWidget;
use App\Filament\Widgets\UserRegistrationsChart;
use App\Filament\Widgets\WelcomeHeaderWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            WelcomeHeaderWidget::class,
            AdminStatsOverviewWidget::class,
            UserRegistrationsChart::class,
            RecentUsersWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 3;
    }
}
