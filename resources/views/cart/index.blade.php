<x-layouts.shop>
    <x-checkout-steps active="cart" />

    <h1 class="text-xl font-semibold text-gray-900 mb-5">{{ __('Cart') }}</h1>

    @if ($items->isEmpty())
        <div class="bg-white border border-gray-200 rounded-xl p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-gray-600 text-sm mb-4">{{ __('Your cart is empty') }}</p>
            <a href="{{ route('catalog.index') }}"
               class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-medium px-5 py-2.5 rounded-md transition">
                {{ __('Continue Shopping') }}
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl overflow-hidden">
                <ul class="divide-y divide-gray-100">
                    @foreach ($items as $item)
                        @php($product = $item['product'])
                        <li class="p-4 flex items-center gap-4">
                            <a href="{{ route('catalog.show', $product) }}" class="shrink-0 w-[90px] h-[70px] rounded-md overflow-hidden bg-gray-100 flex items-center justify-center">
                                @if ($product->image_path)
                                    <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                @endif
                            </a>
                            <div class="flex-1 min-w-0">
                                <a href="{{ route('catalog.show', $product) }}" class="block text-sm font-medium text-gray-900 hover:text-brand-600 line-clamp-2 leading-snug">
                                    {{ $product->name }}
                                </a>
                                <div class="mt-1 text-xs text-gray-500">{{ money_format($item['unit_price']) }} <span class="text-gray-400">/ {{ __('each') }}</span></div>
                            </div>

                            <form method="POST" action="{{ route('cart.update', $product) }}"
                                  x-data="{ qty: {{ $item['quantity'] }}, max: {{ $product->stock }} }"
                                  class="inline-flex items-stretch rounded-md border border-gray-300 overflow-hidden">
                                @csrf
                                @method('PATCH')
                                <button type="button" @click="qty = Math.max(0, qty - 1); $el.closest('form').submit()" aria-label="Decrease"
                                        class="w-8 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                                </button>
                                <input type="number" name="quantity" x-model.number="qty" :max="max" min="0"
                                       @change="$el.closest('form').submit()"
                                       class="w-12 text-center border-0 focus:ring-0 text-sm font-medium p-0">
                                <button type="button" @click="qty = Math.min(max, qty + 1); $el.closest('form').submit()" aria-label="Increase"
                                        class="w-8 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </form>

                            <div class="hidden sm:block w-24 text-end text-sm font-semibold text-gray-900">{{ money_format($item['line_total']) }}</div>

                            <form method="POST" action="{{ route('cart.remove', $product) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" aria-label="{{ __('Remove') }}"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a2 2 0 012-2h2a2 2 0 012 2v3"/>
                                    </svg>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
                <div class="p-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                    <form method="POST" action="{{ route('cart.clear') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-red-600">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            {{ __('Clear cart') }}
                        </button>
                    </form>
                    <a href="{{ route('catalog.index') }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">
                        ← {{ __('Continue Shopping') }}
                    </a>
                </div>
            </div>

            <aside class="bg-white border border-gray-200 rounded-xl p-5 h-fit lg:sticky lg:top-20">
                <h2 class="text-base font-semibold text-gray-900 mb-4">{{ __('Order Summary') }}</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between text-gray-600">
                        <dt>{{ __('Subtotal') }}</dt>
                        <dd class="text-gray-900">{{ money_format($total) }}</dd>
                    </div>
                    <div class="flex items-center justify-between text-gray-600">
                        <dt>{{ __('Payment Method') }}</dt>
                        <dd class="text-gray-900">{{ __('Cash on Delivery') }}</dd>
                    </div>
                </dl>
                <div class="border-t border-gray-100 mt-4 pt-4 flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-900">{{ __('Total') }}</span>
                    <span class="text-xl font-bold text-gray-900">{{ money_format($total) }}</span>
                </div>
                <a href="{{ route('checkout.show') }}"
                   class="mt-5 w-full inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold px-6 py-3 rounded-md transition shadow-card">
                    {{ __('Checkout') }}
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
            </aside>
        </div>
    @endif
</x-layouts.shop>
