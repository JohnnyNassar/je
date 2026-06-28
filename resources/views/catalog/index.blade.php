<x-layouts.shop>
    {{-- Hero / search banner --}}
    @php
        $heroImage = \App\Models\Setting::get('hero_image_path');
        if (! $heroImage) {
            $heroProductId = (int) \App\Models\Setting::get('hero_product_id');
            if ($heroProductId > 0) {
                $heroProduct = \App\Models\Product::find($heroProductId);
                if ($heroProduct && $heroProduct->image_path) {
                    $heroImage = $heroProduct->image_path;
                }
            }
        }
    @endphp
    <section class="relative overflow-hidden rounded-2xl mb-5 bg-brand-900 text-white shadow-card-hover
                    min-h-[10rem] sm:min-h-[12rem] lg:min-h-[14rem] flex">
        @if ($heroImage)
            {{-- Custom hero image with dark overlay for readability --}}
            <div class="absolute inset-0 bg-cover bg-center"
                 style="background-image: url('{{ storage_image_url($heroImage) }}');"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-brand-900/85 via-brand-900/60 to-brand-900/30 pointer-events-none"></div>
        @else
            {{-- Garage-door texture overlay (fallback when no hero image) --}}
            <div class="absolute inset-0 opacity-[0.08] pointer-events-none"
                 style="background-image: repeating-linear-gradient(0deg, rgba(255,255,255,1) 0 1px, transparent 1px 8px);"></div>
        @endif
        {{-- Soft red glow accent --}}
        <div class="absolute -top-24 -end-24 w-80 h-80 rounded-full bg-accent-600/20 blur-3xl pointer-events-none"></div>

        <div class="relative px-6 sm:px-10 lg:px-14 py-6 sm:py-7 lg:py-8 max-w-3xl flex flex-col justify-center">
            <div class="flex items-center gap-2 mb-5">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-accent-600 text-white px-3 py-1 text-xs font-bold uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                    {{ __('Deals') ?? 'Deals' }}
                </span>
                <span class="inline-flex items-center rounded-full bg-white/10 text-white/90 px-3 py-1 text-xs font-medium ring-1 ring-white/20">
                    {{ __('Cash on Delivery') }}
                </span>
            </div>
            <h1 class="text-3xl sm:text-5xl font-extrabold leading-[1.1] mb-3">
                {{ __('Joreption') }}
            </h1>
            <p class="text-white/80 text-sm sm:text-lg max-w-xl leading-relaxed mb-6">
                {{ __('Quality finds at garage-sale prices.') ?? 'Quality finds at garage-sale prices.' }}
            </p>
            <a href="#products"
               class="inline-flex items-center gap-2 bg-white hover:bg-gray-100 text-brand-900 font-semibold px-5 py-2.5 rounded-md transition shadow-card">
                {{ __('Browse Catalog') ?? 'Browse Catalog' }}
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </section>

    {{-- Featured strip --}}
    @if (($featured ?? collect())->isNotEmpty())
        <section class="mb-5">
            <div class="flex items-center gap-3 mb-4">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-brand-50 text-brand-600">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                </span>
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Featured') }}</h2>
            </div>
            <div class="flex gap-4 overflow-x-auto pb-3 -mx-1 px-1 snap-x">
                @foreach ($featured as $product)
                    <a href="{{ route('catalog.show', $product) }}"
                       class="group snap-start shrink-0 w-44 sm:w-52 bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-card-hover hover:border-gray-300 transition">
                        <div class="relative aspect-square bg-gray-100 overflow-hidden">
                            @if ($product->mainImageUrl())
                                <img src="{{ $product->mainImageUrl() }}" alt="{{ $product->name }}" loading="lazy"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @endif
                            @if ($product->isOnSale())
                                <span class="absolute top-2 start-2 inline-flex items-center rounded-md bg-accent-600 text-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider shadow">
                                    {{ __('Save') }} {{ $product->discount_percentage }}%
                                </span>
                            @endif
                        </div>
                        <div class="p-3">
                            <h3 class="text-sm font-medium text-gray-900 line-clamp-2 leading-snug min-h-[2.5rem] group-hover:text-brand-700">{{ $product->name }}</h3>
                            <div class="mt-2 flex items-end gap-2">
                                <span class="text-base font-semibold text-gray-900">{{ money_format($product->price) }}</span>
                                @if ($product->isOnSale())
                                    <span class="text-xs text-gray-400 line-through">{{ money_format($product->compare_at_price) }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Search + categories --}}
    <div id="products" class="mb-4 scroll-mt-20">
        <form method="GET" action="{{ route('catalog.index') }}" class="flex gap-2 mb-4">
            @if ($activeCategory ?? null)
                <input type="hidden" name="category" value="{{ $activeCategory->slug }}">
            @endif
            <div class="relative flex-1">
                <svg class="absolute top-1/2 -translate-y-1/2 start-3 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="{{ __('Search products...') }}"
                       class="w-full ps-10 pe-4 py-2.5 rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
            </div>
            <button type="submit"
                    class="px-4 py-2.5 rounded-md bg-brand-600 hover:bg-brand-700 text-white text-sm font-semibold">
                {{ __('Search') }}
            </button>
            @if (($q ?? '') !== '' || ($activeCategory ?? null))
                <a href="{{ route('catalog.index') }}"
                   class="px-3 py-2.5 rounded-md bg-white text-gray-700 text-sm font-medium border border-gray-300 hover:bg-gray-50">
                    {{ __('Clear') }}
                </a>
            @endif
        </form>

        @if ($categories->isNotEmpty())
            {{-- Top-level categories --}}
            <div class="flex items-center gap-2 overflow-x-auto pb-2 -mx-1 px-1">
                <a href="{{ route('catalog.index', request()->only('q')) }}"
                   class="shrink-0 px-3.5 py-1.5 rounded-full text-sm font-medium border transition
                          {{ ! ($activeParent ?? null) ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">
                    {{ __('All') }}
                </a>
                @foreach ($categories as $cat)
                    <a href="{{ route('catalog.index', array_filter(['q' => $q ?? null, 'category' => $cat->slug])) }}"
                       class="shrink-0 px-3.5 py-1.5 rounded-full text-sm font-medium border transition
                              {{ ($activeParent ?? null) && $activeParent->id === $cat->id ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>

            {{-- Sub-categories of the active top-level category --}}
            @if (($activeParent ?? null) && $activeParent->children->isNotEmpty())
                <div class="flex items-center gap-2 overflow-x-auto pb-2 -mx-1 px-1">
                    <a href="{{ route('catalog.index', array_filter(['q' => $q ?? null, 'category' => $activeParent->slug])) }}"
                       class="shrink-0 px-3 py-1 rounded-full text-xs font-medium border transition
                              {{ ($activeCategory ?? null) && $activeCategory->id === $activeParent->id ? 'bg-brand-100 text-brand-700 border-brand-200' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                        {{ __('All') }} {{ $activeParent->name }}
                    </a>
                    @foreach ($activeParent->children as $child)
                        <a href="{{ route('catalog.index', array_filter(['q' => $q ?? null, 'category' => $child->slug])) }}"
                           class="shrink-0 px-3 py-1 rounded-full text-xs font-medium border transition
                                  {{ ($activeCategory ?? null) && $activeCategory->id === $child->id ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                            {{ $child->name }}
                        </a>
                    @endforeach
                </div>
            @endif
        @endif
    </div>

    {{-- Section header --}}
    <div class="flex items-center justify-between mb-5">
        <h2 class="text-lg font-semibold text-gray-900">
            @if ($activeCategory ?? null)
                {{ $activeCategory->name }}
            @elseif (($q ?? '') !== '')
                {{ __('Search results') }}: <span class="text-gray-600 font-normal">"{{ $q }}"</span>
            @else
                {{ __('Catalog') }}
            @endif
        </h2>
        @if (! $products->isEmpty())
            <span class="text-xs text-gray-500">{{ $products->total() }} {{ __('Items') }}</span>
        @endif
    </div>

    @if ($products->isEmpty())
        <div class="bg-white border border-gray-200 rounded-xl p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
            </svg>
            <p class="text-gray-500 text-sm">
                @if (($q ?? '') !== '' || ($activeCategory ?? null))
                    {{ __('No products match your search.') }}
                @else
                    {{ __('No products available yet') }}
                @endif
            </p>
            @if (($q ?? '') !== '' || ($activeCategory ?? null))
                <a href="{{ route('catalog.index') }}" class="mt-3 inline-block text-brand-600 hover:text-brand-700 text-sm font-medium">
                    {{ __('Clear filters') }}
                </a>
            @endif
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4">
            @foreach ($products as $product)
                <a href="{{ route('catalog.show', $product) }}"
                   class="group flex flex-col bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-card-hover hover:border-gray-300 transition-all duration-200">
                    <div class="relative aspect-square overflow-hidden bg-gray-100">
                        @if ($product->mainImageUrl())
                            <img src="{{ $product->mainImageUrl() }}"
                                 alt="{{ $product->name }}"
                                 loading="lazy"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="absolute inset-0 flex items-center justify-center text-gray-300">
                                <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                                </svg>
                            </div>
                        @endif
                        @if ($product->isOnSale())
                            <span class="absolute top-2 start-2 inline-flex items-center rounded-md bg-accent-600 text-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider shadow">
                                {{ __('Save') }} {{ $product->discount_percentage }}%
                            </span>
                        @endif
                        @if ($product->stock <= 0)
                            <span class="absolute top-2 end-2 inline-flex items-center rounded-md bg-red-100 text-red-700 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider">
                                {{ __('Out of Stock') }}
                            </span>
                        @elseif ($product->stock <= 3)
                            <span class="absolute top-2 end-2 inline-flex items-center rounded-md bg-brand-50 text-brand-700 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider">
                                {{ $product->stock }} {{ __('left') }}
                            </span>
                        @endif
                    </div>
                    <div class="flex-1 flex flex-col p-3 sm:p-4 gap-3">
                        <h3 class="font-medium text-gray-900 text-sm leading-snug line-clamp-2 min-h-[2.5rem] group-hover:text-brand-700 transition">
                            {{ $product->name }}
                        </h3>
                        <div class="flex items-end justify-between mt-auto">
                            <div class="flex flex-col">
                                <span class="text-base font-semibold text-gray-900">{{ money_format($product->price) }}</span>
                                @if ($product->isOnSale())
                                    <span class="text-xs text-gray-400 line-through leading-tight">{{ money_format($product->compare_at_price) }}</span>
                                @endif
                            </div>
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-100 group-hover:bg-brand-600 group-hover:text-white text-gray-700 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17"/>
                                </svg>
                            </span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @endif
</x-layouts.shop>
