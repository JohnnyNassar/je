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
    }
}
