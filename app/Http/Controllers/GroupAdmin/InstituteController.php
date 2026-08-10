<?php

namespace App\Http\Controllers\GroupAdmin;

use App\Exceptions\InstituteQuotaExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstituteRequest;
use App\Http\Requests\UpdateInstituteRequest;
use App\Models\Group;
use App\Models\GroupAdmin;
use App\Models\Institute;
use App\Models\SuperAdmin;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\InstituteProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class InstituteController extends Controller
{
    public function index(Request $request)
    {
        $groupAdmin = Auth::guard('group_admin')->user();

        $institutesQuery = Institute::where('group_id', $groupAdmin->group_id)
            ->withCount('students')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $institutesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%")
                    ->orWhere('institute_uid', 'like', "%{$search}%");
            });
        }

        $institutes = $institutesQuery->paginate(20)->withQueryString();

        $group = $groupAdmin->group;
        $remaining = $group ? $this->remainingQuota($group) : null;

        return view('group_admin.institutes.index', compact('groupAdmin', 'institutes', 'group', 'remaining'));
    }

    public function show(Institute $institute)
    {
        $groupAdmin = Auth::guard('group_admin')->user();
        abort_unless($institute->group_id === $groupAdmin->group_id, 403);

        $institute->loadCount('students');

        return view('group_admin.institutes.show', compact('groupAdmin', 'institute'));
    }

    public function edit(Institute $institute)
    {
        $groupAdmin = Auth::guard('group_admin')->user();
        abort_unless($institute->group_id === $groupAdmin->group_id, 403);

        return view('group_admin.institutes.edit', compact('groupAdmin', 'institute'));
    }

    public function update(UpdateInstituteRequest $request, Institute $institute)
    {
        $groupAdmin = Auth::guard('group_admin')->user();
        abort_unless($institute->group_id === $groupAdmin->group_id, 403);

        // Only the non-group-governed fields — student_limit/subscription/short_name/
        // group_id stay untouched here regardless of what the request contains.
        $data = [
            'name'           => $request->name,
            'mobile'         => $request->mobile,
            'email'          => $request->email,
            'primary_color'  => $request->primary_color,
            'address'        => $request->address,
            'city'           => $request->city,
            'state'          => $request->state,
            'pincode'        => $request->pincode,
            'owner_name'     => $request->owner_name,
            'owner_mobile'   => $request->owner_mobile,
            'owner_email'    => $request->owner_email,
            'owner_whatsapp' => $request->owner_whatsapp,
            'owner_address'  => $request->owner_address,
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('institutes/images', 'public');
        }
        if ($request->hasFile('owner_identity_proof')) {
            $data['owner_identity_proof'] = $request->file('owner_identity_proof')->store('institutes/identity_proofs', 'public');
        }

        $emailChanged = $institute->owner_email !== $data['owner_email'];

        $institute->update($data);

        if ($emailChanged) {
            User::where('institute_id', $institute->id)
                ->where('role', 'institute_admin')
                ->update(['email' => $data['owner_email']]);
        }

        AuditLogService::log($institute->id, 'institute', 'details_updated', 'Institute details updated by Group Admin.', $institute, [
            'updated_by_group_admin_id' => $groupAdmin->id,
            'changed_fields'            => array_keys($data),
        ]);

        return redirect()->route('group_admin.institutes.show', $institute->id)
            ->with('success', 'Institute updated successfully.');
    }

    public function toggle(Institute $institute)
    {
        $groupAdmin = Auth::guard('group_admin')->user();
        abort_unless($institute->group_id === $groupAdmin->group_id, 403);

        $institute->update(['status' => $institute->status === 'active' ? 'inactive' : 'active']);

        AuditLogService::log($institute->id, 'institute', 'status_toggled_by_group_admin', 'Institute status toggled by Group Admin.', $institute, [
            'toggled_by_group_admin_id' => $groupAdmin->id,
            'new_status'                => $institute->status,
        ]);

        return back()->with('success', 'Institute status updated.');
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
            $institute = app(InstituteProvisioningService::class)->create($data, function () use ($group) {
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

        $this->notifySuperAdminsOfNewInstitute($institute, $group, $groupAdmin);

        return redirect()->route('group_admin.institutes.index')
            ->with('success', 'Institute created and credentials sent to email.');
    }

    private function notifySuperAdminsOfNewInstitute(Institute $institute, Group $group, GroupAdmin $groupAdmin): void
    {
        try {
            $body = "A new institute was created via self-service by a Group Admin.\n\n" .
                "Institute: {$institute->name} ({$institute->institute_uid})\n" .
                "Group: {$group->name}\n" .
                "Created by: {$groupAdmin->name} ({$groupAdmin->email})\n" .
                "Created at: " . now()->format('d M Y, h:i A') . "\n\n" .
                "View: " . route('super_admin.institutes.show', $institute->id);

            foreach (SuperAdmin::all() as $superAdmin) {
                Mail::mailer('smtp')->raw($body, fn ($m) => $m->to($superAdmin->email)->subject('New Institute Created — ' . $institute->name));
            }
        } catch (\Throwable $e) {
            \Log::warning('Super-Admin new-institute notification failed', ['institute_id' => $institute->id, 'error' => $e->getMessage()]);
        }
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
