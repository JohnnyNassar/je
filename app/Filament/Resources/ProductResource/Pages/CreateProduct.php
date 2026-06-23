<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use \App\Concerns\HandlesMediaPicking;

    protected static string $resource = ProductResource::class;

    /**
     * The gallery is the single source of truth for images. Mirror its first
     * image into image_path (the legacy cover column).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $gallery = array_values(array_filter((array) ($data['gallery'] ?? []), fn ($p) => filled($p)));
        $data['image_path'] = $gallery[0] ?? null;

        return $data;
    }
}
