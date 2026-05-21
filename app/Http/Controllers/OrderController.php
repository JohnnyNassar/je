<?php

namespace App\Http\Controllers;

use App\Models\Order;

class OrderController extends Controller
{
    public function confirmation(Order $order)
    {
        $order->load('items');

        return view('orders.confirmation', compact('order'));
    }
}
