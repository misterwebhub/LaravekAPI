<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MqopsDocument extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    public $timestamps = false;
    protected $table = "mqops_documents";

    public const MQOPS_ACTIVITY = 1;
    public const MQOPS_SESSION = 5;
    public const MQOPS_TOT = 6;
    public const MQOPS_CENTRE_VISIT = 2;
    public const TYPE_EXTERNAL_MEETING = 4;
    public const TYPE_INTERNAL_MEETING = 3;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "MqopsDocument {$eventName}")
            ->useLogName('MqopsDocument')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }
}
