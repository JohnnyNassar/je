<?php

namespace App\Filament\Resources\BazaarNightResource\Pages;

use App\Filament\Resources\BazaarNightResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBazaarNights extends ListRecords
{
    protected static string $resource = BazaarNightResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
