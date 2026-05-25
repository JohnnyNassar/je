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
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = Cache::rememberForever('settings:all', function () {
            return static::query()->pluck('value', 'key')->toArray();
        });

        return $all[$key] ?? $default ?? self::DEFAULTS[$key] ?? null;
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
