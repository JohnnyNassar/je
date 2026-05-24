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
    @endphp

    <div class="flex items-center justify-between mb-5">
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('My Orders') }}</h1>
        <span class="text-sm text-gray-500">{{ $orders->total() }} {{ __('Items') }}</span>
    </div>

    @php($me = auth('customer')->user())
    @if ($me && filter_var(\App\Models\Setting::get('loyalty_enabled'), FILTER_VALIDATE_BOOLEAN))
        <div class="mb-5 rounded-xl bg-brand-50 border border-brand-200 px-5 py-4 flex items-center gap-3">
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-brand-600 text-white shrink-0">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            </span>
            <div>
                <div class="text-sm font-semibold text-brand-900">{{ number_format($me->points_balance) }} {{ __('points') }}</div>
                <div class="text-xs text-brand-700">{{ __('Redeem them at checkout for a discount.') }}</div>
            </div>
        </div>
    @endif

    @if ($orders->isEmpty())
        <div class="bg-white border border-gray-200 rounded-xl p-12 text-center">
            <p class="text-gray-600 text-sm mb-4">{{ __('You have no orders yet.') }}</p>
            <a href="{{ route('catalog.index') }}"
               class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-medium px-5 py-2.5 rounded-md transition">
                {{ __('Browse Catalog') }}
            </a>
        </div>
    @else
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <ul class="divide-y divide-gray-100">
                @foreach ($orders as $order)
                    @php($c = $statusColors[$order->status] ?? $statusColors['pending'])
                    <li class="p-4 sm:p-5 flex items-center gap-4">
                        <a href="{{ route('my-orders.show', $order) }}" class="flex-1 min-w-0 flex items-center gap-4">
                            @php($firstItem = $order->items->first())
                            <div class="w-[64px] h-[64px] shrink-0 rounded-md overflow-hidden bg-gray-100">
                                @if ($firstItem && $firstItem->product && $firstItem->product->image_path)
                                    <img src="{{ asset('storage/' . $firstItem->product->image_path) }}" alt="" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ __('Order') }} #{{ $order->id }} &middot; {{ $order->items->count() }} {{ __('items') ?? 'items' }}
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5">{{ $order->created_at->format('Y-m-d H:i') }}</div>
                            </div>
                        </a>
                        <div class="text-end shrink-0">
                            <div class="text-sm font-semibold text-gray-900">{{ money_format($order->total) }}</div>
                            <span class="inline-flex items-center gap-1 rounded-full {{ $c['bg'] }} {{ $c['text'] }} px-2 py-0.5 text-[10px] font-semibold mt-1">
                                <span class="w-1.5 h-1.5 rounded-full {{ $c['dot'] }}"></span>
                                {{ $statusLabels[$order->status] ?? $order->status }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="mt-6">{{ $orders->links() }}</div>
    @endif
</x-layouts.shop>
