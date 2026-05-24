<?php

namespace App\Filament\Resources\LoyaltyTransactionResource\Pages;

use App\Filament\Resources\LoyaltyTransactionResource;
use App\Filament\Resources\LoyaltyTransactionResource\Widgets\LoyaltyPointsChart;
use App\Filament\Resources\LoyaltyTransactionResource\Widgets\LoyaltyStatsOverview;
use Filament\Resources\Pages\ListRecords;

class ListLoyaltyTransactions extends ListRecords
{
    protected static string $resource = LoyaltyTransactionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            LoyaltyStatsOverview::class,
            LoyaltyPointsChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 2;
    }
}
