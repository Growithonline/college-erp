<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstituteFinanceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->guard('web')->check()) {
            abort(403, 'Finance module access denied.');
        }

        $user = auth()->guard('web')->user();
        if (!$user || !$user->institute_id) {
            abort(403, 'Institute finance context missing.');
        }

        return $next($request);
    }
}
