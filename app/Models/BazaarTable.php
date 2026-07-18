<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BazaarTable extends Model
{
    use \App\Concerns\LogsActivity;

    public const SECTION_RESTAURANT = 'RESTAURANT';

    /** Single source of truth for section labels, fed straight into Filament selects. */
    public const SECTIONS = [
        'A' => 'Section A',
        'B' => 'Section B',
        'C' => 'Section C',
        'D' => 'Section D',
        self::SECTION_RESTAURANT => 'Restaurants',
    ];

    /** Fill colours for the generated SVG floor plan, matching the architect's legend. */
    public const SECTION_COLOURS = [
        'A' => '#4f7fe0',
        'B' => '#e05252',
        'C' => '#f0b429',
        'D' => '#3faa5a',
        self::SECTION_RESTAURANT => '#b57edc',
    ];

    protected $fillable = [
        'number',
        'section',
        'price',
        'pos_x',
        'pos_y',
        'width',
        'height',
        'rotation',
        'is_active',
        'is_bookable',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'is_bookable' => 'boolean',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(BazaarBooking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Tables a vendor can actually reserve — excludes the restaurant units. */
    public function scopeBookable(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('is_bookable', true);
    }

    public function getSectionLabelAttribute(): string
    {
        return self::SECTIONS[$this->section] ?? $this->section;
    }

    public function getColourAttribute(): string
    {
        return self::SECTION_COLOURS[$this->section] ?? '#9ca3af';
    }

    public function isRestaurant(): bool
    {
        return $this->section === self::SECTION_RESTAURANT;
    }

    protected function activityDescription(string $event): string
    {
        return 'Bazaar table #'.$this->number.' '.$event;
    }
}
