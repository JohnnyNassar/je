<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private Cart $cart)
    {
    }

    public function index()
    {
        return view('cart.index', [
            'items' => $this->cart->items(),
            'total' => $this->cart->total(),
        ]);
    }

    public function add(Request $request, Product $product)
    {
        abort_unless($product->is_active && $product->stock > 0, 404);

        $quantity = max(1, (int) $request->input('quantity', 1));
        $quantity = min($quantity, $product->stock);

        $this->cart->add($product->id, $quantity);

        return redirect()->route('cart.index')->with('status', __('Added to cart'));
    }

    public function update(Request $request, Product $product)
    {
        $quantity = (int) $request->input('quantity', 0);
        $quantity = max(0, min($quantity, $product->stock));

        $this->cart->set($product->id, $quantity);

        return redirect()->route('cart.index');
    }

    public function destroy(Product $product)
    {
        $this->cart->remove($product->id);

        return redirect()->route('cart.index');
    }

    public function clear()
    {
        $this->cart->clear();

        return redirect()->route('cart.index');
    }
}
