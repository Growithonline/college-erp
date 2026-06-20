<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleAuth
{
    /**
     * Handle an incoming request.
     * Usage: middleware('role.auth:center')
     *        middleware('role.auth:staff')
     *        middleware('role.auth:partner')
     */
    public function handle(Request $request, Closure $next, string $guard): mixed
    {
        if (!Auth::guard($guard)->check()) {
            session(['url.intended' => $request->url()]);
            return redirect()->route('session.expired', ['guard' => $guard, 'reason' => 'unauthenticated']);
        }

        // Status check — disabled users ko block karo
        $user = Auth::guard($guard)->user();
        if (isset($user->status) && !$user->status) {
            Auth::guard($guard)->logout();
            return redirect()->route('session.expired', ['guard' => $guard, 'reason' => 'disabled']);
        }

        // Share guard user with all views
        view()->share('authUser',  $user);
        view()->share('authGuard', $guard);

        return $next($request);
    }
}