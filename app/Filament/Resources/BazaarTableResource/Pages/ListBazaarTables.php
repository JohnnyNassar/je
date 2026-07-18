<?php

namespace App\Filament\Resources\BazaarTableResource\Pages;

use App\Filament\Resources\BazaarTableResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBazaarTables extends ListRecords
{
    protected static string $resource = BazaarTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
