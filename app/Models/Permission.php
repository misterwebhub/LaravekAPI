<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends \Spatie\Permission\Models\Permission
{
    use HasFactory;

    public const PERMISSION_TYPE_ONE = 1;
    public const PERMISSION_CREATE = 1;
    public const PERMISSION_UPDATE = 2;
    public const PERMISSION_READ = 3;
    public const PERMISSION_DELETE = 4;
    public const PERMISSION_MISCELLANEOUS = 5;
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'tenant_id',
        'updated_at'
    ];
}
