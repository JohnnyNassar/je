<?php

namespace App\Filament\Resources\LoyaltyTransactionResource\Widgets;

use App\Models\LoyaltyTransaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class LoyaltyPointsChart extends ChartWidget
{
    protected static ?string $heading = 'Points earned vs redeemed';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getData(): array
    {
        $labels = [];
        $earned = [];
        $redeemed = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->startOfMonth()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $labels[] = $month->format('M');

            $earned[] = (int) LoyaltyTransaction::where('type', 'earn')
                ->whereBetween('created_at', [$start, $end])->sum('points');
            $redeemed[] = (int) abs(LoyaltyTransaction::where('type', 'redeem')
                ->whereBetween('created_at', [$start, $end])->sum('points'));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Earned',
                    'data' => $earned,
                    'backgroundColor' => 'rgba(40, 125, 136, 0.5)',
                    'borderColor' => '#287d88',
                ],
                [
                    'label' => 'Redeemed',
                    'data' => $redeemed,
                    'backgroundColor' => 'rgba(220, 38, 38, 0.5)',
                    'borderColor' => '#dc2626',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
