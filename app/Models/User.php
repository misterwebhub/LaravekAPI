<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Carbon;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use HasApiTokens;
    use Uuids;
    use HasRoles;
    use HasPermissions;
    use SoftDeletes;
    use LogsActivity;

    public const TYPE_ADMIN = 1;
    public const TYPE_FACILITATOR = 2;
    public const TYPE_STUDENT = 3;
    public const TYPE_ALUMNI = 4;
    public const CREATED_PLATFORM_ADMIN = 1;
    public const CREATED_PLATFORM_WEBAPP = 4;
    public const CREATED_PLATFORM_MOBILEAPP = 3;
    public const CREATED_PLATFORM_CENTREADMIN = 7;
    public const CREATED_PLATFORM_OFFLINE_APP = 5;
    public const CREATED_PLATFORM_CENTRE_ADMIN = 6;
    public const INACTIVE_STATUS = 0;
    public const ACTIVE_STATUS = 1;
    public const PASSWORD_COUNT = 6;
    public const TYPE_QUEST_ALLIANCE_EMPLOYEE = 1;
    public const MASTER_TRAINER = 1;
    public const NOT_MASTER_TRAINER = 0;
    public const SUPER_FACILITATOR = 1;
    public const NOT_SUPER_FACILITATOR = 0;
    public const API_SUCCESS_STATUS = 1;
    public const DEVICE_TYPE1 = "browser";
    public const DEVICE_TYPE2 = "mobile";
    public const TYPE_IS_APPROVED = 1;
    public const TYPE_NOT_APPROVED = 0;
    public const CHECK_EMAIL_IS_VALID = "Valid";
    public const CHECK_EMAIL_NOT_VALID = "invalid";
    public const LEARNER_PASSWORD = "learner123";
    public const FACILITATOR_PASSWORD = "password123";
    public const IS_QUEST_EMPLOYEE = 1;
    public const COUNTRY_PHONE_CODE = "91";
    public const FILTER_TYPE_ONE = 1;
    public const FILTER_TYPE_ZERO = 0;
    public const FILTER_TYPE_TWO = 2;
    public const FILTER_TYPE_THREE = 3;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "User {$eventName}")
            ->useLogName('User')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }
    /**
     * Get the centre details of User.
     */
    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }

    /**
     * Get the details of User.
     */
    public function details($type)
    {
        if (in_array($type, [User::TYPE_STUDENT, User::TYPE_ALUMNI])) { //If Student or Alumni
            return $this->belongsTo(StudentDetail::class);
        }
        if (in_array($type, [User::TYPE_FACILITATOR])) { //If Facilitator
            return $this->belongsTo(FacilitatorDetail::class);
        }
    }

    /**
     * The centres that belong to the super facilitator.
     */
    public function centres()
    {
        return $this->belongsToMany(Centre::class, 'centre_user');
    }

    /**
     * Get the organisation details of User.
     */
    public function organisation()
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Get the facilitator details of User.
     */
    public function facilitatorDetail()
    {
        return $this->hasOne(FacilitatorDetail::class);
    }

    /**
     * Get the student details of User.
     */
    public function studentDetail()
    {
        return $this->hasOne(StudentDetail::class);
    }

    public function scopeStartDate(Builder $query, $startDate): Builder
    {
        if ($startDate != "") {
            $query->where('users.created_at', '>=', $startDate);
        }

        return $query;
    }

    public function scopeEndDate(Builder $query, $endDate): Builder
    {
        if ($endDate != "") {
            $query->where('users.created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }
        return $query;
    }

    public function scopeProject(Builder $query, $project): Builder
    {
        return $query->whereIn('users.centre_id', Project::find($project)->centres->pluck('id')->toArray());
    }

    /**
     * Get the placement details of User.
     */
    public function placements()
    {
        return $this->hasMany(Placement::class);
    }

    /**
     * Get the program details of User.
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the project details of User.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    public function mqopsInternalMeeting()
    {
        return $this->belongsToMany(MqopsInternalMeeting::class, 'mqops_internal_meeting_user');
    }
    /**
     * Get the mqopsExternalMeeting of User.
     */
    public function mqopsExternalMeetings()
    {
        return $this->belongsToMany(MqopsExternalMeeting::class, 'mqops_external_meeting_user');
    }

    /**
     * Get the search filter for student and facilitator.
     */
    public function scopeName(Builder $query, $value): Builder
    {
        return $query
            ->where('name', 'LIKE', '%' . $value . '%')
            ->orWhere('email', 'LIKE', '%' . $value . '%')
            ->orWhere('mobile', 'LIKE', '%' . $value . '%')
            ->orWhereHas('organisation', function ($q) use ($value) {
                $q->where('name', 'LIKE', '%' . $value . '%');
            });
    }

    /**
     * Get the learning activities of User.
     */
    public function activity()
    {
        return $this->hasMany(LearningActivity::class);
    }

    /**
     * Get the type of User.
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function masterTrainerUsers()
    {
        return $this->belongsToMany(User::class, 'master_trainer_user', 'master_trainer_id', 'user_id');
    }


    /**
     * Get the student details of User.
     */
    public function phaseUsers()
    {
        return $this->belongsToMany(User::class, 'phase_users');
    }

    /**
     * Get the student details of User.
     */
    public function phases()
    {
        return $this->belongsToMany(Phase::class, 'phase_users');
    }

}
