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
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'discount_total' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
