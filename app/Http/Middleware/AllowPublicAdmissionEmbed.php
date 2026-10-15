<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowPublicAdmissionEmbed
{
    // SameSite=None so session/XSRF cookies survive when this form is iframe-embedded on another site; scoped to this request only, other guards keep SameSite=Lax.
    //
    // Also uses its OWN session cookie name, distinct from the app's normal session
    // cookie. Without this, a staff member with the admin dashboard open in one tab
    // and a public page (this group) open in another — e.g. previewing an embedded
    // form, or a student using it on the institute's own website while support staff
    // are logged into the dashboard on the same machine — would silently share one
    // browser cookie jar entry: the guest page's Set-Cookie response overwrites the
    // admin tab's session cookie, and the admin's next request gets treated as
    // unauthenticated ("Session Expired"). A separate cookie name keeps the two
    // entirely isolated, since both requests hit the same host/cookie jar
    // regardless of which page (or iframe parent) the request came from.
    public function handle(Request $request, Closure $next): Response
    {
        $guestCookieName = config('session.cookie') . '_guest';

        config([
            'session.same_site' => 'none',
            'session.secure'    => true,
            'session.cookie'    => $guestCookieName,
        ]);

        // StartSession (global 'web' middleware) already loaded a session before this
        // route middleware ran, using whatever cookie the browser sent under the
        // app's normal cookie name — which could be someone else's authenticated
        // session sharing this browser. If our dedicated guest cookie wasn't present,
        // this is this browser's first hit on a public page: start clean rather than
        // continue with borrowed session data.
        //
        // flush() + regenerate(false), NOT invalidate(): invalidate() regenerates with
        // destroy=true, which deletes the borrowed session row from the DB — that row is
        // the admin's real login session, so merely opening a public page (admission
        // form / fee balance) in the same browser used to log the admin out everywhere.
        // regenerate(false) just moves this request to a new id and leaves the old row intact.
        //
        // The Store was built with the normal cookie name before the config override above,
        // so StartSession would still READ the admin's id and WRITE the response cookie under
        // the normal name — overwriting the admin's cookie with this guest session's id (the
        // real cause of the "Session Expired" logout). Rename the store to the guest cookie,
        // and load the guest session by its own id.
        $session = $request->session();
        $session->setName($guestCookieName);
        $session->flush();

        $guestId = $request->cookies->get($guestCookieName);
        if ($session->isValidId($guestId)) {
            $session->setId($guestId);
            $session->start();
        } else {
            $session->regenerate(false);
        }

        return $next($request);
    }
}
