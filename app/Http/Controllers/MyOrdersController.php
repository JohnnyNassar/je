<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyOrdersController extends Controller
{
    public function index()
    {
        $customer = Auth::guard('customer')->user();
        $orders = $customer->orders()
            ->with('items')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('my-orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($order->customer_id === $customer->id, 404);

        $order->load('items');

        return view('my-orders.show', compact('order'));
    }
}
