<?php


namespace App\Models;


use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Model;

use App\Traits\Uuids;


class ResourceCategory extends Model

{

    use Notifiable;
    use Uuids;
}