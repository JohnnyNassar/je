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
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function subject()
    {
        return $this->morphTo();
    }

    public function causer()
    {
        return $this->morphTo();
    }
}
