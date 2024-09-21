<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class MasterTrainerUser extends Model
{
    use HasFactory;
    use Notifiable;

    protected $table = "master_trainer_user";
}
