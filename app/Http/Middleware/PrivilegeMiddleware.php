<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrivilegeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $privilege
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $privilege)
    {
        $user = Auth::user();

        if ($user->hasRole('Super Admin') || $user->hasPrivilege($privilege)) {
            return $next($request);
        }

        return response()->json(['message' => 'This action is unauthorized.'], 403);
    }
}
