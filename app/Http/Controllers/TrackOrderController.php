<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class TrackOrderController extends Controller
{
    public function show(Request $request)
    {
        return view('track.show', ['order' => null]);
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'min:1'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $key = 'track:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withErrors(['phone' => __('Too many attempts. Try again in a minute.')]);
        }
        RateLimiter::hit($key, 60);

        $needle = preg_replace('/[^0-9+]/', '', $data['phone']);

        $order = Order::with('items')
            ->where('id', $data['order_id'])
            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', '') LIKE ?", ['%' . $needle . '%'])
            ->first();

        if (! $order) {
            return back()
                ->withInput()
                ->withErrors(['order_id' => __('No order matches that ID and phone number.')]);
        }

        return view('track.show', ['order' => $order]);
    }
}
