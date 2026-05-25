<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use \App\Concerns\LogsActivity;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_total',
        'max_uses',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_total' => 'decimal:2',
        'max_uses' => 'integer',
        'used_count' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /** Codes are always stored upper-cased so lookups are case-insensitive. */
    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = strtoupper(trim((string) $value));
    }

    public static function findByCode(?string $code): ?self
    {
        $code = strtoupper(trim((string) $code));

        return $code === '' ? null : static::where('code', $code)->first();
    }

    /** Active, within its date window, and under its usage cap — independent of any cart. */
    public function isRedeemable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isAfter($now)) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isBefore($now)) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function meetsMinimum(float $subtotal): bool
    {
        return $this->min_order_total === null
            || $subtotal >= (float) $this->min_order_total;
    }

    /** Discount amount this coupon takes off the given subtotal, never exceeding it. */
    public function discountFor(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        $discount = $this->type === 'percent'
            ? $subtotal * ((float) $this->value) / 100
            : (float) $this->value;

        return round(min(max($discount, 0), $subtotal), 2);
    }
}
