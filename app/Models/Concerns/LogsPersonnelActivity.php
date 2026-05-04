<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;

trait LogsPersonnelActivity
{
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('personnel')
            ->logOnly($this->fillable ?? [])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => sprintf('%s %s', $eventName, class_basename($this)));
    }
}
