<?php

namespace App\Filament\Resources\BazaarVendorCategoryResource\Pages;

use App\Filament\Resources\BazaarVendorCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBazaarVendorCategory extends EditRecord
{
    protected static string $resource = BazaarVendorCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
