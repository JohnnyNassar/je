<?php

namespace App\Filament\Concerns;

/**
 * Restricts a Filament resource or page to admin users. Overriding canAccess()
 * both hides the item from staff navigation and returns 403 if a staff member
 * reaches the URL directly. Works for Resources and custom Pages alike, since
 * both expose a static canAccess(): bool.
 */
trait AdminOnly
{
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
