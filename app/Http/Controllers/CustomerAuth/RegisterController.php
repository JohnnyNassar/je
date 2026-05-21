<?php

namespace App\Http\Controllers\CustomerAuth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    public function show()
    {
        return view('customer-auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $normalizedPhone = preg_replace('/[^0-9+]/', '', $data['phone']);

        // Merge with existing guest customer row (matched by phone) if any has no password
        $existing = Customer::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', '') = ?", [$normalizedPhone])
            ->whereNull('password')
            ->first();

        if ($existing) {
            $existing->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'city' => $data['city'] ?? $existing->city,
                'address' => $data['address'] ?? $existing->address,
            ]);
            $customer = $existing;
        } else {
            $customer = Customer::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'password' => $data['password'],
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
            ]);
        }

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('my-orders.index');
    }
}
