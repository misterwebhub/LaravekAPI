<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Role extends \Spatie\Permission\Models\Role
{
    use HasFactory;
    use LogsActivity;

    public const TYPE_STATUS = 1;
    public const ACTIVE_STATUS = 1;
    public const INACTIVE_STATUS = 0;
    public const IS_ADMIN = 1;
    public const IS_NOT_ADMIN = 0;
    public const ADMINTYPE = 2;
    public const GUARD_NAME = 'web';
    public const TYPE_ADMIN = 1;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
        'guard_name',
        'created_at',
        'updated_at'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->setDescriptionForEvent(fn (string $eventName) => "Role {$eventName}")
            ->useLogName('Role')
            ->logOnlyDirty();
        // Chain fluent methods for configuration options
    }
}
