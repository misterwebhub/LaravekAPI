<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MqopsActivityMedium extends Model
{
    use HasFactory;
    use Uuids;

    public $timestamps = false;
    protected $table = "mqops_activity_mediums";
}
