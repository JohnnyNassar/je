<x-layouts.shop>
    @php
        $adminPhone = \App\Models\Setting::get('admin_whatsapp');
        $waMessage = "Order #{$order->id} — " . __('Total') . ': ' . money_format($order->total);
        $waLink = $adminPhone
            ? "https://wa.me/{$adminPhone}?text=" . rawurlencode($waMessage)
            : null;
    @endphp

    <x-checkout-steps active="confirmation" />

    <div class="max-w-3xl mx-auto">
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            {{-- Header --}}
            <div class="px-5 sm:px-7 py-6 border-b border-gray-100">
                <div class="flex items-center gap-3 mb-4">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-600">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </span>
                    <div>
                        <h1 class="text-lg sm:text-xl font-semibold text-gray-900">{{ __('Order placed successfully') }}</h1>
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('Order') }} #{{ $order->id }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="flex flex-col gap-1">
                        <span class="text-xs text-gray-500">{{ __('Order') }}</span>
                        <span class="text-sm font-medium text-gray-900">#{{ $order->id }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-xs text-gray-500">{{ __('Total') }}</span>
                        <span class="text-sm font-medium text-gray-900">{{ money_format($order->total) }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-xs text-gray-500">{{ __('Payment Method') }}</span>
                        <span class="text-sm font-medium text-gray-900">{{ __('Cash on Delivery') }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-xs text-gray-500">{{ __('Items') }}</span>
                        <span class="text-sm font-medium text-gray-900">{{ $order->items->count() }}</span>
                    </div>
                </div>
            </div>

            {{-- Items --}}
            <ul class="divide-y divide-gray-100">
                @foreach ($order->items as $item)
                    <li class="px-5 sm:px-7 py-4 flex items-center gap-4">
                        <div class="w-[70px] h-[70px] shrink-0 rounded-md overflow-hidden bg-gray-100">
                            @if ($item->product && $item->product->image_path)
                                <img src="{{ storage_image_url($item->product->image_path) }}" alt="" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 line-clamp-2 leading-snug">{{ $item->product_name }}</div>
                            @if ($item->variant_name)
                                <div class="text-xs text-gray-600 mt-0.5">{{ $item->variant_name }}</div>
                            @endif
                            <div class="text-xs text-gray-500 mt-1">{{ money_format($item->unit_price) }} <span class="text-gray-400">/ {{ __('each') }}</span></div>
                        </div>
                        <div class="text-end">
                            <div class="text-xs text-gray-500">{{ $item->quantity }} ×</div>
                            <div class="text-sm font-semibold text-gray-900">{{ money_format($item->line_total) }}</div>
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- Total --}}
            <div class="px-5 sm:px-7 py-4 bg-gray-50 border-t border-gray-100 space-y-2">
                @if ($order->discount_total > 0)
                    <div class="flex items-center justify-between text-sm text-gray-600">
                        <span>{{ __('Subtotal') }}</span>
                        <span>{{ money_format($order->total + $order->discount_total) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm text-green-700">
                        <span>{{ __('Discount') }}@if ($order->coupon_code)<span class="text-[11px] text-green-600 ms-1">({{ $order->coupon_code }})</span>@endif</span>
                        <span>−{{ money_format($order->discount_total) }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between {{ $order->discount_total > 0 ? 'pt-2 border-t border-gray-200' : '' }}">
                    <span class="text-sm font-semibold text-gray-900">{{ __('Total') }}</span>
                    <span class="text-xl font-bold text-gray-900">{{ money_format($order->total) }}</span>
                </div>
            </div>

            {{-- Actions --}}
            <div class="px-5 sm:px-7 py-5 border-t border-gray-100 flex flex-col sm:flex-row gap-3">
                @if ($waLink)
                    <a href="{{ $waLink }}" target="_blank"
                       class="flex-1 inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#1DAB52] text-white font-semibold px-5 py-3 rounded-md transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.711.306 1.265.489 1.697.626.713.226 1.362.194 1.875.118.572-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                        {{ __('Contact admin on WhatsApp') }}
                    </a>
                @endif
                <a href="{{ route('catalog.index') }}"
                   class="flex-1 inline-flex items-center justify-center gap-2 bg-white hover:bg-gray-50 text-gray-700 font-semibold px-5 py-3 rounded-md border border-gray-300 transition">
                    {{ __('Continue Shopping') }}
                </a>
            </div>
            <div class="px-5 sm:px-7 pb-5 text-center text-xs text-gray-500">
                {{ __('Track this order anytime at') }} <a href="{{ route('track.show') }}" class="text-brand-600 hover:underline">/track</a>
                &middot; {{ __('Save this number: #') }}{{ $order->id }}
            </div>
        </div>
    </div>
</x-layouts.shop>
