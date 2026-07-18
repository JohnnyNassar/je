{{--
    Generated floor plan. Geometry comes from bazaar_tables (seeded from the
    architect's drawing), so the map and the booking data can never disagree.

    Expects: $tables (all active), $takenIds (ids booked on the selected night).
    Alpine state `selected` / `selectedNumber` is owned by the parent page.
--}}
<div class="relative w-full overflow-x-auto">
    <svg viewBox="160 40 700 530"
         class="w-full min-w-[38rem] h-auto select-none"
         role="img"
         aria-label="{{ __('Bazaar floor plan') }}">

        {{-- Venue outline --}}
        <rect x="170" y="50" width="680" height="510" rx="8"
              fill="#f8fafc" stroke="#e2e8f0" stroke-width="2" />

        <text x="510" y="66" text-anchor="middle" font-size="11"
              fill="#94a3b8" font-weight="600">{{ __('RESTAURANTS') }}</text>

        @foreach ($tables as $t)
            @php
                $isTaken = in_array($t->id, $takenIds, true);
                $isBookable = $t->is_bookable && ! $isTaken;
                $cx = $t->pos_x + ($t->width / 2);
                $cy = $t->pos_y + ($t->height / 2);
                $transform = $t->rotation ? "rotate({$t->rotation} {$cx} {$cy})" : null;
            @endphp

            <g @if ($transform) transform="{{ $transform }}" @endif
               @if ($isBookable)
                   role="button"
                   tabindex="0"
                   class="cursor-pointer"
                   x-on:click="select({{ $t->id }}, {{ $t->number }})"
                   x-on:keydown.enter.prevent="select({{ $t->id }}, {{ $t->number }})"
                   x-on:keydown.space.prevent="select({{ $t->id }}, {{ $t->number }})"
               @endif
               aria-label="{{ $t->is_bookable
                    ? ($isTaken
                        ? __('Table :n — already booked', ['n' => $t->number])
                        : __('Table :n — available', ['n' => $t->number]))
                    : __('Restaurant unit :n', ['n' => $t->number]) }}">

                <rect x="{{ $t->pos_x }}" y="{{ $t->pos_y }}"
                      width="{{ $t->width }}" height="{{ $t->height }}" rx="3"
                      @if ($isBookable)
                          fill="{{ $t->colour }}"
                          x-bind:stroke="selected === {{ $t->id }} ? '#0f4248' : '#ffffff'"
                          x-bind:stroke-width="selected === {{ $t->id }} ? 3 : 1"
                          class="transition-opacity hover:opacity-75"
                      @elseif ($isTaken)
                          fill="#cbd5e1" stroke="#ffffff" stroke-width="1"
                      @else
                          fill="{{ $t->colour }}" fill-opacity="0.45"
                          stroke="#ffffff" stroke-width="1"
                      @endif />

                <text x="{{ $cx }}" y="{{ $cy + 3.5 }}"
                      text-anchor="middle" font-size="9"
                      font-weight="{{ $isBookable ? '600' : '400' }}"
                      fill="{{ $isTaken ? '#64748b' : '#ffffff' }}"
                      class="pointer-events-none">{{ $t->number }}</text>
            </g>
        @endforeach
    </svg>
</div>

{{-- Legend --}}
<div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-gray-600">
    @foreach (\App\Models\BazaarTable::SECTIONS as $key => $label)
        @continue($key === \App\Models\BazaarTable::SECTION_RESTAURANT)
        <span class="inline-flex items-center gap-1.5">
            <span class="w-3 h-3 rounded-sm"
                  style="background: {{ \App\Models\BazaarTable::SECTION_COLOURS[$key] }}"></span>
            {{ __($label) }}
        </span>
    @endforeach
    <span class="inline-flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-sm bg-slate-300"></span>{{ __('Already booked') }}
    </span>
    <span class="inline-flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-sm" style="background: #b57edc; opacity: .45"></span>{{ __('Restaurants (not for rent)') }}
    </span>
</div>
