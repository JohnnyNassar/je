<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OrdersChart extends ChartWidget
{
    protected static ?string $heading = 'Orders (last 14 days)';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn ($i) => Carbon::today()->subDays($i));
        $counts = $days->map(fn ($day) => Order::whereDate('created_at', $day)->count());

        return [
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $counts->all(),
                    'borderColor' => '#287d88',
                    'backgroundColor' => 'rgba(40, 125, 136, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $days->map(fn ($day) => $day->format('M j'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
