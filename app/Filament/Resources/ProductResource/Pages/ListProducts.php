<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    /** Shown right under the "Products" page title, so the total is unmissable. */
    public function getSubheading(): ?string
    {
        $total = Product::count();
        $active = Product::where('is_active', true)->count();

        return number_format($total) . ' products total · ' . number_format($active) . ' active';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
