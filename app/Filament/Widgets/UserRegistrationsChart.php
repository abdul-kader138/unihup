<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;

class UserRegistrationsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'User registrations';

    protected int|string|array $columnSpan = 2;

    protected function getData(): array
    {
        $days = 14;
        $labels = [];
        $registrations = [];

        for ($offset = $days - 1; $offset >= 0; $offset--) {
            $date = now()->subDays($offset)->startOfDay();

            $labels[] = $date->format('d M');
            $registrations[] = User::query()
                ->whereBetween('created_at', [$date, $date->copy()->endOfDay()])
                ->count();
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
