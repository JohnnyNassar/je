<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BazaarBooking extends Model
{
    use \App\Concerns\LogsActivity;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected $fillable = [
        'bazaar_night_id',
        'bazaar_table_id',
        'customer_id',
        'vendor_name',
        'vendor_phone',
        'vendor_business',
        'goods_description',
        'price',
        'status',
        'notes',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // `active_slot` backs the unique index that makes double-booking
        // impossible at the database level. It holds 1 while the booking
        // occupies the table and NULL once cancelled -- MariaDB treats NULLs as
        // distinct, so cancelled bookings stay on record without blocking a
        // rebooking of the same table on the same night.
        static::saving(function (self $booking): void {
            $booking->active_slot = $booking->status === self::STATUS_CANCELLED ? null : 1;
        });
    }

    public function night(): BelongsTo
    {
        return $this->belongsTo(BazaarNight::class, 'bazaar_night_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(BazaarTable::class, 'bazaar_table_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** Bookings still holding a table — pending and confirmed both count. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNot('status', self::STATUS_CANCELLED);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * wa.me deep link for messaging the vendor, mirroring how the order
     * confirmation page links to the shop's own WhatsApp.
     *
     * Vendors type their number every which way (07…, +962…, 00962…), so
     * normalise to the international form wa.me expects: digits, no plus.
     */
    public function getWhatsappUrlAttribute(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->vendor_phone);

        if (blank($digits)) {
            return null;
        }

        $digits = match (true) {
            str_starts_with($digits, '00962') => substr($digits, 2),
            str_starts_with($digits, '962') => $digits,
            // Local form: 07XXXXXXXX -> 9627XXXXXXXX
            str_starts_with($digits, '0') => '962'.ltrim($digits, '0'),
            // Bare mobile without the trunk zero: 7XXXXXXXX
            strlen($digits) === 9 && str_starts_with($digits, '7') => '962'.$digits,
            default => $digits,
        };

        return strlen($digits) >= 10 ? 'https://wa.me/'.$digits : null;
    }

    protected function activityDescription(string $event): string
    {
        return 'Bazaar booking #'.$this->id.' '.$event;
    }
}
