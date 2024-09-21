<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\AppModel;

class Centre extends AppModel
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    public const TYPE_CONFIGURE_BATCH_END_DATE = 1;
    public const TYPE_CONFIGURE_YEAR_OF_REGISTRATION = 2;
    public const TYPE_BATCH_DISABLED = 0;
    public const TYPE_BATCH_ENABLED = 1;
    public const TYPE_IS_ANYWHERE_LEARNING = 1;
    public const TYPE_IS_APPROVED = 1;
    public const TYPE_NOT_APPROVED = 0;
    public const ACTIVE_STATUS = 1;
    public const INACTIVE_STATUS = 0;


    protected $hidden = [
        'created_by',
        'deleted_at',
        'created_at',
        'updated_at',
        'tenant_id',
        'pivot'
    ];

    protected $dates = [
        'synced_at'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Centre {$eventName}")
            ->useLogName('Centre')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }
    /**
     * Get the students of the centre.
     */
    public function students()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the alumnis of the centre.
     */
    public function alumnis()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the facilitators of the centre.
     */
    public function facilitators()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the centre type.
     */
    public function centreType()
    {
        return $this->belongsTo(CentreType::class);
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
     * Get the organisation.
     */
    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Get assigned projects
     * @return [type]
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'centre_project');
    }
    public function subjects()
    {
        return $this->belongsToMany(Subject::class)->withPivot('order');
    }
    public function phases()
    {
        return $this->belongsToMany(Phase::class, 'centre_phase')->whereNull('centre_phase.deleted_at');
    }
    /**
     * Get centre user
     * @return [type]
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the partnershipType.
     */
    public function partnershipType()
    {
        return $this->belongsTo(PartnershipType::class);
    }

    /**
     * Get the batches of the centre.
     */
    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    /**
     * Get the centre heads of the centre.
     */
    public function centreHeads()
    {
        return $this->hasMany(User::class)->where('type', User::TYPE_ADMIN);
    }
}
