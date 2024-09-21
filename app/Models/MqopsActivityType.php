<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MqopsActivityType extends Model
{
    use HasFactory;
    use Uuids;

    public $timestamps = false;
    protected $table = "mqops_activity_types";
}
