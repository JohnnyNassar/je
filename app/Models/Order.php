<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'phone',
        'city',
        'address',
        'notes',
        'status',
        'payment_method',
        'total',
        'discount_total',
        'coupon_code',
        'points_earned',
        'points_redeemed',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'points_earned' => 'integer',
        'points_redeemed' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    protected static function booted(): void
    {
        // Credit loyalty points when an order becomes delivered (idempotent).
        static::updated(function (self $order) {
            if ($order->wasChanged('status') && $order->status === 'delivered') {
                app(\App\Services\LoyaltyService::class)->awardForOrder($order);
            }
        });

        // In-app alert to admins on a new order (the "Dashboard" channel).
        static::created(function (self $order) {
            $enabled = filter_var(Setting::get('notify_admin_new_order') ?? 'true', FILTER_VALIDATE_BOOLEAN);
            if (! $enabled) {
                return;
            }

            try {
                $admins = User::where('role', 'admin')->get();
                if ($admins->isEmpty()) {
                    return;
                }

                \Filament\Notifications\Notification::make()
                    ->title('New order #' . $order->id)
                    ->body(money_format($order->total) . ' · ' . ($order->phone ?? ''))
                    ->icon('heroicon-o-shopping-bag')
                    ->iconColor('success')
                    ->sendToDatabase($admins);
            } catch (\Throwable $e) {
                // A notification failure must never break order creation.
                report($e);
            }
        });
    }
}
