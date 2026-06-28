<x-layouts.shop>
    <x-checkout-steps active="shipping" />

    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-semibold text-gray-900">{{ __('Shipping Information') }}</h1>
        <a href="{{ route('cart.index') }}" class="text-xs font-medium text-brand-600 hover:text-brand-700">← {{ __('Cart') }}</a>
    </div>

    @if ($errors->any())
        <div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3">
            <div class="flex items-center gap-2 font-medium text-sm mb-1">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span>{{ __('Please correct the errors below.') }}</span>
            </div>
            <ul class="list-disc ps-8 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5" x-data="{ redeem: false }">
        <form method="POST" action="{{ route('checkout.store') }}" class="lg:col-span-2 bg-white border border-gray-200 rounded-xl overflow-hidden">
            @csrf
            <div class="px-5 sm:px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">{{ __('Shipping Information') }}</h2>
            </div>
            @php($me = auth('customer')->user())
            <div class="px-5 sm:px-6 py-5 space-y-4">
                @guest('customer')
                    <div class="text-xs text-gray-500 -mb-2">
                        {{ __('Have an account?') }}
                        <a href="{{ route('customer.login') }}" class="text-brand-600 hover:text-brand-700 font-medium">{{ __('Sign in') }}</a>
                        {{ __('for faster checkout, or continue as guest.') }}
                    </div>
                @endguest

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Full Name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $me->name ?? '') }}" required
                           class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Phone') }} <span class="text-red-500">*</span></label>
                        <input type="tel" name="phone" value="{{ old('phone', $me->phone ?? '') }}" required dir="ltr"
                               class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('City') }}</label>
                        <input type="text" name="city" value="{{ old('city', $me->city ?? '') }}"
                               class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Address') }} <span class="text-red-500">*</span></label>
                    <textarea name="address" required rows="2"
                              class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">{{ old('address', $me->address ?? '') }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1.5">{{ __('Notes') }}</label>
                    <textarea name="notes" rows="2"
                              class="w-full rounded-md border-gray-300 focus:border-brand-500 focus:ring-brand-500 text-sm">{{ old('notes') }}</textarea>
                </div>

                <div class="rounded-md bg-brand-50 border border-brand-200 px-4 py-3 flex items-start gap-3">
                    <svg class="w-5 h-5 text-brand-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a5 5 0 00-10 0v2a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2zM7 9V7a3 3 0 016 0v2"/>
                    </svg>
                    <div class="text-xs">
                        <div class="font-medium text-brand-900">{{ __('Payment Method') }}: {{ __('Cash on Delivery') }}</div>
                        <div class="text-brand-800 mt-0.5">{{ __('You will pay when the order is delivered to you.') }}</div>
                    </div>
                </div>

                @if ($loyaltyEnabled && $redeemPoints > 0)
                    <label class="flex items-start gap-3 rounded-md bg-green-50 border border-green-200 px-4 py-3 cursor-pointer">
                        <input type="checkbox" name="redeem_points" value="1" x-model="redeem"
                               class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-xs">
                            <span class="font-medium text-green-900">{{ __('Redeem :n points', ['n' => number_format($redeemPoints)]) }}</span>
                            <span class="text-green-800">— {{ __('Save') }} {{ money_format($redeemDiscount) }}</span>
                            <span class="block text-green-700 mt-0.5">{{ __('You have :n points.', ['n' => number_format($pointsBalance)]) }}</span>
                        </span>
                    </label>
                @endif
            </div>
            <div class="px-5 sm:px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold px-6 py-2.5 rounded-md transition shadow-card">
                    {{ __('Place Order') }}
                    <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </form>

        <aside class="bg-white border border-gray-200 rounded-xl p-5 h-fit lg:sticky lg:top-20">
            <h2 class="text-base font-semibold text-gray-900 mb-4">{{ __('Order Summary') }}</h2>
            <ul class="divide-y divide-gray-100">
                @foreach ($items as $item)
                    @php($product = $item['product'])
                    @php($variant = $item['variant'])
                    @php($thumb = $variant && $variant->image_path ? $variant->image_path : $product->image_path)
                    <li class="py-3 flex items-center gap-3">
                        <div class="w-12 h-12 shrink-0 rounded-md overflow-hidden bg-gray-100 relative">
                            @if ($thumb)
                                <img src="{{ storage_image_url($thumb) }}" alt="" class="w-full h-full object-cover">
                            @endif
                            <span class="absolute -top-1.5 -end-1.5 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-brand-600 rounded-full ring-2 ring-white">
                                {{ $item['quantity'] }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-medium text-gray-900 line-clamp-1">{{ $product->name }}</div>
                            @if ($variant)
                                <div class="text-[11px] text-gray-600">{{ $variant->name }}</div>
                            @endif
                            <div class="text-[11px] text-gray-500">{{ money_format($item['unit_price']) }}</div>
                        </div>
                        <div class="text-xs font-semibold text-gray-900">{{ money_format($item['line_total']) }}</div>
                    </li>
                @endforeach
            </ul>
            <dl class="border-t border-gray-100 mt-2 pt-3 space-y-2 text-sm">
                <div class="flex items-center justify-between text-gray-600">
                    <dt>{{ __('Subtotal') }}</dt>
                    <dd class="text-gray-900">{{ money_format($subtotal) }}</dd>
                </div>
                @if ($coupon)
                    <div class="flex items-center justify-between text-green-700">
                        <dt class="flex items-center gap-1.5">
                            {{ __('Discount') }}
                            <span class="text-[11px] font-medium text-green-600">({{ $coupon->code }})</span>
                            <form method="POST" action="{{ route('coupon.remove') }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[11px] text-gray-400 hover:text-red-600 underline">{{ __('Remove') }}</button>
                            </form>
                        </dt>
                        <dd>−{{ money_format($discount) }}</dd>
                    </div>
                @endif
                @if ($loyaltyEnabled && $redeemPoints > 0)
                    <div class="flex items-center justify-between text-green-700" x-show="redeem" style="display:none">
                        <dt>{{ __('Points') }} <span class="text-[11px] text-green-600">({{ number_format($redeemPoints) }})</span></dt>
                        <dd>−{{ money_format($redeemDiscount) }}</dd>
                    </div>
                @endif
            </dl>
            <div class="border-t border-gray-100 mt-3 pt-3 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-900">{{ __('Total') }}</span>
                <span class="text-lg font-bold text-gray-900"
                      @if ($loyaltyEnabled && $redeemPoints > 0) x-text="redeem ? '{{ money_format($totalWithPoints) }}' : '{{ money_format($total) }}'" @endif>{{ money_format($total) }}</span>
            </div>
            @if ($loyaltyEnabled && $pointsEarn > 0)
                <p class="mt-3 text-[11px] text-gray-500">{{ __('You will earn :n points on this order.', ['n' => number_format($pointsEarn)]) }}</p>
            @endif
        </aside>
    </div>
</x-layouts.shop>
