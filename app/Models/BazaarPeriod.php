<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * What a vendor actually buys. Today one period is a weekend — Thursday plus
 * Friday, sold together for a single fee — but the model deliberately says
 * "period" rather than "weekend" so single days or longer runs can be sold
 * later without a rewrite.
 */
class BazaarPeriod extends Model
{
    use \App\Concerns\ArabicDateNames;
    use \App\Concerns\LogsActivity;

    protected $fillable = [
        'starts_on',
        'ends_on',
        'position',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function nights(): HasMany
    {
        return $this->hasMany(BazaarNight::class)->orderBy('event_date');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(BazaarBooking::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Periods a vendor can still book — a weekend stays open until its last night is over. */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereDate('ends_on', '>=', now()->toDateString());
    }

    /** "Thu 23 – Fri 24 July" / "الخميس 23 – الجمعة 24 يوليو" */
    public function getLabelAttribute(): string
    {
        $from = $this->starts_on;
        $to = $this->ends_on;

        if (app()->getLocale() === 'ar') {
            return self::arabicWeekday($from).' '.$from->format('j').' – '
                .self::arabicWeekday($to).' '.$to->format('j').' '.self::arabicMonth($to);
        }

        return $from->format('D j').' – '.$to->format('D j F');
    }

    /** "23–24 July" — for tighter spaces like table columns. */
    public function getShortLabelAttribute(): string
    {
        if (app()->getLocale() === 'ar') {
            return $this->starts_on->format('j').'–'.$this->ends_on->format('j').' '
                .self::arabicMonth($this->ends_on);
        }

        return $this->starts_on->format('j').'–'.$this->ends_on->format('j F');
    }

    public function hasFinished(): bool
    {
        return $this->ends_on->endOfDay()->isPast();
    }

    /** Tables held on this period — pending and confirmed both hold. */
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
        return 'Bazaar period '.$this->starts_on?->format('Y-m-d').' '.$event;
    }
}
