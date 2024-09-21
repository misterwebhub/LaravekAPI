<?php

namespace App\Http\Middleware;

use App\Models\AppModel;
use Closure;
use Illuminate\Support\Facades\Auth;

class SetCurrentUser
{

    public function handle($request, Closure $next)
    {

        if (Auth::user() != null) {
            AppModel::setCurrentUser(Auth::user());
        }        return $next($request);
    }
}
