<?php

namespace App\Filament\Resources\BazaarTableResource\Pages;

use App\Filament\Resources\BazaarTableResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBazaarTable extends EditRecord
{
    protected static string $resource = BazaarTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
