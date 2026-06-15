<?php

namespace App\Http\Controllers\Institute\Library;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Library\LibraryRuleSet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

abstract class BaseLibraryController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            View::share('libraryLayout',      $this->libraryLayout());
            View::share('libraryRoutePrefix', $this->libraryRoutePrefix());
            View::share('libraryPortalLabel', $this->guardName() === 'staff' ? 'Staff Library' : 'Library');

            return $next($request);
        });
    }

    protected function guardName(): string
    {
        if (Auth::guard('library_staff')->check()) return 'library_staff';
        if (Auth::guard('staff')->check())         return 'staff';
        return 'web';
    }

    protected function authUser()
    {
        return match ($this->guardName()) {
            'library_staff' => Auth::guard('library_staff')->user(),
            'staff'         => Auth::guard('staff')->user(),
            default         => Auth::user(),
        };
    }

    protected function instituteId(): int
    {
        $user = $this->authUser();
        abort_if(!$user, 403, 'Unauthenticated.');
        return (int) $user->institute_id;
    }

    protected function actorName(): string
    {
        return (string) ($this->authUser()->name ?? 'System');
    }

    protected function activeSessionId(): ?int
    {
        return AcademicSession::where('institute_id', $this->instituteId())
            ->where('is_active', true)
            ->value('id');
    }

    protected function defaultRuleSetId(string $memberType): ?int
    {
        return LibraryRuleSet::forInstitute($this->instituteId())
            ->where('member_type', $memberType)
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');
    }

    protected function libraryLayout(): string
    {
        return match ($this->guardName()) {
            'library_staff' => 'library_staff.layout',
            'staff'         => 'staff.layout',
            default         => 'institute.layout',
        };
    }

    protected function libraryRoutePrefix(): string
    {
        return $this->guardName() === 'staff' ? 'staff.library' : 'library';
    }

    protected function routeName(string $suffix): string
    {
        return $this->libraryRoutePrefix() . '.' . $suffix;
    }

    protected function redirectRoute(string $suffix, mixed ...$parameters)
    {
        return redirect()->route($this->routeName($suffix), ...$parameters);
    }

    protected function ensureLibraryPermission(string $ability = 'view'): void
    {
        $guard = $this->guardName();

        // Institute admin — unrestricted
        if ($guard === 'web') {
            return;
        }

        // Staff portal guard — uses StaffMember permission methods
        if ($guard === 'staff') {
            $staff   = $this->authUser();
            $allowed = match ($ability) {
                'manage'       => $staff?->canManageLibrary(),
                'issue'        => $staff?->canIssueLibraryBooks(),
                'reports'      => $staff?->canViewLibraryReports(),
                'members'      => $staff?->canManageLibraryMembers(),
                'reservations' => $staff?->canManageLibraryReservations(),
                'no_due'       => $staff?->canGenerateLibraryNoDue(),
                default        => $staff?->canViewLibrary(),
            };
            abort_unless($allowed, 403, 'You do not have permission to access this library section.');
            return;
        }

        // Dedicated library staff guard — uses LibraryStaff permission keys
        if ($guard === 'library_staff') {
            $libStaff = $this->authUser();
            $allowed  = match ($ability) {
                'manage'       => $libStaff?->canManageLibrary(),
                'issue'        => $libStaff?->canIssueLibraryBooks(),
                'reports'      => $libStaff?->canViewLibraryReports(),
                'members'      => $libStaff?->canManageLibraryMembers(),
                'reservations' => $libStaff?->canManageLibraryReservations(),
                'no_due'       => $libStaff?->canGenerateLibraryNoDue(),
                default        => $libStaff?->canViewLibrary(),
            };
            abort_unless($allowed, 403, 'You do not have permission to access this library section.');
        }
    }
}
