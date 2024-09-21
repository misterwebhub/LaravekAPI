<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MqopsInternalMeetingUser extends Model
{
    use HasFactory;
    public function internal()
    {
        return $this->belongsToMany(InternalMeeting::class);
    }

} 