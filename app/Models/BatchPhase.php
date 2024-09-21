<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatchPhase extends Model
{
    use Notifiable;
    use SoftDeletes;

    protected $table = "batch_phase";
    public $timestamps = true;
}
