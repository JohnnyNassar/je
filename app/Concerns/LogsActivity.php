<?php

namespace App\Concerns;

use App\Models\ActivityLog;
use Illuminate\Support\Arr;

/**
 * Records create / update / delete on a model to the activity_logs table —
 * but only when a logged-in admin/staff user (web guard) performed the change,
 * so the log is a clean accountability trail of deliberate admin actions
 * rather than a firehose of storefront/customer-driven writes.
 *
 * Models may override activityDescription() and tweakActivityProperties()
 * (e.g. Setting redacts secret values).
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => $model->recordActivity('created'));
        static::updated(fn ($model) => $model->recordActivity('updated'));
        static::deleted(fn ($model) => $model->recordActivity('deleted'));
    }

    protected function recordActivity(string $event): void
    {
        $causer = auth('web')->user(); // admin/staff only
        if (! $causer) {
            return;
        }

        $hide = array_merge(
            ['password', 'remember_token', 'created_at', 'updated_at'],
            property_exists($this, 'activityExcept') ? $this->activityExcept : []
        );

        if ($event === 'updated') {
            $changed = array_values(array_diff(array_keys($this->getChanges()), $hide));
            if (empty($changed)) {
                return;
            }
            $properties = [
                'old' => Arr::only($this->getOriginal(), $changed),
                'attributes' => Arr::only($this->getAttributes(), $changed),
            ];
        } else {
            $properties = ['attributes' => Arr::except($this->getAttributes(), $hide)];
        }

        $properties = $this->tweakActivityProperties($properties);

        try {
            ActivityLog::create([
                'log_name' => class_basename($this),
                'event' => $event,
                'description' => $this->activityDescription($event),
                'subject_type' => $this->getMorphClass(),
                'subject_id' => $this->getKey(),
                'causer_type' => $causer->getMorphClass(),
                'causer_id' => $causer->getKey(),
                'properties' => $properties,
            ]);
        } catch (\Throwable $e) {
            // Auditing must never break the underlying write.
            report($e);
        }
    }

    protected function activityDescription(string $event): string
    {
        return class_basename($this) . ' ' . $event;
    }

    protected function tweakActivityProperties(array $properties): array
    {
        return $properties;
    }
}
