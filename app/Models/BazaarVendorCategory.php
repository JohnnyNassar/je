<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * What a vendor sells. Drives which paperwork the booking must carry:
 * anything eaten, drunk or applied to the body needs a health certificate
 * before it may trade (leasing contract, Art. 31).
 */
class BazaarVendorCategory extends Model
{
    use \App\Concerns\LogsActivity;

    protected $fillable = [
        'name_en',
        'name_ar',
        'slug',
        'requires_health_certificate',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_health_certificate' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(BazaarBooking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('position');
    }

    /** Locale-aware name, mirroring Product::name and Category::name. */
    public function getNameAttribute(): string
    {
        if (app()->getLocale() === 'ar' && filled($this->name_ar)) {
            return $this->name_ar;
        }

        return (string) $this->name_en;
    }

    protected function activityDescription(string $event): string
    {
        return 'Bazaar vendor category "'.$this->name_en.'" '.$event;
    }
}
