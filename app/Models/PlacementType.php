<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;

class PlacementType extends Model
{
    use HasFactory;
    use Uuids;

    public const TYPE1 = 0;
    public const TYPE2 = 1;
    public const TYPE3 = 2;
}
