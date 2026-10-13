<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-route framing policy.
 *   frame:allow — cross-origin framing permitted (ONLY the credential-free embed launcher)
 *   frame:same  — SAMEORIGIN (scoped login pages)
 */
class FrameOptions
{
    public function handle(Request $request, Closure $next, string $mode = 'same'): Response
    {
        $response = $next($request);

        if ($mode === 'allow') {
            $response->headers->remove('X-Frame-Options');
            $response->headers->set('Content-Security-Policy', 'frame-ancestors *');
        } else {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        return $response;
    }
}
