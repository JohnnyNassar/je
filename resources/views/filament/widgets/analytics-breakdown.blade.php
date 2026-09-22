{{-- Top pages / sources / countries from GA4. Each list degrades to a line of
     text rather than an empty card when Google returns nothing. --}}
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Website traffic — last 28 days</x-slot>

        <x-slot name="description">
            From Google Analytics, cached for 15 minutes.
            <a href="{{ $propertyUrl }}" target="_blank" rel="noopener"
               class="text-primary-600 hover:underline dark:text-primary-400">Open the full report ↗</a>
        </x-slot>

        <div class="grid gap-6 md:grid-cols-3">
            @foreach ([
                ['title' => 'Most visited pages', 'rows' => $pages, 'unit' => 'views'],
                ['title' => 'Where visitors came from', 'rows' => $sources, 'unit' => 'sessions'],
                ['title' => 'Countries', 'rows' => $countries, 'unit' => 'visitors'],
            ] as $block)
                <div>
                    <h3 class="mb-3 text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $block['title'] }}
                    </h3>

                    @if (empty($block['rows']))
                        <p class="text-sm text-gray-500 dark:text-gray-400">No data yet.</p>
                    @else
                        @php($max = max(array_column($block['rows'], 'value')) ?: 1)
                        <ul class="space-y-2">
                            @foreach ($block['rows'] as $row)
                                <li>
                                    <div class="flex items-baseline justify-between gap-3 text-sm">
                                        <span class="truncate text-gray-700 dark:text-gray-300"
                                              title="{{ $row['label'] }}">{{ $row['label'] }}</span>
                                        <span class="shrink-0 font-medium tabular-nums text-gray-950 dark:text-white">
                                            {{ number_format($row['value']) }}
                                        </span>
                                    </div>
                                    {{-- A bar makes the shape of the list readable at a glance. --}}
                                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                        <div class="h-full rounded-full bg-primary-500"
                                             style="width: {{ max(2, round(($row['value'] / $max) * 100)) }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ $block['unit'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
