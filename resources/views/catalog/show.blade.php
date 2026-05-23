<x-layouts.shop>
    <nav class="text-xs text-gray-500 mb-5" aria-label="Breadcrumb">
        <ol class="inline-flex items-center gap-1.5">
            <li><a href="{{ route('catalog.index') }}" class="hover:text-brand-600">{{ __('Catalog') }}</a></li>
            <li class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-gray-400 rtl:rotate-180" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                </svg>
                <span class="text-gray-700 line-clamp-1 max-w-xs">{{ $product->name }}</span>
            </li>
        </ol>
    </nav>

    @if ($product->variants->isNotEmpty())
        @php
            $variantData = $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'stock' => (int) $v->stock,
                'price' => money_format($v->effectivePrice()),
                'image' => $v->image_path ? asset('storage/' . $v->image_path) : null,
            ])->values();
            $firstInStock = $product->variants->firstWhere('stock', '>', 0) ?? $product->variants->first();
            $productImage = $product->image_path ? asset('storage/' . $product->image_path) : '';
        @endphp
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden"
             x-data="{
                variants: @js($variantData),
                productImage: @js($productImage),
                selectedId: {{ $firstInStock?->id ?? 'null' }},
                qty: 1,
                get current() { return this.variants.find(v => v.id === this.selectedId) || null; },
                get max() { return this.current ? this.current.stock : 0; },
                get image() { return (this.current && this.current.image) ? this.current.image : this.productImage; },
             }"
             x-init="$watch('selectedId', () => { qty = 1; })">
            <div class="grid grid-cols-1 md:grid-cols-2">
                <div class="relative aspect-square md:aspect-auto bg-gray-100 overflow-hidden">
                    <template x-if="image">
                        <img :src="image" :alt="current ? current.name : @js($product->name)" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!image">
                        <div class="w-full h-full min-h-[20rem] flex items-center justify-center text-gray-300">
                            <svg class="w-20 h-20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/>
                            </svg>
                        </div>
                    </template>
                    @if ($product->isOnSale())
                        <span class="absolute top-3 start-3 inline-flex items-center rounded-md bg-accent-600 text-white px-2.5 py-1 text-xs font-bold uppercase tracking-wider shadow">
                            {{ __('Save') }} {{ $product->discount_percentage }}%
                        </span>
                    @endif
                </div>

                <div class="p-6 sm:p-8 flex flex-col">
                    <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 leading-tight">{{ $product->name }}</h1>

                    <div class="mt-4 flex items-center gap-3 flex-wrap">
                        <span class="text-2xl sm:text-3xl font-bold text-gray-900" x-text="current ? current.price : '{{ money_format($product->price) }}'"></span>
                        @if ($product->isOnSale())
                            <span class="text-lg text-gray-400 line-through">{{ money_format($product->compare_at_price) }}</span>
                        @endif
                        <template x-if="current && current.stock > 0">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 text-green-700 px-2.5 py-0.5 text-xs font-medium ring-1 ring-green-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                <span x-text="current.stock"></span> {{ __('left') }}
                            </span>
                        </template>
                        <template x-if="!current || current.stock <= 0">
                            <span class="inline-flex items-center rounded-full bg-red-50 text-red-700 px-2.5 py-0.5 text-xs font-medium ring-1 ring-red-200">
                                {{ __('Out of Stock') }}
                            </span>
                        </template>
                    </div>

                    @if ($product->description)
                        <div class="mt-5 text-sm text-gray-700 leading-relaxed whitespace-pre-line">
                            {{ $product->description }}
                        </div>
                    @endif

                    {{-- Variant chooser --}}
                    <div class="mt-6">
                        <div class="text-xs font-medium text-gray-700 mb-2">{{ __('Options') }}</div>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="v in variants" :key="v.id">
                                <button type="button"
                                        @click="if (v.stock > 0) selectedId = v.id"
                                        :disabled="v.stock <= 0"
                                        :class="{
                                            'ring-2 ring-brand-600 border-brand-600 text-brand-700': selectedId === v.id,
                                            'border-gray-300 text-gray-700 hover:border-gray-400': selectedId !== v.id && v.stock > 0,
                                            'border-gray-200 text-gray-300 line-through cursor-not-allowed': v.stock <= 0
                                        }"
                                        class="px-3 py-1.5 rounded-md border text-sm font-medium transition"
                                        x-text="v.name"></button>
                            </template>
                        </div>
                    </div>

                    <div class="mt-6 inline-flex items-center gap-2 rounded-md bg-gray-100 px-3 py-2 text-xs text-gray-700 w-fit">
                        <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/>
                        </svg>
                        <span class="font-medium">{{ __('Payment Method') }}:</span>
                        <span>{{ __('Cash on Delivery') }}</span>
                    </div>

                    <form method="POST" action="{{ route('cart.add', $product) }}" class="mt-auto pt-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        @csrf
                        <input type="hidden" name="variant_id" :value="selectedId">
                        <div class="inline-flex items-stretch rounded-md border border-gray-300 overflow-hidden bg-white shrink-0">
                            <button type="button" @click="qty = Math.max(1, qty - 1)" aria-label="Decrease"
                                    class="w-10 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                            </button>
                            <input type="number" name="quantity" x-model.number="qty" :max="max" min="1"
                                   class="w-14 text-center border-0 focus:ring-0 text-sm font-medium p-0">
                            <button type="button" @click="qty = Math.min(max, qty + 1)" aria-label="Increase"
                                    class="w-10 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                        <button type="submit"
                                :disabled="!current || current.stock <= 0"
                                :class="(!current || current.stock <= 0) ? 'opacity-50 cursor-not-allowed' : ''"
                                class="flex-1 inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold px-6 py-3 rounded-md transition shadow-card">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            {{ __('Add to Cart') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @else
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2">
            <div class="relative aspect-square md:aspect-auto bg-gray-100 overflow-hidden">
                @if ($product->image_path)
                    <img src="{{ asset('storage/' . $product->image_path) }}"
                         alt="{{ $product->name }}"
                         class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full min-h-[20rem] flex items-center justify-center text-gray-300">
                        <svg class="w-20 h-20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159"/>
                        </svg>
                    </div>
                @endif
                @if ($product->isOnSale())
                    <span class="absolute top-3 start-3 inline-flex items-center rounded-md bg-accent-600 text-white px-2.5 py-1 text-xs font-bold uppercase tracking-wider shadow">
                        {{ __('Save') }} {{ $product->discount_percentage }}%
                    </span>
                @endif
                @if ($product->stock > 0 && $product->stock <= 3)
                    <span class="absolute top-3 end-3 inline-flex items-center rounded-md bg-amber-100 text-amber-700 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider">
                        {{ $product->stock }} {{ __('left') }}
                    </span>
                @endif
            </div>

            <div class="p-6 sm:p-8 flex flex-col">
                <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 leading-tight">{{ $product->name }}</h1>

                <div class="mt-4 flex items-center gap-3 flex-wrap">
                    <span class="text-2xl sm:text-3xl font-bold text-gray-900">{{ money_format($product->price) }}</span>
                    @if ($product->isOnSale())
                        <span class="text-lg text-gray-400 line-through">{{ money_format($product->compare_at_price) }}</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-accent-100 text-accent-700 px-2.5 py-0.5 text-xs font-bold ring-1 ring-accent-200">
                            {{ __('Save') }} {{ $product->discount_percentage }}%
                        </span>
                    @endif
                    @if ($product->stock > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 text-green-700 px-2.5 py-0.5 text-xs font-medium ring-1 ring-green-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                            {{ $product->stock }} {{ __('left') }}
                        </span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-red-50 text-red-700 px-2.5 py-0.5 text-xs font-medium ring-1 ring-red-200">
                            {{ __('Out of Stock') }}
                        </span>
                    @endif
                </div>

                @if ($product->description)
                    <div class="mt-5 text-sm text-gray-700 leading-relaxed whitespace-pre-line">
                        {{ $product->description }}
                    </div>
                @endif

                <div class="mt-6 inline-flex items-center gap-2 rounded-md bg-gray-100 px-3 py-2 text-xs text-gray-700 w-fit">
                    <svg class="w-4 h-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/>
                    </svg>
                    <span class="font-medium">{{ __('Payment Method') }}:</span>
                    <span>{{ __('Cash on Delivery') }}</span>
                </div>

                @if ($product->stock > 0)
                    <form method="POST" action="{{ route('cart.add', $product) }}" class="mt-auto pt-6 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        @csrf
                        <div x-data="{ qty: 1, max: {{ $product->stock }} }" class="inline-flex items-stretch rounded-md border border-gray-300 overflow-hidden bg-white shrink-0">
                            <button type="button" @click="qty = Math.max(1, qty - 1)" aria-label="Decrease"
                                    class="w-10 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                            </button>
                            <input type="number" name="quantity" x-model.number="qty" :max="max" min="1"
                                   class="w-14 text-center border-0 focus:ring-0 text-sm font-medium p-0">
                            <button type="button" @click="qty = Math.min(max, qty + 1)" aria-label="Increase"
                                    class="w-10 flex items-center justify-center text-gray-600 hover:bg-gray-100">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            </button>
                        </div>
                        <button type="submit"
                                class="flex-1 inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold px-6 py-3 rounded-md transition shadow-card">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            {{ __('Add to Cart') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Related products: same-category siblings --}}
    @if (($related ?? collect())->isNotEmpty())
        <section class="mt-10">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('You may also like') }}</h2>
            <div class="flex gap-4 overflow-x-auto pb-3 -mx-1 px-1 snap-x">
                @foreach ($related as $item)
                    <a href="{{ route('catalog.show', $item) }}"
                       class="group snap-start shrink-0 w-44 sm:w-52 bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-card-hover hover:border-gray-300 transition">
                        <div class="relative aspect-square bg-gray-100 overflow-hidden">
                            @if ($item->image_path)
                                <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->name }}" loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @endif
                            @if ($item->isOnSale())
                                <span class="absolute top-2 start-2 inline-flex items-center rounded-md bg-accent-600 text-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider shadow">
                                    {{ __('Save') }} {{ $item->discount_percentage }}%
                                </span>
                            @endif
                            @if ($item->stock <= 0)
                                <span class="absolute top-2 end-2 inline-flex items-center rounded-md bg-red-100 text-red-700 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider">
                                    {{ __('Out of Stock') }}
                                </span>
                            @endif
                        </div>
                        <div class="p-3">
                            <h3 class="text-sm font-medium text-gray-900 line-clamp-2 leading-snug min-h-[2.5rem] group-hover:text-brand-700">{{ $item->name }}</h3>
                            <div class="mt-2 flex items-end gap-2">
                                <span class="text-base font-semibold text-gray-900">{{ money_format($item->price) }}</span>
                                @if ($item->isOnSale())
                                    <span class="text-xs text-gray-400 line-through">{{ money_format($item->compare_at_price) }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.shop>
