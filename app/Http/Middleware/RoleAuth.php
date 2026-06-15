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
            // Store intended URL
            session(['url.intended' => $request->url()]);

            return redirect()->route("{$guard}.login")
                ->with('error', 'Please login to continue.');
        }

        // Status check — disabled users ko block karo
        $user = Auth::guard($guard)->user();
        if (isset($user->status) && !$user->status) {
            Auth::guard($guard)->logout();
            return redirect()->route("{$guard}.login")
                ->with('error', 'Your account has been disabled. Please contact admin.');
        }

        // Share guard user with all views
        view()->share('authUser',  $user);
        view()->share('authGuard', $guard);

        return $next($request);
    }
}