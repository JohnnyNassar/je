@props(['active' => 'cart'])
@php
    $steps = [
        ['key' => 'cart',         'label' => __('Cart')],
        ['key' => 'shipping',     'label' => __('Shipping Information')],
        ['key' => 'confirmation', 'label' => __('Order placed successfully')],
    ];
    $activeIndex = collect($steps)->pluck('key')->search($active);
@endphp

<nav aria-label="Checkout progress" class="flex items-center justify-center flex-wrap lg:flex-nowrap gap-4 lg:gap-1.5 mb-10">
    @foreach ($steps as $i => $step)
        @php
            $done    = $i < $activeIndex;
            $current = $i === $activeIndex;
        @endphp
        <div class="relative inline-flex items-center gap-2 h-9 px-4 rounded-full border text-sm
            @if ($current) border-brand-300 bg-brand-50 text-brand-700 font-medium
            @elseif ($done) border-gray-200 text-gray-600
            @else border-gray-200 text-gray-400
            @endif">
            @if ($done)
                <svg class="w-5 h-5 text-green-500 absolute -top-1.5 -end-1.5 bg-white rounded-full" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            @endif
            <span class="text-xs sm:text-sm">{{ $step['label'] }}</span>
        </div>
        @if ($i < count($steps) - 1)
            <div class="hidden lg:block w-10 h-px border-t border-dashed border-gray-300"></div>
        @endif
    @endforeach
</nav>
