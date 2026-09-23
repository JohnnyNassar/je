<?php

namespace App\Filament\Concerns;

/**
 * Restricts a resource or page to back-office users who are allowed to see
 * orders — admins and the owner, minus anyone whose can_view_orders flag has
 * been turned off.
 *
 * Separate from AdminOnly because "can use the back office" and "may see what
 * the business earns" are different questions: a catalogue manager can need
 * customers, coupons and the bazaar while having no business seeing revenue.
 */
trait OrdersOnly
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isAdmin() && $user->canViewOrders());
    }
}
