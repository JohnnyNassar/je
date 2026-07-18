<?php

namespace App\Filament\Resources\BazaarBookingResource\Pages;

use App\Filament\Resources\BazaarBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBazaarBooking extends EditRecord
{
    protected static string $resource = BazaarBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
