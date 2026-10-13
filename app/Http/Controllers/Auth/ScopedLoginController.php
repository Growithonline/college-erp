<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\LoginScope;

class ScopedLoginController extends Controller
{
    /** GET /{portal}/login/{slug} — stores the scope server-side, then renders that portal's normal login view. */
    public function show(string $slug, string $portal)
    {
        abort_unless(LoginScope::isPortal($portal), 404);

        $institute = LoginScope::findActiveInstitute($slug);
        abort_unless($institute, 404);

        LoginScope::begin($institute, $portal);

        return view(LoginScope::portals()[$portal]['view']);
    }

    /** GET /embed/login/{slug}/{portal} — credential-free, session-free launcher shown inside third-party iframes. */
    public function launcher(string $slug, string $portal)
    {
        abort_unless(LoginScope::isPortal($portal), 404);

        $institute = LoginScope::findActiveInstitute($slug);
        abort_unless($institute, 404);

        return view('embed.login-launcher', [
            'institute'   => $institute,
            'logoUrl'     => LoginScope::logoUrl($institute),
            'portalLabel' => LoginScope::portals()[$portal]['label'],
            'loginUrl'    => LoginScope::loginUrl($portal, $institute),
        ]);
    }
}
