<?php

namespace App\Filament\Resources\BazaarBookingResource\Pages;

use App\Filament\Resources\BazaarBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBazaarBookings extends ListRecords
{
    protected static string $resource = BazaarBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
