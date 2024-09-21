<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MqopsCentreVisit extends AppModel
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
        'tenant_id',
        'pivot',
    ];

    public const TYPE_NGO = 'ngo';
    public const TYPE_ITI = 'iti';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "MqopsCentreVisit {$eventName}")
            ->useLogName('MqopsCentreVisit')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }

    /**
     * Get the users.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'mqops_centre_visit_user', 'mqops_centre_visit_id')
            ->select(['id', 'name']);
    }

    /**
     * Get the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
     * Get the district.
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Get the centre type.
     */
    public function centreType()
    {
        return $this->belongsTo(CentreType::class);
    }

    /**
     * Get assigned centres
     * @return [type]
     */
    public function centres()
    {
        return $this->belongsToMany(Centre::class, 'centre_mqops_centre_visit', 'mqops_centre_visit_id')
            ->select(['id', 'name']);
    }
}
