<?php

namespace App\Filament\Resources\LoyaltyTransactionResource\Widgets;

use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Services\LoyaltyService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class LoyaltyStatsOverview extends BaseWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $loyalty = app(LoyaltyService::class);

        $outstanding = (int) Customer::sum('points_balance');
        $members = Customer::where('points_balance', '>', 0)->count();

        $since = Carbon::now()->subDays(30);
        $earned30 = (int) LoyaltyTransaction::where('type', 'earn')
            ->where('created_at', '>=', $since)->sum('points');
        $redeemed30 = (int) abs(LoyaltyTransaction::where('type', 'redeem')
            ->where('created_at', '>=', $since)->sum('points'));

        return [
            Stat::make('Points outstanding', number_format($outstanding))
                ->description($members . ' members with a balance')
                ->descriptionIcon('heroicon-m-gift')
                ->color('primary'),
            Stat::make('Liability value', money_format($loyalty->valueOfPoints($outstanding)))
                ->description('What outstanding points are worth')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
            Stat::make('Earned (30 days)', '+' . number_format($earned30))
                ->description('Points credited to customers')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Redeemed (30 days)', number_format($redeemed30))
                ->description('Points spent on discounts')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('gray'),
        ];
    }
}
