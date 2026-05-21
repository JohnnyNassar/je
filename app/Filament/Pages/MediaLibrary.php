<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Setting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\WithPagination;

class MediaLibrary extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Media';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.media-library';

    public string $search = '';

    public string $filter = 'all';   // all | used | orphan

    public string $sort = 'newest';  // newest | oldest | largest | smallest | name

    protected $queryString = [
        'search' => ['except' => ''],
        'filter' => ['except' => 'all'],
        'sort'   => ['except' => 'newest'],
    ];

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilter(): void { $this->resetPage(); }
    public function updatedSort():   void { $this->resetPage(); }

    public function deleteFile(string $relative): void
    {
        $relative = ltrim($relative, '/');
        // Whitelist: only files inside products/ or hero/ under public disk
        if (! preg_match('#^(products|hero)/#', $relative)) {
            Notification::make()->title('Refused: file outside allowed folders')->danger()->send();
            return;
        }

        $abs = storage_path('app/public/' . $relative);
        if (! file_exists($abs)) {
            Notification::make()->title('File not found')->danger()->send();
            return;
        }

        // Block delete if in use
        $inUse = Product::where('image_path', $relative)->exists()
            || Setting::get('hero_image_path') === $relative;
        if ($inUse) {
            Notification::make()->title('File is in use — cannot delete')->warning()->send();
            return;
        }

        @unlink($abs);
        Notification::make()->title('Deleted ' . basename($relative))->success()->send();
    }

    public function getImagesProperty(): LengthAwarePaginator
    {
        $files = $this->scanFiles();

        // Build usage map
        $usage = $this->usageMap($files->pluck('path')->all());

        $heroPath = Setting::get('hero_image_path');

        $files = $files->map(function (array $f) use ($usage, $heroPath) {
            $count = $usage[$f['path']] ?? 0;
            if ($heroPath && $heroPath === $f['path']) {
                $count++;
            }
            $f['used_by'] = $count;
            return $f;
        });

        // Search filter
        if (trim($this->search) !== '') {
            $needle = mb_strtolower(trim($this->search));
            $files = $files->filter(fn ($f) => str_contains(mb_strtolower(basename($f['path'])), $needle));
        }

        // Used/orphan filter
        $files = match ($this->filter) {
            'used'   => $files->filter(fn ($f) => $f['used_by'] > 0),
            'orphan' => $files->filter(fn ($f) => $f['used_by'] === 0),
            default  => $files,
        };

        // Sort
        $files = match ($this->sort) {
            'oldest'   => $files->sortBy('mtime')->values(),
            'largest'  => $files->sortByDesc('size')->values(),
            'smallest' => $files->sortBy('size')->values(),
            'name'     => $files->sortBy(fn ($f) => basename($f['path']))->values(),
            default    => $files->sortByDesc('mtime')->values(),
        };

        $perPage = 48;
        $page = (int) request('page', 1);
        $items = $files->forPage($page, $perPage);

        return new LengthAwarePaginator(
            $items,
            $files->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function getTotalsProperty(): array
    {
        $files = $this->scanFiles();
        $totalBytes = $files->sum('size');
        $usage = $this->usageMap($files->pluck('path')->all());
        $heroPath = Setting::get('hero_image_path');
        $orphans = $files->filter(function ($f) use ($usage, $heroPath) {
            return ! (($usage[$f['path']] ?? 0) > 0 || ($heroPath && $heroPath === $f['path']));
        })->count();
        return [
            'count' => $files->count(),
            'bytes' => $totalBytes,
            'orphans' => $orphans,
        ];
    }

    private function scanFiles(): Collection
    {
        $root = storage_path('app/public');
        $files = collect();

        foreach (['products', 'hero'] as $dir) {
            $abs = $root . DIRECTORY_SEPARATOR . $dir;
            if (! is_dir($abs)) continue;
            foreach (scandir($abs) ?: [] as $f) {
                if ($f === '.' || $f === '..') continue;
                $full = $abs . DIRECTORY_SEPARATOR . $f;
                if (! is_file($full)) continue;
                if (! preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $f)) continue;
                $files->push([
                    'path' => $dir . '/' . $f,
                    'name' => $f,
                    'size' => filesize($full),
                    'mtime' => filemtime($full),
                ]);
            }
        }

        return $files;
    }

    private function usageMap(array $paths): array
    {
        if (empty($paths)) return [];
        return Product::whereIn('image_path', $paths)
            ->selectRaw('image_path, COUNT(*) as c')
            ->groupBy('image_path')
            ->pluck('c', 'image_path')
            ->toArray();
    }

    protected function getViewData(): array
    {
        return [
            'images' => $this->images,
            'totals' => $this->totals,
        ];
    }
}
