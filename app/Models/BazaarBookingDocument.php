<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A certificate or licence attached to a booking.
 *
 * Files live on the PRIVATE local disk, never under public/ — they carry
 * personal and business data (national IDs, health clearances), so they are
 * only ever streamed out through an authenticated admin route.
 */
class BazaarBookingDocument extends Model
{
    public const KIND_WORK = 'work';

    public const KIND_HEALTH = 'health';

    public const KINDS = [
        self::KIND_WORK => 'Work / trade licence',
        self::KIND_HEALTH => 'Health certificate',
    ];

    /** Where uploads land on the local (private) disk. */
    public const DIRECTORY = 'bazaar-documents';

    protected $fillable = [
        'bazaar_booking_id',
        'kind',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Don't leave orphaned files behind when a booking is deleted.
        static::deleting(function (self $document): void {
            try {
                Storage::disk('local')->delete($document->path);
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(BazaarBooking::class, 'bazaar_booking_id');
    }

    public function getKindLabelAttribute(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function getSizeForHumansAttribute(): string
    {
        $kb = $this->size / 1024;

        return $kb >= 1024
            ? round($kb / 1024, 1).' MB'
            : max(1, round($kb)).' KB';
    }

    public function exists(): bool
    {
        return Storage::disk('local')->exists($this->path);
    }
}
