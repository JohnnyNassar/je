<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'bazaar_period_id',
        'bazaar_table_id',
        'customer_id',
        'bazaar_vendor_category_id',
        'vendor_name',
        'vendor_phone',
        'vendor_business',
        'goods_description',
        'price',
        'deposit',
        'deposit_returned_at',
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
            'deposit' => 'decimal:2',
            'deposit_returned_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // `active_slot` backs the unique index that makes double-booking
        // impossible at the database level. It holds 1 while the booking
        // occupies the table and NULL once cancelled -- MariaDB treats NULLs as
        // distinct, so cancelled bookings stay on record without blocking a
        // rebooking of the same table for the same period.
        static::saving(function (self $booking): void {
            $booking->active_slot = $booking->status === self::STATUS_CANCELLED ? null : 1;
        });
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(BazaarPeriod::class, 'bazaar_period_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(BazaarTable::class, 'bazaar_table_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BazaarVendorCategory::class, 'bazaar_vendor_category_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BazaarBookingDocument::class);
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

    /** Fee plus the refundable deposit — what the vendor actually hands over. */
    public function getTotalDueAttribute(): float
    {
        return (float) $this->price + (float) $this->deposit;
    }

    public function documentOf(string $kind): ?BazaarBookingDocument
    {
        return $this->documents->firstWhere('kind', $kind);
    }

    /** A vendor may attach several of each kind — a licence and its renewal, say. */
    public function documentsOf(string $kind): \Illuminate\Support\Collection
    {
        return $this->documents->where('kind', $kind)->values();
    }

    /** ["Health certificate" => 2, "Work / trade licence" => 1] */
    public function documentSummary(): array
    {
        return $this->documents
            ->groupBy('kind')
            ->mapWithKeys(fn ($group, $kind) => [
                BazaarBookingDocument::KINDS[$kind] ?? $kind => $group->count(),
            ])
            ->all();
    }

    /** True when the category demands a health certificate and none is attached. */
    public function isMissingHealthCertificate(): bool
    {
        if (! $this->category?->requires_health_certificate) {
            return false;
        }

        return ! $this->documents()
            ->where('kind', BazaarBookingDocument::KIND_HEALTH)
            ->exists();
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
