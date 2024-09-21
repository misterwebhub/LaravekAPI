<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Phase extends AppModel
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    protected $dates = [
        'start_date',
        'end_date'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Phase {$eventName}")
            ->useLogName('Phase')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'phase_project');
    }
    public function phases()
    {
        return $this->belongsToMany(Centre::class, 'centre_phase');
    }
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'phase_subject');
    }
    public function centres()
    {
        return $this->belongsToMany(Centre::class, 'centre_phase');
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'phase_users')
            ->withPivot('centre_id')
            ->withTimestamps();
    }
}
