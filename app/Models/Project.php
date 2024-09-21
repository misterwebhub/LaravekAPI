<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Project extends AppModel
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
        'tenant_id',
        'status',
        'updated_by',
        'deleted_by'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Project {$eventName}")
            ->useLogName('Project')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }
    /**
     * @return [type]
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function centres()
    {
        return $this->belongsToMany(Centre::class, 'centre_project');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class);
    }
    public function phases()
    {
        return $this->belongsToMany(Phase::class, 'phase_project')->select('id', 'name', 'start_date', 'end_date');
    }
}
