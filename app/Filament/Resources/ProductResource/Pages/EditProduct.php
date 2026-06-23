<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use \App\Concerns\HandlesMediaPicking;

    protected static string $resource = ProductResource::class;

    /**
     * The gallery is the single source of truth for images. Mirror its first
     * image into image_path (the legacy cover column) so clearing the list
     * also clears the cover.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $gallery = array_values(array_filter((array) ($data['gallery'] ?? []), fn ($p) => filled($p)));
        $data['image_path'] = $gallery[0] ?? null;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('viewOnSite')
                ->label('View on website')
                ->icon('heroicon-m-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => route('catalog.show', $this->getRecord()))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
