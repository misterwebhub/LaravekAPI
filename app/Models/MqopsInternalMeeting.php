<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AppModel;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MqopsInternalMeeting extends AppModel
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    protected $dates = ['start_date', 'end_date'];

    protected $fillable = [
        'id', 'start_date', 'end_date', 'state_id',
    ];
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "MqopsInternalMeeting {$eventName}")
            ->useLogName('MqopsInternalMeeting')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }
    public function teamMembers()
    {
        return $this->belongsToMany(User::class, 'mqops_internal_meeting_user');
    }
    /**
     * Get the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    /**
     * Get the mqopsDocument.
     */
    public function mqopsDocument()
    {
        return $this->hasMany(MqopsDocument::class, 'parent_id');
    }
}
