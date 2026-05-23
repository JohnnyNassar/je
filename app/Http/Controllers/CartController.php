<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart;
use App\Services\CouponService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private Cart $cart,
        private CouponService $coupons,
    ) {
    }

    public function index()
    {
        $items = $this->cart->items();
        $subtotal = (float) $items->sum('line_total');
        [$coupon, $discount] = $this->coupons->applied($subtotal);

        if ($this->coupons->hasCode() && ! $coupon) {
            $this->coupons->forget();
        }

        // For products that have more than one variant, list the other in-stock
        // variants not already in the cart so they can be quick-added from here.
        $inCart = [];
        foreach ($items as $it) {
            if ($it['variant']) {
                $inCart[$it['product']->id][] = $it['variant']->id;
            }
        }

        $moreVariants = [];
        foreach ($items as $it) {
            $product = $it['product'];

            if (array_key_exists($product->id, $moreVariants) || $product->variants->count() <= 1) {
                continue;
            }

            $already = $inCart[$product->id] ?? [];
            $others = $product->variants
                ->filter(fn ($v) => $v->stock > 0 && ! in_array($v->id, $already, true))
                ->values();

            if ($others->isNotEmpty()) {
                $moreVariants[$product->id] = $others;
            }
        }

        return view('cart.index', [
            'items' => $items,
            'subtotal' => $subtotal,
            'coupon' => $coupon,
            'discount' => $discount,
            'total' => round($subtotal - $discount, 2),
            'moreVariants' => $moreVariants,
        ]);
    }

    public function add(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        $variant = null;

        if ($product->variants()->exists()) {
            $request->validate(['variant_id' => ['required']]);
            $variant = $product->variants()->find($request->input('variant_id'));
            abort_unless($variant && $variant->stock > 0, 404);
            $available = (int) $variant->stock;
        } else {
            abort_unless($product->stock > 0, 404);
            $available = (int) $product->stock;
        }

        $quantity = max(1, (int) $request->input('quantity', 1));
        $quantity = min($quantity, $available);

        $this->cart->add($product->id, $variant?->id, $quantity);

        return redirect()->route('cart.index')->with('status', __('Added to cart'));
    }

    public function update(Request $request, string $line)
    {
        // Quantity is clamped to available stock when the cart is resolved.
        $this->cart->set($line, max(0, (int) $request->input('quantity', 0)));

        return redirect()->route('cart.index');
    }

    public function destroy(string $line)
    {
        $this->cart->remove($line);

        return redirect()->route('cart.index');
    }

    public function clear()
    {
        $this->cart->clear();

        return redirect()->route('cart.index');
    }
}
