<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(private Cart $cart)
    {
    }

    public function show()
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart.index');
        }

        return view('checkout.show', [
            'items' => $this->cart->items(),
            'total' => $this->cart->total(),
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

        $order = DB::transaction(function () use ($data, $items) {
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

            $total = (float) $items->sum('line_total');

            $order = Order::create([
                'customer_id' => $customer->id,
                'phone' => $data['phone'],
                'city' => $data['city'] ?? null,
                'address' => $data['address'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'payment_method' => 'cod',
                'total' => $total,
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name_en,
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                ]);

                $item['product']->decrement('stock', $item['quantity']);
            }

            return $order;
        });

        $this->cart->clear();

        return redirect()->route('orders.confirmation', $order);
    }
}
