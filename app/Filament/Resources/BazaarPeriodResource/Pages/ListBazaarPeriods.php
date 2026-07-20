<?php

namespace App\Filament\Resources\BazaarPeriodResource\Pages;

use App\Filament\Resources\BazaarPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBazaarPeriods extends ListRecords
{
    protected static string $resource = BazaarPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
