<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubjectLogs extends Model
{
    use HasFactory;
    protected $table="subject_logs";

    public function user()
    {
        return $this->belongsTo(User::class,'logged_user_id');
    }

}
