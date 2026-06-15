<?php

namespace App\Http\Middleware;

use App\Models\LibraryLoginLog;
use App\Models\LibraryStaffActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LibraryStaffSession
{
    // Inactivity limit in minutes (8 hours)
    private const INACTIVITY_LIMIT = 480;

    public function handle(Request $request, Closure $next): mixed
    {
        $guard = Auth::guard('library_staff');

        if (!$guard->check()) {
            return $next($request);
        }

        $staff = $guard->user();

        // ── 1. Single-session check ──────────────────────────────
        // If another login has since replaced the session_token in DB, kick this session out.
        $sessionToken = session('lib_staff_session_token');

        if ($sessionToken && $staff->session_token && $sessionToken !== $staff->session_token) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            LibraryStaffActivityLog::record(
                $staff->id,
                'session_kicked',
                null,
                'Session terminated because a new login was detected.',
                $request->ip()
            );

            return redirect()->route('library_staff.login')
                ->with('error', 'Your session was ended because the account was logged in from another location.');
        }

        // ── 2. Inactivity timeout ────────────────────────────────
        $lastActivity = session('lib_staff_last_activity');

        if ($lastActivity && (time() - $lastActivity) > (self::INACTIVITY_LIMIT * 60)) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('library_staff.login')
                ->with('error', 'You were logged out due to inactivity. Please login again.');
        }

        session(['lib_staff_last_activity' => time()]);

        // ── 3. IP change detection (log only, no block) ──────────
        $loginIp = session('lib_staff_login_ip');

        if ($loginIp && $loginIp !== $request->ip()) {
            LibraryLoginLog::create([
                'library_staff_id' => $staff->id,
                'ip_address'       => $request->ip(),
                'user_agent'       => substr($request->userAgent() ?? '', 0, 300),
                'status'           => 'ip_change',
            ]);

            LibraryStaffActivityLog::record(
                $staff->id,
                'ip_change',
                null,
                "IP changed from {$loginIp} to {$request->ip()}",
                $request->ip()
            );

            // Update stored IP so we don't spam logs on every request
            session(['lib_staff_login_ip' => $request->ip()]);
        }

        return $next($request);
    }
}
