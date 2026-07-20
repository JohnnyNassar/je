<?php

namespace App\Filament\Resources\BazaarPeriodResource\Pages;

use App\Filament\Resources\BazaarPeriodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBazaarPeriod extends EditRecord
{
    protected static string $resource = BazaarPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
