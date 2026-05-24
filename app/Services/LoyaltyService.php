<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltyPromotion;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Loyalty points: customers earn points when an order is delivered and can
 * redeem them for a discount at checkout. All rates are admin-editable settings.
 */
class LoyaltyService
{
    public function enabled(): bool
    {
        return filter_var(Setting::get('loyalty_enabled'), FILTER_VALIDATE_BOOLEAN);
    }

    /** Points earned per 1 currency unit spent. */
    public function earnRate(): float
    {
        return (float) (Setting::get('loyalty_earn_rate') ?: 1);
    }

    /** Currency value of a single point (e.g. 0.01 → 100 points = 1.00). */
    public function redeemValue(): float
    {
        return (float) (Setting::get('loyalty_redeem_value') ?: 0.01);
    }

    public function minRedeem(): int
    {
        return (int) (Setting::get('loyalty_min_redeem') ?: 0);
    }

    public function pointsForAmount(float $amount): int
    {
        return (int) floor(max(0, $amount) * $this->earnRate());
    }

    public function valueOfPoints(int $points): float
    {
        return round(max(0, $points) * $this->redeemValue(), 2);
    }

    /**
     * Points the customer may redeem against $against (currency) and the
     * resulting discount — capped so the discount never exceeds $against.
     *
     * @return array{points: int, discount: float}
     */
    public function maxRedeemable(Customer $customer, float $against): array
    {
        $none = ['points' => 0, 'discount' => 0.0];

        if (! $this->enabled() || $against <= 0) {
            return $none;
        }

        $balance = (int) $customer->points_balance;
        $rate = $this->redeemValue();

        if ($balance <= 0 || $balance < $this->minRedeem() || $rate <= 0) {
            return $none;
        }

        $valueOfAll = $balance * $rate;

        if ($valueOfAll <= $against) {
            return ['points' => $balance, 'discount' => round($valueOfAll, 2)];
        }

        // More points than needed — spend only enough to cover $against.
        $points = (int) floor($against / $rate);

        return ['points' => $points, 'discount' => round($points * $rate, 2)];
    }

    /**
     * Apply the best active promotion to a base points earn for an order total.
     *
     * @return array{points: int, promotion: ?LoyaltyPromotion}
     */
    public function applyPromotions(int $basePoints, float $orderTotal, ?\Illuminate\Support\Carbon $at = null): array
    {
        $best = ['points' => $basePoints, 'promotion' => null];

        if (! $this->enabled()) {
            return $best;
        }

        foreach (LoyaltyPromotion::active($at)->get() as $promotion) {
            if (! $promotion->appliesTo($orderTotal)) {
                continue;
            }

            $points = $promotion->apply($basePoints);
            if ($points > $best['points']) {
                $best = ['points' => $points, 'promotion' => $promotion];
            }
        }

        return $best;
    }

    /** Points a purchase of $amount would earn, including any active promotion. */
    public function estimatedPointsForAmount(float $amount): int
    {
        return $this->applyPromotions($this->pointsForAmount($amount), $amount)['points'];
    }

    /** Credit points for a delivered order. Idempotent — safe to call repeatedly. */
    public function awardForOrder(Order $order): void
    {
        if (! $this->enabled() || $order->status !== 'delivered' || $order->points_earned > 0) {
            return;
        }

        $customer = $order->customer;
        if (! $customer) {
            return;
        }

        $base = $this->pointsForAmount((float) $order->total);
        $result = $this->applyPromotions($base, (float) $order->total);
        $points = $result['points'];
        if ($points <= 0) {
            return;
        }

        $promotion = $result['promotion'];
        $description = "Order #{$order->id} delivered" . ($promotion ? " — {$promotion->name}" : '');

        DB::transaction(function () use ($order, $customer, $points, $description) {
            $order->updateQuietly(['points_earned' => $points]);
            $customer->increment('points_balance', $points);
            LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'points' => $points,
                'type' => 'earn',
                'description' => $description,
            ]);
        });
    }
}
