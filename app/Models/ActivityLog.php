<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends \Spatie\Activitylog\Models\Activity
{
    use HasFactory;
    
    public function user(){
        return $this->belongsTo('\App\Models\User', 'causer_id'); //arg1 - Model, arg2 - foreign key
    }
}
