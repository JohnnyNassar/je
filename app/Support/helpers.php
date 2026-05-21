<?php

use App\Models\Setting;

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
