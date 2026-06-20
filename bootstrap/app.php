<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Phase 7: Role-based auth middleware
        $middleware->alias([
            'role.auth'          => \App\Http\Middleware\RoleAuth::class,
            'finance.access'     => \App\Http\Middleware\EnsureInstituteFinanceAccess::class,
            'lib.dual.auth'      => \App\Http\Middleware\LibraryDualAuth::class,
            'lib.staff.session'  => \App\Http\Middleware\LibraryStaffSession::class,
            'center.wallet'      => \App\Http\Middleware\CheckCenterWallet::class,
            'channel.wallet'     => \App\Http\Middleware\CheckChannelWallet::class,
        ]);

        // Redirect guests to session-expired page (shows portal-specific message & login button)
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('super-admin/*')) return route('session.expired', ['guard' => 'super_admin', 'reason' => 'unauthenticated']);
            if ($request->is('center/*'))      return route('session.expired', ['guard' => 'center',      'reason' => 'unauthenticated']);
            if ($request->is('staff/*'))       return route('session.expired', ['guard' => 'staff',       'reason' => 'unauthenticated']);
            if ($request->is('partner/*'))     return route('session.expired', ['guard' => 'partner',     'reason' => 'unauthenticated']);
            if ($request->is('student/*'))     return route('session.expired', ['guard' => 'student',     'reason' => 'unauthenticated']);
            return route('session.expired', ['guard' => 'web', 'reason' => 'unauthenticated']);
        });

        // Keep each panel on its own dashboard when an already-authenticated user
        // visits a guest-only login page like /staff/login.
        $middleware->redirectUsersTo(function ($request) {
            if ($request->is('center/*') && Auth::guard('center')->check()) {
                return route('center.dashboard');
            }

            if ($request->is('staff/*') && Auth::guard('staff')->check()) {
                return route('staff.dashboard');
            }

            if ($request->is('partner/*') && Auth::guard('partner')->check()) {
                return route('partner.dashboard');
            }

            if (Auth::guard('web')->check()) {
                return route('institute.dashboard');
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
