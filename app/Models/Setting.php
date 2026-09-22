<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use \App\Concerns\LogsActivity;

    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'currency_code' => 'USD',
        'currency_symbol' => '$',
        'currency_position' => 'before',
        'coming_soon_enabled' => 'true',
        'coming_soon_message_en' => 'Something big is coming.',
        'coming_soon_message_ar' => 'قريباً جداً.',
        'hero_image_path' => '',
        'hero_product_id' => '',
        'google_analytics_id' => '',
        // Storefront wording, editable in /admin/settings. The defaults are
        // the words the shop shipped with, so nothing moves until someone
        // edits them. Keep the EN value non-empty for the two that must never
        // render blank — the brand and the hero headline.
        'brand_name_en' => 'JorEption',
        'brand_name_ar' => 'جوربشن',
        'hero_headline_en' => 'JorEption',
        'hero_headline_ar' => 'جوربشن',
        'hero_tagline_en' => 'Quality finds at garage-sale prices.',
        'hero_tagline_ar' => 'منتجات بجودة عالية بأسعار مميزة.',
        'hero_cta_label_en' => 'Browse Catalog',
        'hero_cta_label_ar' => 'تصفح المنتجات',
        // Which parts of the hero banner to show. All on, so the banner looks
        // the same until someone turns something off in /admin/settings.
        'hero_enabled' => 'true',
        'hero_headline_enabled' => 'true',
        'hero_tagline_enabled' => 'true',
        'hero_cta_enabled' => 'true',
        'hero_pill_deals_enabled' => 'true',
        'hero_pill_cod_enabled' => 'true',
        // Customer-tier perks (see App\Services\CustomerTierService).
        'tier_wholesale_discount_percent' => '10',
        'tier_vip_points_multiplier' => '2',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = Cache::rememberForever('settings:all', function () {
            return static::query()->pluck('value', 'key')->toArray();
        });

        return $all[$key] ?? $default ?? self::DEFAULTS[$key] ?? null;
    }

    /**
     * A setting kept in both languages, as `<base>_en` / `<base>_ar`.
     *
     * Under an Arabic locale the Arabic value wins when it has been filled in,
     * and English is the fallback so clearing the Arabic box never leaves a
     * blank on the page. Returns '' when both are empty — callers decide
     * whether that means "hide it" (a tagline) or "use something else" (the
     * brand, which must never render blank).
     */
    public static function localized(string $base): string
    {
        $keys = app()->getLocale() === 'ar'
            ? [$base . '_ar', $base . '_en']
            : [$base . '_en'];

        foreach ($keys as $key) {
            $value = trim((string) static::get($key));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * An on/off setting. Values are stored as the strings 'true' / 'false'.
     */
    public static function enabled(string $key): bool
    {
        return filter_var(static::get($key), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * The shop's name, as shown in the header, the footer, the browser tab and
     * the Coming Soon page. Falls back to APP_NAME so it is never blank.
     */
    public static function brandName(): string
    {
        return static::localized('brand_name') ?: (string) config('app.name');
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings:all');
    }

    public static function all($columns = ['*'])
    {
        if (func_num_args() === 0) {
            $values = Cache::rememberForever('settings:all', function () {
                return static::query()->pluck('value', 'key')->toArray();
            });
            return array_merge(self::DEFAULTS, $values);
        }
        return parent::all($columns);
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings:all'));
        static::deleted(fn () => Cache::forget('settings:all'));
    }

    protected function activityDescription(string $event): string
    {
        return "Setting '{$this->key}' {$event}";
    }

    /** Redact secret values (relay password, API keys) from the audit log. */
    protected function tweakActivityProperties(array $properties): array
    {
        $secret = ['mail_password', 'sms_secret', 'sms_key', 'whatsapp_token'];

        if (in_array($this->key, $secret, true)) {
            foreach (['old', 'attributes'] as $group) {
                if (array_key_exists('value', $properties[$group] ?? [])) {
                    $properties[$group]['value'] = '••••••';
                }
            }
        }

        return $properties;
    }
}
