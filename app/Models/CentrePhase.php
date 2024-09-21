<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CentrePhase extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = "centre_phase";
}
