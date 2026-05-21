<x-filament-panels::page>
    @php
        $totals = $totals ?? ['count' => 0, 'bytes' => 0, 'orphans' => 0];
        $formatBytes = function ($bytes) {
            if ($bytes < 1024) return $bytes . ' B';
            if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1) . ' KB';
            return number_format($bytes / 1024 / 1024, 2) . ' MB';
        };
    @endphp

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-5">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 px-5 py-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider">Files</div>
            <div class="text-2xl font-bold mt-1">{{ number_format($totals['count']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 px-5 py-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider">Disk usage</div>
            <div class="text-2xl font-bold mt-1">{{ $formatBytes($totals['bytes']) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 px-5 py-4">
            <div class="text-xs text-gray-500 uppercase tracking-wider">Orphans</div>
            <div class="text-2xl font-bold mt-1 {{ $totals['orphans'] > 0 ? 'text-amber-600' : '' }}">{{ number_format($totals['orphans']) }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-5">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[180px]">
                <svg class="absolute top-1/2 -translate-y-1/2 start-3 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="Search by filename..."
                       class="w-full ps-10 pe-3 py-2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm focus:ring-primary-500 focus:border-primary-500">
            </div>
            <select wire:model.live="filter"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                <option value="all">All files</option>
                <option value="used">Used by a product</option>
                <option value="orphan">Orphans only</option>
            </select>
            <select wire:model.live="sort"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                <option value="newest">Newest first</option>
                <option value="oldest">Oldest first</option>
                <option value="largest">Largest first</option>
                <option value="smallest">Smallest first</option>
                <option value="name">By name (A→Z)</option>
            </select>
        </div>
    </div>

    {{-- Grid --}}
    @if ($images->total() === 0)
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center text-gray-500">
            No images found.
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
            @foreach ($images as $img)
                @php
                    $url = asset('storage/' . $img['path']);
                    $isOrphan = $img['used_by'] === 0;
                @endphp
                <div class="group relative bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition" x-data="{ open: false }">
                    <div class="relative aspect-square bg-gray-100 dark:bg-gray-900">
                        <img src="{{ $url }}" alt="{{ $img['name'] }}" loading="lazy" class="w-full h-full object-cover">
                        @if ($isOrphan)
                            <span class="absolute top-1.5 start-1.5 inline-flex items-center rounded bg-amber-500 text-white px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider">orphan</span>
                        @else
                            <span class="absolute top-1.5 start-1.5 inline-flex items-center rounded bg-emerald-600 text-white px-1.5 py-0.5 text-[10px] font-bold">{{ $img['used_by'] }}× used</span>
                        @endif
                        <button type="button" @click="open = true"
                                class="absolute inset-0 w-full h-full opacity-0 group-hover:opacity-100 bg-black/30 backdrop-blur-sm flex items-center justify-center text-white text-xs font-semibold transition">
                            View
                        </button>
                    </div>
                    <div class="p-2">
                        <div class="text-[11px] font-medium text-gray-900 dark:text-gray-100 truncate" title="{{ $img['name'] }}">{{ $img['name'] }}</div>
                        <div class="text-[10px] text-gray-500 mt-0.5">{{ $formatBytes($img['size']) }}</div>
                    </div>

                    {{-- Modal --}}
                    <div x-show="open" x-cloak
                         x-transition.opacity
                         @keydown.escape.window="open = false"
                         @click.self="open = false"
                         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
                        <div class="bg-white dark:bg-gray-900 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-hidden shadow-2xl flex flex-col">
                            <div class="px-5 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <h3 class="font-semibold text-sm truncate">{{ $img['name'] }}</h3>
                                <button @click="open = false" class="text-gray-500 hover:text-gray-900 dark:hover:text-white text-2xl leading-none">&times;</button>
                            </div>
                            <div class="flex-1 overflow-auto bg-gray-100 dark:bg-black flex items-center justify-center">
                                <img src="{{ $url }}" alt="" class="max-w-full max-h-[60vh] object-contain">
                            </div>
                            <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700 text-xs space-y-1.5">
                                <div><span class="text-gray-500">Path:</span> <code>{{ $img['path'] }}</code></div>
                                <div><span class="text-gray-500">Size:</span> {{ $formatBytes($img['size']) }}</div>
                                <div><span class="text-gray-500">Used by:</span> {{ $img['used_by'] }} product(s)</div>
                            </div>
                            <div class="px-5 py-3 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-2 justify-end">
                                <button type="button"
                                        @click="navigator.clipboard.writeText('{{ $url }}'); $el.innerText='Copied!'; setTimeout(() => $el.innerText='Copy URL', 1500)"
                                        class="text-xs px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200">Copy URL</button>
                                <a href="{{ $url }}" target="_blank"
                                   class="text-xs px-3 py-2 rounded-md bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200">Open full</a>
                                @if ($isOrphan)
                                    <button type="button"
                                            wire:click="deleteFile('{{ $img['path'] }}')"
                                            wire:confirm="Delete this file permanently? It's not used by any product."
                                            @click="open = false"
                                            class="text-xs px-3 py-2 rounded-md bg-red-600 hover:bg-red-700 text-white">Delete orphan</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-5">
            {{ $images->links() }}
        </div>
    @endif
</x-filament-panels::page>
