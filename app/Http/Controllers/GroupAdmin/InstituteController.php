<?php

namespace App\Http\Controllers\GroupAdmin;

use App\Exceptions\InstituteQuotaExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstituteRequest;
use App\Models\Group;
use App\Models\Institute;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\InstituteProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class InstituteController extends Controller
{
    public function index()
    {
        $groupAdmin = Auth::guard('group_admin')->user();

        $institutes = Institute::where('group_id', $groupAdmin->group_id)
            ->withCount('students')
            ->orderBy('name')
            ->get();

        $group = $groupAdmin->group;
        $remaining = $group ? $this->remainingQuota($group) : null;

        return view('group_admin.institutes.index', compact('groupAdmin', 'institutes', 'group', 'remaining'));
    }

    public function create()
    {
        $groupAdmin = Auth::guard('group_admin')->user();
        abort_unless($groupAdmin->can_create_institutes, 403);

        $group = $groupAdmin->group;
        $remaining = $this->remainingQuota($group);

        return view('group_admin.institutes.create', compact('groupAdmin', 'group', 'remaining'));
    }

    public function store(StoreInstituteRequest $request)
    {
        $groupAdmin = Auth::guard('group_admin')->user();
        abort_unless($groupAdmin->can_create_institutes, 403);

        $group = $groupAdmin->group;

        if (!$group || !$group->status) {
            return back()->withErrors(['error' => 'Your group is not active.']);
        }

        if ($group->per_institute_student_limit === null) {
            return back()->withErrors(['error' => 'Your group has not been configured with a student limit yet. Contact the platform administrator.']);
        }

        if ($group->institute_subscription_type === null) {
            return back()->withErrors(['error' => 'Your group has not been configured with a subscription policy yet. Contact the platform administrator.']);
        }

        $data = $request->validated();
        $data['group_id']             = $group->id;
        $data['student_limit']        = $group->per_institute_student_limit; // server-resolved — client-submitted value is ignored
        $data['subscription_start']   = now();
        $data['subscription_end']     = $group->institute_subscription_type === 'lifetime' ? null : $group->institute_subscription_end;
        $data['image']                = $request->file('image');
        $data['owner_identity_proof'] = $request->file('owner_identity_proof');

        try {
            app(InstituteProvisioningService::class)->create($data, function () use ($group) {
                $locked = Group::where('id', $group->id)->lockForUpdate()->first();
                if ($locked->institute_quota !== null) {
                    $count = Institute::where('group_id', $locked->id)->count();
                    if ($count >= $locked->institute_quota) {
                        throw new InstituteQuotaExceededException($locked->institute_quota);
                    }
                }
            });
        } catch (InstituteQuotaExceededException $e) {
            return back()->withErrors(['error' => "Your group has reached its institute quota ({$e->quota}). Contact the platform administrator to increase it."]);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Something went wrong. ' . $e->getMessage()]);
        }

        return redirect()->route('group_admin.institutes.index')
            ->with('success', 'Institute created and credentials sent to email.');
    }

    private function remainingQuota(Group $group): ?int
    {
        if ($group->institute_quota === null) {
            return null;
        }

        return max(0, $group->institute_quota - Institute::where('group_id', $group->id)->count());
    }

    public function resetPassword(Request $request, Institute $institute)
    {
        $groupAdmin = Auth::guard('group_admin')->user();

        abort_unless(
            $institute->group_id === $groupAdmin->group_id && $groupAdmin->can_reset_institute_password,
            403
        );

        $request->validate([
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = User::where('institute_id', $institute->id)
            ->where('role', 'institute_admin')
            ->firstOrFail();

        $user->update(['password' => Hash::make($request->password)]);

        AuditLogService::log($institute->id, 'institute', 'owner_password_reset', 'Institute-owner password reset by Group Admin.', $user, [
            'reset_by_group_admin_id' => $groupAdmin->id,
            'notify_email'            => $request->boolean('notify_email'),
        ]);

        if ($request->boolean('notify_email')) {
            Mail::mailer('smtp')->raw(
                "Hello {$user->name},\n\n" .
                "Your password for College ERP has been reset by your Group Admin.\n\n" .
                "New Password: {$request->password}\n\n" .
                "Login URL: " . url('/login') . "\n\n" .
                "Please change your password after logging in.",
                fn($m) => $m->to($user->email)->subject('Password Reset — College ERP')
            );
        }

        return back()->with('success', 'Password updated successfully.');
    }
}
