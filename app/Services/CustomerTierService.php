<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Setting;

/**
 * Applies the perks attached to a customer's tier (see Customer::TIERS):
 *   - wholesale  → a percentage discount off every unit price they pay.
 *   - vip        → a multiplier on the loyalty points they earn.
 * Both rates are admin-editable settings (Store settings / Loyalty settings).
 * A guest (null customer) or a 'regular' customer gets no adjustment.
 */
class CustomerTierService
{
    /** Wholesale discount as a fraction in [0, 1]. 0 disables wholesale pricing. */
    public function wholesaleDiscountRate(): float
    {
        $percent = (float) (Setting::get('tier_wholesale_discount_percent') ?? 0);

        return max(0.0, min(100.0, $percent)) / 100;
    }

    /** Loyalty points multiplier for VIPs (>= 1). 1 means no boost. */
    public function vipPointsMultiplier(): float
    {
        $multiplier = (float) (Setting::get('tier_vip_points_multiplier') ?? 1);

        return $multiplier > 0 ? $multiplier : 1.0;
    }

    /** The unit price a customer actually pays, after any tier discount. */
    public function priceFor(?Customer $customer, float $retailPrice): float
    {
        if ($customer?->tier === 'wholesale') {
            $rate = $this->wholesaleDiscountRate();
            if ($rate > 0) {
                return round($retailPrice * (1 - $rate), 2);
            }
        }

        return round($retailPrice, 2);
    }

    /** Loyalty points multiplier that applies to this customer's tier. */
    public function pointsMultiplierFor(?Customer $customer): float
    {
        return $customer?->tier === 'vip' ? $this->vipPointsMultiplier() : 1.0;
    }
}
