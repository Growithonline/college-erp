<?php

namespace App\Http\Middleware;

use App\Models\InstitutePolicyAcceptance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsurePolicyAccepted
{
    /**
     * Blocks dashboard access for an institute's 'web' guard user until
     * every document in config('legal.documents') has been accepted at
     * its current version. Registered on the institute route group,
     * outside of (and before) the consent routes themselves.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::guard('web')->user();

        if ($user && !InstitutePolicyAcceptance::allAccepted($user->institute_id)) {
            return redirect()->route('institute.consent.show');
        }

        return $next($request);
    }
}
