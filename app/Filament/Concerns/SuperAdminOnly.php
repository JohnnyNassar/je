<?php

namespace App\Filament\Concerns;

/**
 * Restricts a Filament resource or page to super-admin users only (the owner).
 * Unlike AdminOnly — which allows both 'admin' and 'super_admin' — this guards
 * owner-only areas: staff/user management, core settings, and the activity log.
 * Overriding canAccess() hides the item from navigation and returns 403 if a
 * non-super-admin reaches the URL directly. Works for Resources and Pages alike.
 */
trait SuperAdminOnly
{
    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
