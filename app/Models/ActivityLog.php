<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An audit-log entry: a deliberate admin/staff action (model change or auth
 * event), who did it (causer), what it affected (subject), and before/after
 * values (properties). Written by the App\Concerns\LogsActivity trait and the
 * auth listeners in AppServiceProvider.
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'log_name',
        'event',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'ip_address',
        'user_agent',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    /**
     * Stamp every entry with the request origin (client IP + user agent) unless
     * a caller already supplied them. Skipped outside an HTTP request — e.g.
     * console/queue writes have no client IP — so nothing bogus is recorded.
     */
    protected static function booted(): void
    {
        static::creating(function (self $log) {
            $request = request();
            $ip = $request?->ip();
            if (! $ip) {
                return;
            }

            $log->ip_address ??= $ip;
            if ($log->user_agent === null && $request->userAgent()) {
                $log->user_agent = mb_substr($request->userAgent(), 0, 500);
            }
        });
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function causer()
    {
        return $this->morphTo();
    }
}
