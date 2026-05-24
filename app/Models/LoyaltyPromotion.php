<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A loyalty promotion temporarily boosts the points a delivered order earns —
 * either by multiplying the base points ("double points") or adding a flat
 * bonus. Optionally limited to orders above a minimum and to a date window.
 */
class LoyaltyPromotion extends Model
{
    protected $fillable = [
        'name',
        'type',
        'multiplier',
        'bonus_points',
        'min_order_total',
        'starts_at',
        'ends_at',
        'active',
    ];

    protected $casts = [
        'multiplier' => 'decimal:2',
        'bonus_points' => 'integer',
        'min_order_total' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'active' => 'boolean',
    ];

    /** Promotions that are enabled and within their date window at $at (default now). */
    public function scopeActive($query, ?Carbon $at = null)
    {
        $at = $at ?: now();

        return $query->where('active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $at));
    }

    /** Whether this promotion applies to an order of the given total. */
    public function appliesTo(float $orderTotal): bool
    {
        return $this->min_order_total === null || $orderTotal >= (float) $this->min_order_total;
    }

    /** Points a customer receives given a base earn of $basePoints. */
    public function apply(int $basePoints): int
    {
        return match ($this->type) {
            'multiplier' => (int) floor($basePoints * (float) ($this->multiplier ?: 1)),
            'bonus' => $basePoints + (int) $this->bonus_points,
            default => $basePoints,
        };
    }

    /** Is the promotion live right now (enabled and inside its window)? */
    public function getRunningAttribute(): bool
    {
        if (! $this->active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->gt($now)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->lt($now)) {
            return false;
        }

        return true;
    }
}
