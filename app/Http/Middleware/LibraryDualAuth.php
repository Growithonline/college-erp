<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Accepts either the institute admin guard (web) OR the library_staff guard.
 * Applied on all /library/* routes so both admin and dedicated library staff can use them.
 */
class LibraryDualAuth
{
    public function handle(Request $request, Closure $next): mixed
    {
        // ── 1. Institute admin ───────────────────────────────────
        if (Auth::check()) {
            $user = Auth::user();

            if (isset($user->status) && !$user->status) {
                Auth::logout();
                return redirect()->route('login')->with('error', 'Your account has been disabled.');
            }

            view()->share('authUser',  $user);
            view()->share('authGuard', 'web');

            return $next($request);
        }

        // ── 2. Dedicated library staff ───────────────────────────
        $guard = Auth::guard('library_staff');

        if ($guard->check()) {
            $staff = $guard->user();

            if (!$staff->status) {
                $guard->logout();
                return redirect()->route('library_staff.login')
                    ->with('error', 'Your account has been deactivated. Please contact the administrator.');
            }

            if ($staff->isLocked()) {
                $guard->logout();
                return redirect()->route('library_staff.login')
                    ->with('error', 'Your account is temporarily locked.');
            }

            view()->share('authUser',  $staff);
            view()->share('authGuard', 'library_staff');

            return $next($request);
        }

        // ── 3. Not authenticated — send to appropriate login ──────
        // If coming from a library-staff session path, send to library staff login
        session(['url.intended' => $request->url()]);

        return redirect()->route('library_staff.login')
            ->with('error', 'Please login to continue.');
    }
}
