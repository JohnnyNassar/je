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
        $lowStock = Product::active()->where('stock', '>', 0)->where('stock', '<=', 3)->count();

        $stats = [];

        // Order count and revenue are the money on this page, so they come and
        // go with order access. The rest of the row still has something to say
        // to a catalogue manager, which is why the widget itself stays.
        if (auth()->user()?->canViewOrders()) {
            $pending = Order::where('status', 'pending')->count();
            $revenue = (float) Order::where('status', 'delivered')->sum('total');

            $stats[] = Stat::make('Orders', Order::count())
                ->description($pending . ' pending')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pending > 0 ? 'warning' : 'success');

            $stats[] = Stat::make('Revenue (delivered)', money_format($revenue))
                ->description('Collected from delivered orders')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success');
        }

        return array_merge($stats, [
            Stat::make('Customers', Customer::count())
                ->description('Registered + guest')
                ->descriptionIcon('heroicon-m-users'),
            Stat::make('Low stock', $lowStock)
                ->description('Active products with 3 or fewer left')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStock > 0 ? 'danger' : 'gray'),
        ]);
    }
}
