<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable implements CanResetPasswordContract
{
    use Notifiable, CanResetPassword;

    /**
     * Customer tiers, value => label. Single source of truth used by the admin
     * form/table and anywhere tier logic is applied (e.g. pricing, loyalty).
     */
    public const TIERS = [
        'regular' => 'Regular',
        'vip' => 'VIP',
        'wholesale' => 'Wholesale',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'city',
        'address',
        'password',
        'points_balance',
        'tier',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'tier' => 'regular',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getTierLabelAttribute(): string
    {
        return self::TIERS[$this->tier] ?? self::TIERS['regular'];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class)->latest();
    }
}
