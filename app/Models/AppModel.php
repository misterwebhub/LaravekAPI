<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AppModel extends Model
{
    public static $currentUser;

    public static function setCurrentUser(User $user)
    {
        static::$currentUser = $user;
    }

    public static function getCurrentUser()
    {
        return static::$currentUser;
    }
}
