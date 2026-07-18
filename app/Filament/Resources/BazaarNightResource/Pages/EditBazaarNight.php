<?php

namespace App\Filament\Resources\BazaarNightResource\Pages;

use App\Filament\Resources\BazaarNightResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBazaarNight extends EditRecord
{
    protected static string $resource = BazaarNightResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
