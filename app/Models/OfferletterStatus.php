<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;

class OfferletterStatus extends Model
{
    use HasFactory;
    use Uuids;

    protected $table = "offerletter_status";
}
