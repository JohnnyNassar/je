<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $pending = Order::where('status', 'pending')->count();
        $revenue = (float) Order::where('status', 'delivered')->sum('total');
        $lowStock = Product::active()->where('stock', '>', 0)->where('stock', '<=', 3)->count();

        return [
            Stat::make('Orders', Order::count())
                ->description($pending . ' pending')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'success'),
            Stat::make('Revenue (delivered)', money_format($revenue))
                ->description('Collected from delivered orders')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Customers', Customer::count())
                ->description('Registered + guest')
                ->descriptionIcon('heroicon-m-users'),
            Stat::make('Low stock', $lowStock)
                ->description('Active products with 3 or fewer left')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStock > 0 ? 'danger' : 'gray'),
        ];
    }
}
