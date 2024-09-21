<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Subject extends AppModel
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    public const IN_ACTIVE = 0;
    public const ACTIVE = 1;


    protected $hidden = [
        'created_by',
        'deleted_at',
        'created_at',
        'updated_at',
        'tenant_id'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Subject {$eventName}")
            ->useLogName('Subject')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }
    /**
     * Courses corresponding to the subject.
     */
    public function courses()
    {
        return $this->hasMany(Course::class)->orderBy('name');
    }
    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function centres()
    {
        return $this->belongsToMany(Centre::class)->withPivot('order');
    }

    public function batches()
    {
        return $this->belongsToMany(Batch::class, 'batch_subject');
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    /**
     * Resource corresponding to the subject.
     */
    public function resources()
    {
        return $this->hasMany(Resource::class)->orderBy('name');
    }

    public function subject_user()
    {
        return $this->belongsTo(User::class, 'created_by'); 
   }

    public function subjectlogs()
    {
        return $this->hasOne(SubjectLogs::class)->with('user'); 
    }
     public function lastSentonReview()
    {
        return $this->hasOne(SubjectLogs::class)->with('user'); 
    }
    
      public function phases()
    {
        return $this->belongsToMany(Phase::class, 'phase_subject', 'subject_id', 'phase_id');
    }

    public function organisations()
    {
        return $this->belongsToMany(Organisation::class);
    }
}
