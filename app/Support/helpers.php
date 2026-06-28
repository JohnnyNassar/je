<?php

use App\Models\Setting;

if (! function_exists('storage_image_url')) {
    /**
     * Public URL for an image on the "public" storage disk, with a cache-busting
     * "?v=<mtime>" query string. The Cover-logo tool overwrites image files in
     * place (same path), but nginx serves /storage assets with a long-lived
     * "immutable" Cache-Control, so browsers would otherwise keep showing the
     * stale copy. Keying the query to the file's modification time makes the URL
     * change exactly when the file changes, busting the cache on edit.
     */
    function storage_image_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $url = asset('storage/' . $path);
        $abs = public_path('storage/' . $path);

        return is_file($abs) ? $url . '?v=' . filemtime($abs) : $url;
    }
}

if (! function_exists('money_format')) {
    function money_format(float|int|string $amount): string
    {
        $symbol = Setting::get('currency_symbol', '$');
        $position = Setting::get('currency_position', 'before');
        $formatted = number_format((float) $amount, 2);

        return $position === 'after'
            ? "{$formatted} {$symbol}"
            : "{$symbol}{$formatted}";
    }
}
