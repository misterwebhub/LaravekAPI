<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;

class PlacementStatus extends Model
{
    use HasFactory;
    use Uuids;

    public $timestamps = false;
    protected $table = "placement_status";
    public const EMPLOYED_STATUS = 1;
    public const SELF_EMPLOYED_STATUS = 2;
    public const HIGHER_STUDIES_STATUS = 3;
    public const DROPOUT_STATUS = 5;
    public const APPRENTICESHIP_INTERNSHIP_STATUS = 6;
    public const NOT_WORKING_STATUS = 7;
    public const LOOKING_FOR_WORK_STATUS = 4;
}
