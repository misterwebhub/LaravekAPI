<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Approval extends AppModel
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    public const TYPE_SINGLE = 1;
    public const TYPE_BULK = 2;
    public const TYPE_CENTRE_MODEL = "Centre";
    public const TYPE_ORGANISATION_MODEL = "Organisation";
    public const TYPE_LEARNER_MODEL = "User";
    public const TYPE_FACILITATOR_MODEL = "User";
    public const TYPE_NEEDS_APPROVAL = 0;
    public const TYPE_APPROVED = 1;
    public const TYPE_REJECTED = 2;
    public const TYPE_TO_REJECTED = 0;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Approval {$eventName}")
            ->useLogName('Approval')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }

    /**
     * Get the reference user.
     */
    public function referenceUser()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
