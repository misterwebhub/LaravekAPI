<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MqopsTotSummaryProject extends Model
{
    use HasFactory;
    use Uuids;

    public $timestamps = false;
    protected $table = "mqops_tot_summary_project";

    public function project()
    {
        return $this->belongsTo(Project::class);
    }


    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
