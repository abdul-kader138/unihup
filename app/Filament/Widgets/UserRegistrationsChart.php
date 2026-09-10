<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class UserRegistrationsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'User registrations';

    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $days = 14;
        $since = now()->subDays($days - 1)->startOfDay();

        // One grouped query instead of 14 COUNT()s in a loop.
        $counts = Cache::remember('admin.stats.registrations-14d', now()->addMinutes(10), fn () => User::query()
            ->where('created_at', '>=', $since)
            ->get(['created_at'])
            ->countBy(fn (User $u) => $u->created_at->format('Y-m-d')));

        $labels = [];
        $registrations = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $date = now()->subDays($offset)->startOfDay();

            $labels[] = $date->format('d M');
            $registrations[] = $counts[$date->format('Y-m-d')] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'New users',
                    'data' => $registrations,
                    'borderColor' => '#6366f1',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
