<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    use HasFactory;
    use Uuids;
    use LogsActivity;

    public const COURSE_BADGE = 1;
    public const COMMUNITY_BADGE = 2;
    public const RESOURCE_BADGE = 3;
    public const ACTIVITY_BADGE_TYPE = 1;
    public const PERFORMANCE_BADGE_TYPE = 2;
    public $timestamps = false;
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Badge {$eventName}")
            ->useLogName('Badge')
            ->logOnlyDirty();
    }
}
