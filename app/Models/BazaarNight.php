<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One trading night. Nights still carry the opening hours and remain the
 * physical reality of the event, but they are no longer what a vendor books —
 * bookings attach to a BazaarPeriod (currently a Thursday+Friday weekend).
 */
class BazaarNight extends Model
{
    use \App\Concerns\ArabicDateNames;
    use \App\Concerns\LogsActivity;

    protected $fillable = [
        'bazaar_period_id',
        'event_date',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(BazaarPeriod::class, 'bazaar_period_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('event_date', '>=', now()->toDateString());
    }

    /** "Thursday 23 July" / "الخميس 23 يوليو" */
    public function getLabelAttribute(): string
    {
        return app()->getLocale() === 'ar'
            ? self::arabicDate($this->event_date)
            : $this->event_date->format('l j F');
    }

    /** "6:00 PM – 12:00 AM" */
    public function getTimeRangeAttribute(): string
    {
        return $this->starts_at->format('g:i A').' – '.$this->ends_at->format('g:i A');
    }

    public function hasFinished(): bool
    {
        return $this->ends_at->isPast();
    }

    protected function activityDescription(string $event): string
    {
        return 'Bazaar night '.$this->event_date?->format('Y-m-d').' '.$event;
    }
}
