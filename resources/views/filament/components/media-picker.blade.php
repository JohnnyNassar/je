@php
    $statePath = $statePath ?? 'data.image_path';
    $dirs = $dirs ?? ['products', 'hero'];
    $limit = $limit ?? 200;

    $rootPath = storage_path('app/public');
    $files = collect();
    foreach ($dirs as $dir) {
        $abs = $rootPath . DIRECTORY_SEPARATOR . $dir;
        if (! is_dir($abs)) continue;
        foreach (scandir($abs) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            $full = $abs . DIRECTORY_SEPARATOR . $f;
            if (! is_file($full)) continue;
            if (! preg_match('/\.(jpe?g|png|gif|webp)$/i', $f)) continue;
            $files->push([
                'path'  => $dir . '/' . $f,
                'name'  => $f,
                'size'  => filesize($full),
                'mtime' => filemtime($full),
            ]);
        }
    }
    $files = $files->sortByDesc('mtime')->values()->take($limit);
@endphp

<div x-data="mediaPicker({{ json_encode($statePath) }})" class="space-y-3">
    {{-- Search --}}
    <div class="relative">
        <svg class="absolute top-1/2 -translate-y-1/2 start-3 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" x-model="q" placeholder="Filter by filename..."
               class="w-full ps-10 pe-3 py-2 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
    </div>

    <div x-show="picked" x-cloak class="rounded bg-green-50 border border-green-200 text-green-800 px-3 py-2 text-xs">
        ✓ Picked: <code x-text="picked"></code>
    </div>

    @if ($files->isEmpty())
        <div class="text-center text-sm text-gray-500 py-8">
            No images found in <code>storage/app/public/{{ implode(', ', $dirs) }}</code>.
        </div>
    @else
        <div class="text-xs text-gray-500 px-1">
            {{ $files->count() }} images. Click any to pick.
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;max-height:60vh;overflow-y:auto;padding-inline-end:4px;">
            @foreach ($files as $f)
                @php
                    $nameLower = strtolower($f['name']);
                    $pathJson = htmlspecialchars(json_encode($f['path']), ENT_QUOTES);
                    $nameJson = htmlspecialchars(json_encode($nameLower), ENT_QUOTES);
                @endphp
                <button
                    type="button"
                    x-show="q === '' || {{ json_encode($nameLower) }}.includes(q.toLowerCase())"
                    x-on:click="pickImage($el, {{ json_encode($f['path']) }})"
                    style="position:relative;aspect-ratio:1/1;background:#f3f4f6;border-radius:6px;overflow:hidden;border:1px solid #e5e7eb;cursor:pointer;padding:0;"
                    onmouseover="this.style.borderColor='#287d88';this.style.boxShadow='0 0 0 2px #287d88';"
                    onmouseout="this.style.borderColor='#e5e7eb';this.style.boxShadow='none';"
                    title="{{ $f['name'] }} · {{ number_format($f['size'] / 1024, 0) }} KB"
                >
                    <img src="{{ asset('storage/' . $f['path']) }}" alt=""
                         loading="lazy"
                         style="width:100%;height:100%;object-fit:cover;display:block;">
                    <div style="position:absolute;inset-inline:0;bottom:0;background:linear-gradient(to top, rgba(0,0,0,0.7), transparent);padding:4px 6px;">
                        <div style="font-size:10px;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $f['name'] }}</div>
                    </div>
                </button>
            @endforeach
        </div>
    @endif
</div>
