<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowPublicAdmissionEmbed
{
    // SameSite=None so session/XSRF cookies survive when this form is iframe-embedded on another site; scoped to this request only, other guards keep SameSite=Lax.
    public function handle(Request $request, Closure $next): Response
    {
        config(['session.same_site' => 'none', 'session.secure' => true]);

        return $next($request);
    }
}
