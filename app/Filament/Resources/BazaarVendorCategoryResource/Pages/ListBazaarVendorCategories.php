<?php

namespace App\Filament\Resources\BazaarVendorCategoryResource\Pages;

use App\Filament\Resources\BazaarVendorCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBazaarVendorCategories extends ListRecords
{
    protected static string $resource = BazaarVendorCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
