<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdminStatsOverviewWidget;
use App\Filament\Widgets\RecentUsersWidget;
use App\Filament\Widgets\UserRegistrationsChart;
use App\Filament\Widgets\WelcomeHeaderWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    /**
     * The dashboard is staff-only. Self-registered student / customer
     * accounts hold just the base `panel_user` role with no permissions —
     * they land on FindUniversities instead (see LoginResponse and the
     * panel home URL; App\Http\Middleware\RedirectNonAdminsFromDashboard
     * catches a direct hit on "/"). Returning false also hides it from the
     * navigation.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasRole('super_admin') || $user->getAllPermissions()->isNotEmpty());
    }

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
