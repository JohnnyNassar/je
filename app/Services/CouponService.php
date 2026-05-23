<?php

namespace App\Services;

use App\Models\Coupon;

/**
 * Resolves the coupon currently held in the session against a live subtotal.
 * Shared by the cart and checkout so both pages compute the same discount.
 */
class CouponService
{
    private const SESSION_KEY = 'coupon_code';

    /**
     * @return array{0: ?Coupon, 1: float} [coupon, discount] — [null, 0.0] when none/invalid.
     */
    public function applied(float $subtotal): array
    {
        $code = session(self::SESSION_KEY);

        if (! $code) {
            return [null, 0.0];
        }

        $coupon = Coupon::findByCode($code);

        if (! $coupon || ! $coupon->isRedeemable() || ! $coupon->meetsMinimum($subtotal)) {
            return [null, 0.0];
        }

        return [$coupon, $coupon->discountFor($subtotal)];
    }

    public function put(string $code): void
    {
        session([self::SESSION_KEY => strtoupper(trim($code))]);
    }

    public function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function hasCode(): bool
    {
        return (bool) session(self::SESSION_KEY);
    }
}
