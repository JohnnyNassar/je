<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BazaarNight extends Model
{
    use \App\Concerns\LogsActivity;

    protected $fillable = [
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

    // The bazaar only ever runs on Thursdays and Fridays, so a small map beats
    // pulling in a full localised date formatter.
    private const WEEKDAYS_AR = [
        'Thursday' => 'الخميس',
        'Friday' => 'الجمعة',
    ];

    private const MONTHS_AR = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(BazaarBooking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Nights a vendor can still book — today counts until it has finished. */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('event_date', '>=', now()->toDateString());
    }

    /** "Thursday 23 July" / "الخميس 23 يوليو" — locale-aware, like Product::name. */
    public function getLabelAttribute(): string
    {
        $date = $this->event_date;

        if (app()->getLocale() === 'ar') {
            $day = self::WEEKDAYS_AR[$date->format('l')] ?? $date->format('l');

            return $day.' '.$date->format('j').' '.(self::MONTHS_AR[(int) $date->format('n')] ?? '');
        }

        return $date->format('l j F');
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

    /** Table numbers taken on this night (pending + confirmed both hold the slot). */
    public function bookedTableIds(): array
    {
        return $this->bookings()
            ->whereNot('status', BazaarBooking::STATUS_CANCELLED)
            ->pluck('bazaar_table_id')
            ->all();
    }

    public function bookedCount(): int
    {
        return count($this->bookedTableIds());
    }

    public function availableCount(): int
    {
        return max(0, BazaarTable::bookable()->count() - $this->bookedCount());
    }

    public function isSoldOut(): bool
    {
        return $this->availableCount() === 0;
    }

    protected function activityDescription(string $event): string
    {
        return 'Bazaar night '.$this->event_date?->format('Y-m-d').' '.$event;
    }
}
