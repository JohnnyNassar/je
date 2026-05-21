<x-layouts.shop>
    @php
        $statusColors = [
            'pending'   => ['bg' => 'bg-amber-100',  'text' => 'text-amber-800',  'dot' => 'bg-amber-500'],
            'confirmed' => ['bg' => 'bg-blue-100',   'text' => 'text-blue-800',   'dot' => 'bg-blue-500'],
            'delivered' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'dot' => 'bg-green-500'],
            'cancelled' => ['bg' => 'bg-red-100',    'text' => 'text-red-800',    'dot' => 'bg-red-500'],
        ];
        $statusLabels = [
            'pending'   => __('Pending'),
            'confirmed' => __('Confirmed'),
            'delivered' => __('Delivered'),
            'cancelled' => __('Cancelled'),
        ];
        $c = $statusColors[$order->status] ?? $statusColors['pending'];
    @endphp

    <a href="{{ route('my-orders.index') }}" class="text-sm text-brand-600 hover:text-brand-700 mb-4 inline-block">← {{ __('My Orders') }}</a>

    <div class="max-w-3xl">
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="px-5 sm:px-7 py-5 border-b border-gray-100">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h1 class="text-lg sm:text-xl font-semibold text-gray-900">{{ __('Order') }} #{{ $order->id }}</h1>
                    <span class="inline-flex items-center gap-1.5 rounded-full {{ $c['bg'] }} {{ $c['text'] }} px-3 py-1 text-xs font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full {{ $c['dot'] }}"></span>
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div>
                        <div class="text-xs text-gray-500">{{ __('Placed on') }}</div>
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
                        <div class="w-[70px] h-[70px] shrink-0 rounded-md overflow-hidden bg-gray-100">
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

        <div class="mt-4 text-xs text-gray-500">
            {{ __('Shipping to') }}: {{ $order->city ? $order->city . ' — ' : '' }}{{ $order->address }}
        </div>
    </div>
</x-layouts.shop>
