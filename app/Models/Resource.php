<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Resource extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    public const STATUS_ACTIVE = 1;
    public const STATUS_DELETED = 4;
    public const STATUS_INACTIVE = 0;
    /* The attributes that are mass assignable.
     *
     * @var array
     *
     */
    protected $fillable = [
        'name', 'category', 'link', 'point', 'status', 'course_id', 'subject_id', 'tenant_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Resource {$eventName}")
            ->useLogName('Resource')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }

    /**
     * @return mixed
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return mixed
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return mixed
     */
    public function resourceCategory()
    {
        return $this->belongsTo(ResourceCategory::class);
    }
}
