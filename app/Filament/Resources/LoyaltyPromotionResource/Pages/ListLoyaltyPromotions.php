<?php

namespace App\Filament\Resources\LoyaltyPromotionResource\Pages;

use App\Filament\Resources\LoyaltyPromotionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoyaltyPromotions extends ListRecords
{
    protected static string $resource = LoyaltyPromotionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
