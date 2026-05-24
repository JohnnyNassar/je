<?php

namespace App\Filament\Resources\LoyaltyPromotionResource\Pages;

use App\Filament\Resources\LoyaltyPromotionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoyaltyPromotion extends EditRecord
{
    protected static string $resource = LoyaltyPromotionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
