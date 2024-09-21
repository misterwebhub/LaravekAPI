<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class AutoLogout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $status = $request->user()->status;
        if ($status == User::INACTIVE_STATUS) {
            auth()->user()->tokens()->delete();
            return response()->json(['message' => trans('admin.unauthenticated')], 403);
        }
        return $next($request);
    }
}
