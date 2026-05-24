<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Cart;
use App\Services\CouponService;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(
        private Cart $cart,
        private CouponService $coupons,
        private LoyaltyService $loyalty,
    ) {
    }

    public function show()
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $subtotal = $this->cart->total();
        [$coupon, $discount] = $this->coupons->applied($subtotal);

        // A code may have become invalid since it was applied (cart changed,
        // coupon expired/disabled). Drop it so the UI doesn't promise a discount.
        if ($this->coupons->hasCode() && ! $coupon) {
            $this->coupons->forget();
        }

        $afterCoupon = round($subtotal - $discount, 2);

        // Loyalty: points the customer will earn, and points they can redeem now.
        $customer = auth('customer')->user();
        $loyaltyEnabled = $this->loyalty->enabled();
        $redeem = ['points' => 0, 'discount' => 0.0];
        $pointsBalance = 0;
        if ($loyaltyEnabled && $customer) {
            $pointsBalance = (int) $customer->points_balance;
            $redeem = $this->loyalty->maxRedeemable($customer, $afterCoupon);
        }

        return view('checkout.show', [
            'items' => $this->cart->items(),
            'subtotal' => $subtotal,
            'coupon' => $coupon,
            'discount' => $discount,
            'total' => $afterCoupon,
            'loyaltyEnabled' => $loyaltyEnabled,
            'pointsBalance' => $pointsBalance,
            'pointsEarn' => $loyaltyEnabled ? $this->loyalty->pointsForAmount($afterCoupon) : 0,
            'redeemPoints' => $redeem['points'],
            'redeemDiscount' => $redeem['discount'],
            'totalWithPoints' => round($afterCoupon - $redeem['discount'], 2),
        ]);
    }

    public function store(Request $request)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $items = $this->cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $subtotal = (float) $items->sum('line_total');
        [$coupon, $couponDiscount] = $this->coupons->applied($subtotal);
        $afterCoupon = round($subtotal - $couponDiscount, 2);

        // Loyalty redemption — logged-in customers only, opt-in via the checkbox.
        $pointsRedeemed = 0;
        $pointsDiscount = 0.0;
        $authCustomer = auth('customer')->user();
        if ($this->loyalty->enabled() && $authCustomer && $request->boolean('redeem_points')) {
            $r = $this->loyalty->maxRedeemable($authCustomer, $afterCoupon);
            $pointsRedeemed = $r['points'];
            $pointsDiscount = $r['discount'];
        }

        $discountTotal = round($couponDiscount + $pointsDiscount, 2);
        $total = round($subtotal - $discountTotal, 2);

        $order = DB::transaction(function () use ($data, $items, $total, $discountTotal, $coupon, $couponDiscount, $pointsRedeemed) {
            $authCustomer = auth('customer')->user();
            if ($authCustomer) {
                $customer = $authCustomer;
                $customer->fill([
                    'name' => $data['name'],
                    'city' => $data['city'] ?? $customer->city,
                    'address' => $data['address'],
                ])->save();
            } else {
                $customer = Customer::firstOrCreate(
                    ['phone' => $data['phone']],
                    [
                        'name' => $data['name'],
                        'city' => $data['city'] ?? null,
                        'address' => $data['address'],
                    ],
                );
            }

            $order = Order::create([
                'customer_id' => $customer->id,
                'phone' => $data['phone'],
                'city' => $data['city'] ?? null,
                'address' => $data['address'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'payment_method' => 'cod',
                'total' => $total,
                'discount_total' => $discountTotal,
                'coupon_code' => $coupon?->code,
                'points_redeemed' => $pointsRedeemed,
            ]);

            foreach ($items as $item) {
                $variant = $item['variant'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'variant_id' => $variant?->id,
                    'product_name' => $item['product']->name_en,
                    'variant_name' => $variant?->name,
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                ]);

                if ($variant) {
                    $variant->decrement('stock', $item['quantity']);
                    $item['product']->syncStockFromVariants();
                } else {
                    $item['product']->decrement('stock', $item['quantity']);
                }
            }

            if ($coupon && $couponDiscount > 0) {
                $coupon->increment('used_count');
            }

            if ($pointsRedeemed > 0) {
                $customer->decrement('points_balance', $pointsRedeemed);
                \App\Models\LoyaltyTransaction::create([
                    'customer_id' => $customer->id,
                    'order_id' => $order->id,
                    'points' => -$pointsRedeemed,
                    'type' => 'redeem',
                    'description' => "Redeemed on order #{$order->id}",
                ]);
            }

            return $order;
        });

        $this->cart->clear();
        $this->coupons->forget();

        return redirect()->route('orders.confirmation', $order);
    }

    public function applyCoupon(Request $request)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $request->validate(['code' => ['required', 'string', 'max:60']]);

        $subtotal = $this->cart->total();
        $coupon = \App\Models\Coupon::findByCode($request->input('code'));

        if (! $coupon || ! $coupon->isRedeemable()) {
            return back()->withErrors(['code' => __('This coupon code is invalid or has expired.')]);
        }

        if (! $coupon->meetsMinimum($subtotal)) {
            return back()->withErrors([
                'code' => __('This coupon needs a minimum order of :amount.', ['amount' => money_format($coupon->min_order_total)]),
            ]);
        }

        $this->coupons->put($coupon->code);

        return back()->with('cart_status', __('Coupon applied.'));
    }

    public function removeCoupon()
    {
        $this->coupons->forget();

        return back();
    }
}
