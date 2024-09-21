<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Organisation extends AppModel
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    public const TYPE_IS_APPROVED = 1;
    public const TYPE_NOT_APPROVED = 0;

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
            ->setDescriptionForEvent(fn (string $eventName) => "Organisation {$eventName}")
            ->useLogName('Organisation')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }
    /**
     * State of the Org.
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    /**
     * District of the Org.
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function program()
    {
        return $this->belongsToMany(Program::class, 'organisation_program');
    }

    public function centres()
    {
        return $this->hasMany(Centre::class);
    }
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
