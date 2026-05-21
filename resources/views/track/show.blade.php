<x-layouts.shop>
    @php
        $statusColors = [
            'pending'   => ['bg' => 'bg-amber-100',  'text' => 'text-amber-800',  'ring' => 'ring-amber-200',  'dot' => 'bg-amber-500'],
            'confirmed' => ['bg' => 'bg-blue-100',   'text' => 'text-blue-800',   'ring' => 'ring-blue-200',   'dot' => 'bg-blue-500'],
            'delivered' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'ring' => 'ring-green-200', 'dot' => 'bg-green-500'],
            'cancelled' => ['bg' => 'bg-red-100',    'text' => 'text-red-800',    'ring' => 'ring-red-200',    'dot' => 'bg-red-500'],
        ];
        $statusLabels = [
            'pending'   => __('Pending'),
            'confirmed' => __('Confirmed'),
            'delivered' => __('Delivered'),
            'cancelled' => __('Cancelled'),
        ];
    @endphp

    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-semibold text-gray-900 mb-2">{{ __('Track Order') }}</h1>
        <p class="text-sm text-gray-600 mb-6">{{ __('Enter your order number and the phone number you used to place it.') }}</p>

        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <form method="POST" action="{{ route('track.lookup') }}" class="p-5 sm:p-6 space-y-4">
                @csrf
                @if ($errors->any())
                    <div role="alert" class="rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
                        <ul class="list-disc ps-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Order') }} #</label>
                        <input type="number" name="order_id" value="{{ old('order_id') }}" min="1" required
                               class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm" dir="ltr">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Phone') }}</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}" required dir="ltr"
                               class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                    </div>
                </div>

                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold px-6 py-3 rounded-md transition shadow-card">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    {{ __('Look up order') }}
                </button>
            </form>
        </div>

        @if ($order ?? null)
            @php($c = $statusColors[$order->status] ?? $statusColors['pending'])
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden mt-6">
                <div class="px-5 sm:px-7 py-5 border-b border-gray-100">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Order') }} #{{ $order->id }}</h2>
                        <span class="inline-flex items-center gap-1.5 rounded-full {{ $c['bg'] }} {{ $c['text'] }} px-3 py-1 text-xs font-semibold ring-1 {{ $c['ring'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $c['dot'] }}"></span>
                            {{ $statusLabels[$order->status] ?? $order->status }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div>
                            <div class="text-xs text-gray-500">{{ __('Placed on') ?? 'Placed on' }}</div>
                            <div class="text-sm font-medium text-gray-900">{{ $order->created_at->format('Y-m-d H:i') }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">{{ __('Total') }}</div>
                            <div class="text-sm font-medium text-gray-900">{{ money_format($order->total) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">{{ __('Payment Method') }}</div>
                            <div class="text-sm font-medium text-gray-900">{{ __('Cash on Delivery') }}</div>
                        </div>
                    </div>
                </div>

                <ul class="divide-y divide-gray-100">
                    @foreach ($order->items as $item)
                        <li class="px-5 sm:px-7 py-4 flex items-center gap-4">
                            <div class="w-[64px] h-[64px] shrink-0 rounded-md overflow-hidden bg-gray-100">
                                @if ($item->product && $item->product->image_path)
                                    <img src="{{ asset('storage/' . $item->product->image_path) }}" alt="" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 line-clamp-2 leading-snug">{{ $item->product_name }}</div>
                                <div class="text-xs text-gray-500 mt-1">{{ money_format($item->unit_price) }} <span class="text-gray-400">/ {{ __('each') }}</span></div>
                            </div>
                            <div class="text-end">
                                <div class="text-xs text-gray-500">{{ $item->quantity }} ×</div>
                                <div class="text-sm font-semibold text-gray-900">{{ money_format($item->line_total) }}</div>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="px-5 sm:px-7 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-900">{{ __('Total') }}</span>
                    <span class="text-lg font-bold text-gray-900">{{ money_format($order->total) }}</span>
                </div>
            </div>
        @endif
    </div>
</x-layouts.shop>
