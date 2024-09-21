<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MqopsExternalMeeting extends AppModel
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

    public const TYPE_OTHERS = 'Others';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "MqopsExternalMeeting {$eventName}")
            ->useLogName('MqopsExternalMeeting')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }

    /**
     * Get the users.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'mqops_external_meeting_user');
    }

    /**
     * Get the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the organisation.
     */
    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Get the mqopsDocument.
     */
    public function mqopsDocuments()
    {
        return $this->hasMany(MqopsDocument::class, 'parent_id');
    }

    /**
     * Get the state.
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    /**
     * Get the mqops partner type.
     */
    public function mqopsPartnerType()
    {
        return $this->belongsTo(MqopsPartnerType::class);
    }
}
