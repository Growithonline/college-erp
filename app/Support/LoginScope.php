<?php

namespace App\Support;

use App\Models\Institute;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;

/**
 * Institute-scoped login: the scope lives ONLY in the server-side session
 * (set by GET /{portal}/login/{slug}); nothing from a POST body is trusted.
 */
class LoginScope
{
    private const SESSION_KEY = 'login_scope';
    private const PENDING_KEY = 'login_scope_pending';
    private const ORIGIN_KEY  = 'login_origin';
    public const  COOKIE      = 'login_origin';
    private const COOKIE_MINUTES = 60 * 24 * 30;

    /** portal key => [label, guard, global login route, login view, otp view] */
    public static function portals(): array
    {
        return [
            'institute'     => ['label' => 'Institute',     'guard' => 'web',           'route' => 'login',               'view' => 'institute.auth.login',     'otp' => 'institute.auth.otp'],
            'center'        => ['label' => 'Center',        'guard' => 'center',        'route' => 'center.login',        'view' => 'center.auth.login',        'otp' => 'center.auth.otp'],
            'staff'         => ['label' => 'Staff',         'guard' => 'staff',         'route' => 'staff.login',         'view' => 'staff.auth.login',         'otp' => 'staff.auth.otp'],
            'partner'       => ['label' => 'Channel Partner', 'guard' => 'partner',     'route' => 'partner.login',       'view' => 'partner.auth.login',       'otp' => 'partner.auth.otp'],
            'library-staff' => ['label' => 'Library Staff', 'guard' => 'library_staff', 'route' => 'library_staff.login', 'view' => 'library_staff.auth.login', 'otp' => 'library_staff.auth.otp'],
            'student'       => ['label' => 'Student',       'guard' => 'student',       'route' => 'student.login',       'view' => 'student.auth.login',       'otp' => 'student.auth.otp'],
        ];
    }

    public static function isPortal(mixed $portal): bool
    {
        return is_string($portal) && array_key_exists($portal, self::portals());
    }

    /** Portal key for an auth guard name (web, library_staff, ...). */
    public static function portalForGuard(?string $guard): ?string
    {
        foreach (self::portals() as $key => $cfg) {
            if ($cfg['guard'] === $guard) {
                return $key;
            }
        }
        return null;
    }

    /** The slug is only ever used to look up an ACTIVE institute. */
    public static function findActiveInstitute(mixed $slug): ?Institute
    {
        if (!is_string($slug) || $slug === '' || strlen($slug) > 100) {
            return null;
        }

        return Institute::where('short_name', $slug)->where('status', 'active')->first();
    }

    public static function loginUrl(string $portal, Institute $institute): string
    {
        return route("login.scoped.{$portal}", ['slug' => $institute->short_name]);
    }

    /** Real logo only: file must exist on the public disk, otherwise null (no placeholder). */
    public static function logoUrl(Institute $institute): ?string
    {
        $image = $institute->image;

        if (!is_string($image) || $image === '' || !Storage::disk('public')->exists($image)) {
            return null;
        }

        return asset('storage/' . $image);
    }

    // ── Scope (session) ──────────────────────────────────────────────

    public static function begin(Institute $institute, string $portal): void
    {
        session([self::SESSION_KEY => [
            'institute_id' => $institute->id,
            'portal'       => $portal,
            'slug'         => $institute->short_name,
        ]]);
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /** Active scope, only when it belongs to the given portal. */
    public static function active(string $portal): ?array
    {
        $scope = session(self::SESSION_KEY);

        if (!is_array($scope) || ($scope['portal'] ?? null) !== $portal || empty($scope['institute_id'])) {
            return null;
        }

        return $scope;
    }

    /** The scoped institute for this portal, or null (global mode / institute no longer active). */
    public static function institute(string $portal): ?Institute
    {
        $scope = self::active($portal);

        if (!$scope) {
            return null;
        }

        return Institute::where('id', $scope['institute_id'])->where('status', 'active')->first();
    }

    /** Password-step gate: true when no scope applies, or the account belongs to the scoped institute. */
    public static function allows(string $portal, object $account): bool
    {
        $scope = self::active($portal);

        return $scope === null || self::matches($scope, $account);
    }

    /** Remember the scope that was enforced at the password step, for the OTP step. */
    public static function markPending(string $portal): void
    {
        session([self::PENDING_KEY . '.' . $portal => self::active($portal) ?? false]);
    }

    /** OTP-step gate: must satisfy both the scope enforced at the password step and any currently active one. */
    public static function allowsPending(string $portal, object $account): bool
    {
        $pending = session(self::PENDING_KEY . '.' . $portal);

        if (is_array($pending) && !self::matches($pending, $account)) {
            return false;
        }

        return self::allows($portal, $account);
    }

    private static function matches(array $scope, object $account): bool
    {
        $id = method_exists($account, 'resolveInstituteId') ? $account->resolveInstituteId() : null;

        return $id !== null
            && $id === (int) $scope['institute_id']
            && Institute::where('id', $id)->where('status', 'active')->exists();
    }

    // ── Return-to-origin (session + cookie) ──────────────────────────

    /** Call right after a successful login (guard login done). */
    public static function completed(string $portal): void
    {
        $scope = self::active($portal);

        session()->forget([self::SESSION_KEY, self::PENDING_KEY . '.' . $portal]);

        if ($scope && !empty($scope['slug'])) {
            $value = $portal . '|' . $scope['slug'];
            session([self::ORIGIN_KEY => $value]);
            Cookie::queue(self::COOKIE, $value, self::COOKIE_MINUTES);
            return;
        }

        session()->forget(self::ORIGIN_KEY);
        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    /**
     * Strictly validate a "portal|slug" value: known portal, slug of a real ACTIVE institute.
     * A tampered value can therefore only ever resolve to a real institute's own login page.
     *
     * @return array{0: string, 1: Institute}|null
     */
    public static function resolveOrigin(mixed $value): ?array
    {
        if (!is_string($value) || strlen($value) > 150) {
            return null;
        }

        $parts = explode('|', $value);

        if (count($parts) !== 2 || !self::isPortal($parts[0])) {
            return null;
        }

        $institute = self::findActiveInstitute($parts[1]);

        return $institute ? [$parts[0], $institute] : null;
    }

    /** Scoped login URL to return to for this portal (session first, then cookie), or null for the global page. */
    public static function originUrl(string $portal): ?string
    {
        foreach ([session(self::ORIGIN_KEY), request()->cookie(self::COOKIE)] as $candidate) {
            $origin = self::resolveOrigin($candidate);

            if ($origin && $origin[0] === $portal) {
                return self::loginUrl($portal, $origin[1]);
            }
        }

        return null;
    }

    /** Login page to send a pending/refused login back to: the active scoped page, else the global one. */
    public static function currentLoginUrl(string $portal): string
    {
        $institute = self::institute($portal);

        return $institute ? self::loginUrl($portal, $institute) : route(self::portals()[$portal]['route']);
    }

    /** Where logout / expiry should send the user for this portal. */
    public static function loginUrlOrGlobal(string $portal): string
    {
        return self::originUrl($portal) ?? route(self::portals()[$portal]['route']);
    }
}
