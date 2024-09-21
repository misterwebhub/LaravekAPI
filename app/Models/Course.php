<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Course extends AppModel
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    protected $hidden = [
        'created_by',
        'deleted_at',
        'created_at',
        'updated_at',
        'tenant_id'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Course {$eventName}")
            ->useLogName('Course')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }
    /**
     * @return [type]
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Resource corresponding to the subject.
     */
    public function resources()
    {
        return $this->hasMany(Resource::class)->orderBy('name');
    }
}
