<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstituteRequest;
use App\Models\Institute;
use App\Mail\InstituteCredentialMail;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\AccountingSetupService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InstituteController extends Controller
{
    public function index()
    {
        $institutes = Institute::withCount('students')->orderByDesc('id')->get();
        return view('super_admin.institutes.index', compact('institutes'));
    }

    public function show(Institute $institute)
    {
        $institute->loadCount('students');
        return view('super_admin.institutes.show', compact('institute'));
    }

    public function toggle(Institute $institute)
    {
        $institute->update(['status' => $institute->status === 'active' ? 'inactive' : 'active']);
        return back()->with('success', 'Institute status updated.');
    }

    public function resetPassword(Request $request, Institute $institute)
    {
        $request->validate([
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = \App\Models\User::where('institute_id', $institute->id)
            ->where('role', 'institute_admin')
            ->firstOrFail();

        $user->update(['password' => Hash::make($request->password)]);

        if ($request->boolean('notify_email')) {
            Mail::raw(
                "Hello {$user->name},\n\n" .
                "Your password for College ERP has been reset by the Super Admin.\n\n" .
                "New Password: {$request->password}\n\n" .
                "Login URL: " . url('/login') . "\n\n" .
                "Please change your password after logging in.",
                fn($m) => $m->to($user->email)->subject('Password Reset — College ERP')
            );
        }

        return back()->with('success', 'Password updated successfully.');
    }

    public function create()
    {
        return view('super_admin.institutes.create');
    }

    public function store(StoreInstituteRequest $request)
    {
        // ── Step 1: DB transaction (only real failures abort here) ──────────
        try {
            DB::beginTransaction();

            $uid = $this->generateInstituteUID();

            $imagePath = $request->hasFile('image')
                ? $request->file('image')->store('institutes/images', 'public')
                : null;

            $identityProofPath = $request->hasFile('owner_identity_proof')
                ? $request->file('owner_identity_proof')
                    ->store('institutes/identity_proofs', 'public')
                : null;

            $institute = Institute::create([
                'institute_uid'        => $uid,
                'name'                 => $request->name,
                'short_name'           => strtoupper($request->short_name),
                'mobile'               => $request->mobile,
                'email'                => $request->email,
                'image'                => $imagePath,
                'address'              => $request->address,
                'city'                 => $request->city,
                'state'                => $request->state,
                'pincode'              => $request->pincode,
                'owner_name'           => $request->owner_name,
                'owner_mobile'         => $request->owner_mobile,
                'owner_email'          => $request->owner_email,
                'owner_whatsapp'       => $request->owner_whatsapp,
                'owner_address'        => $request->owner_address,
                'owner_identity_proof' => $identityProofPath,
                'student_limit'        => $request->student_limit,
                'subscription_start'   => $request->subscription_start ?? now(),
                'subscription_end'     => $request->subscription_end,
                'status'               => 'active',
            ]);

            $plainPassword = Str::random(10);

            $user = User::create([
                'institute_id' => $institute->id,
                'name'         => $request->owner_name,
                'email'        => $request->owner_email,
                'mobile'       => $request->owner_mobile,
                'password'     => Hash::make($plainPassword),
                'role'         => 'institute_admin',
            ]);

            \Database\Seeders\StaffRoleSeeder::createDefaultRoles($institute->id);
            AccountingSetupService::bootstrapInstitute($institute->id);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Something went wrong. ' . $e->getMessage()]);
        }

        // ── Step 2: Email — completely outside DB transaction ────────────────
        try {
            Mail::to($user->email)->send(new InstituteCredentialMail(
                ownerName:     $request->input('owner_name'),
                instituteName: $request->input('name'),
                instituteUid:  $uid,
                email:         $user->email,
                password:      $plainPassword,
                loginUrl:      url('/login'),
                logoUrl:       asset('images/logog.png'),
            ));
        } catch (\Throwable $mailEx) {
            \Log::warning('Institute welcome email failed', [
                'institute_id' => $institute->id,
                'error'        => $mailEx->getMessage(),
            ]);
        }

        // ── Step 3: SMS — fire-and-forget ────────────────────────────────────
        try {
            if (! empty($request->owner_mobile) && SmsService::isPlatformConfigured()) {
                $smsMessage = "Welcome to College ERP! Institute ID: {$uid} | Email: {$user->email} | Password: {$plainPassword} | Login: " . url('/login') . " | Please change your password after first login.";
                SmsService::sendFromPlatform($request->owner_mobile, $smsMessage, SmsLog::TYPE_WELCOME, $institute->id);
            }
        } catch (\Throwable $smsEx) {
            \Log::warning('Institute welcome SMS failed', [
                'institute_id' => $institute->id,
                'error'        => $smsEx->getMessage(),
            ]);
        }

        return redirect()
            ->route('super_admin.institutes.create')
            ->with('success', 'Institute created and credentials sent to email.');
    }

    public function resendCredentials(Institute $institute)
    {
        $user = User::where('institute_id', $institute->id)
            ->where('role', 'institute_admin')
            ->firstOrFail();

        $plainPassword = Str::random(10);
        $user->update(['password' => Hash::make($plainPassword)]);

        try {
            Mail::to($user->email)->send(new InstituteCredentialMail(
                ownerName:     $institute->owner_name,
                instituteName: $institute->name,
                instituteUid:  $institute->institute_uid,
                email:         $user->email,
                password:      $plainPassword,
                loginUrl:      url('/login'),
                logoUrl:       asset('images/logog.png'),
            ));
        } catch (\Throwable $e) {
            return back()->with('error', 'Password reset but email failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Credentials resent successfully to ' . $user->email);
    }

    private function generateInstituteUID(): string
    {
        $year = now()->year;

        $count = Institute::whereYear('created_at', $year)->lockForUpdate()->count() + 1;

        return 'GT/' . $year . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
