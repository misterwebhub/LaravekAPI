<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Community extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at',
        'tenant_id'
    ];
}