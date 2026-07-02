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
            Mail::mailer('smtp')->raw(
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
            ]);
            // 'role' is not mass-assignable — set directly to prevent privilege escalation
            $user->role = 'institute_admin';
            $user->save();

            \Database\Seeders\StaffRoleSeeder::createDefaultRoles($institute->id);
            AccountingSetupService::bootstrapInstitute($institute->id);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Something went wrong. ' . $e->getMessage()]);
        }

        // ── Step 2: Email — completely outside DB transaction ────────────────
        try {
            Mail::mailer('smtp')->to($user->email)->send(new InstituteCredentialMail(
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
            Mail::mailer('smtp')->to($user->email)->send(new InstituteCredentialMail(
                ownerName:     $institute->owner_name,
                instituteName: $institute->name,
                instituteUid:  $institute->institute_uid,
                email:         $user->email,
                password:      $plainPassword,
                loginUrl:      url('/login'),
                logoUrl:       asset('images/logog.png'),
            ));
        } catch (\Throwable $e) {
            \Log::warning('Resend credentials email failed', ['institute_id' => $institute->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Password was reset but the email could not be delivered. Please check platform SMTP settings.');
        }

        return back()->with('success', 'Credentials resent successfully to ' . $user->email);
    }

    public function cleanData(Request $request, Institute $institute)
    {
        $request->validate([
            'confirm_name' => ['required', 'string'],
        ]);

        if (trim($request->confirm_name) !== $institute->name) {
            return back()->with('error', 'Institute ka naam match nahi hua. Data delete nahi hua.');
        }

        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            \DB::transaction(function () use ($institute) {
                $id = $institute->id;

                // Library — pehle staff ke child records (library_staff_id se linked)
                $libStaffIds = \DB::table('library_staff')->where('institute_id', $id)->pluck('id');
                if ($libStaffIds->isNotEmpty()) {
                    foreach (['library_staff_activity_logs','library_login_logs','library_staff_permissions'] as $tbl) {
                        if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->whereIn('library_staff_id', $libStaffIds)->delete();
                    }
                }
                // Library — baki sabhi institute_id se linked hain
                foreach (['library_fine_payments','library_reservations','library_transactions','library_members','library_book_copies','library_books','library_racks','library_rule_sets','library_publishers','library_authors','library_categories','library_subjects','library_vendors','library_staff'] as $tbl) {
                    if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->where('institute_id', $id)->delete();
                }

                // Transport
                foreach (['transport_payments','transport_monthly_charges','transport_maintenance_logs'] as $tbl) {
                    if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->where('institute_id', $id)->delete();
                }
                $vehicleIds = \DB::table('transport_vehicles')->where('institute_id', $id)->pluck('id');
                if ($vehicleIds->isNotEmpty()) \DB::table('transport_vehicle_documents')->whereIn('vehicle_id', $vehicleIds)->delete();
                $routeIds = \DB::table('transport_routes')->where('institute_id', $id)->pluck('id');
                if ($routeIds->isNotEmpty()) \DB::table('transport_route_stops')->whereIn('route_id', $routeIds)->delete();
                foreach (['transport_vehicles','transport_routes'] as $tbl) {
                    if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->where('institute_id', $id)->delete();
                }

                // Finance
                foreach (['cheque_payments','contra_entries','salary_records','expenses','institute_manual_incomes','institute_transactions'] as $tbl) {
                    if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->where('institute_id', $id)->delete();
                }
                $journalIds = \DB::table('journal_entries')->where('institute_id', $id)->pluck('id');
                if ($journalIds->isNotEmpty()) \DB::table('journal_entry_lines')->whereIn('journal_entry_id', $journalIds)->delete();
                foreach (['journal_entries','accounts','finance_settings','expense_vendors','expense_approval_limits','expense_categories_l2','expense_categories_l1','institute_income_categories'] as $tbl) {
                    if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->where('institute_id', $id)->delete();
                }

                // Fee Invoices
                $invoiceIds = \DB::table('fee_invoices')->where('institute_id', $id)->pluck('id');
                if ($invoiceIds->isNotEmpty()) \DB::table('fee_invoice_items')->whereIn('fee_invoice_id', $invoiceIds)->delete();
                \DB::table('fee_invoices')->where('institute_id', $id)->delete();

                // Practical tokens
                $batchIds = \DB::table('practical_fee_token_batches')->where('institute_id', $id)->pluck('id');
                if ($batchIds->isNotEmpty()) \DB::table('practical_fee_token_entries')->whereIn('batch_id', $batchIds)->delete();
                if (\DB::getSchemaBuilder()->hasTable('practical_fee_token_batches')) \DB::table('practical_fee_token_batches')->where('institute_id', $id)->delete();

                // Students
                $studentIds = \DB::table('students')->where('institute_id', $id)->pluck('id');
                if ($studentIds->isNotEmpty()) {
                    foreach (['student_education_details','student_subjects','student_transactions','student_wallets','student_attendance','certificates','admission_documents','student_academic_change_logs','subject_change_logs','promotion_logs'] as $tbl) {
                        if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->whereIn('student_id', $studentIds)->delete();
                    }
                    if (\DB::getSchemaBuilder()->hasTable('student_academic_identities')) \DB::table('student_academic_identities')->whereIn('student_id', $studentIds)->delete();
                }
                \DB::table('students')->where('institute_id', $id)->delete();

                // Staff
                $staffIds = \DB::table('staff_members')->where('institute_id', $id)->pluck('id');
                if ($staffIds->isNotEmpty()) {
                    foreach (['staff_attendance','staff_loans','staff_course_permissions','staff_fee_collection_permissions','staff_fee_discount_permissions','staff_permission_overrides'] as $tbl) {
                        if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->whereIn('staff_member_id', $staffIds)->delete();
                    }
                }
                foreach (['staff_members','staff_roles','attendance_lock_records'] as $tbl) {
                    if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->where('institute_id', $id)->delete();
                }

                // Centers
                $centerIds = \DB::table('centers')->where('institute_id', $id)->pluck('id');
                if ($centerIds->isNotEmpty()) {
                    // center_wallet_transactions ka FK center_wallet_id hai (center_id nahi)
                    $centerWalletIds = \DB::table('center_wallets')->whereIn('center_id', $centerIds)->pluck('id');
                    if ($centerWalletIds->isNotEmpty()) {
                        if (\DB::getSchemaBuilder()->hasTable('center_wallet_transactions'))
                            \DB::table('center_wallet_transactions')->whereIn('center_wallet_id', $centerWalletIds)->delete();
                    }
                    foreach (['center_fee_collection_permissions','center_fee_discount_permissions','center_wallets'] as $tbl) {
                        if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->whereIn('center_id', $centerIds)->delete();
                    }
                }
                \DB::table('centers')->where('institute_id', $id)->delete();

                // Channel Partners
                $partnerIds = \DB::table('channel_partners')->where('institute_id', $id)->pluck('id');
                if ($partnerIds->isNotEmpty()) {
                    // channel_wallet_transactions ka FK channel_wallet_id hai (partner_id nahi)
                    $channelWalletIds = \DB::table('channel_wallets')->whereIn('channel_partner_id', $partnerIds)->pluck('id');
                    if ($channelWalletIds->isNotEmpty()) {
                        if (\DB::getSchemaBuilder()->hasTable('channel_wallet_transactions'))
                            \DB::table('channel_wallet_transactions')->whereIn('channel_wallet_id', $channelWalletIds)->delete();
                    }
                    // channel_wallets ka FK channel_partner_id hai (partner_id nahi)
                    if (\DB::getSchemaBuilder()->hasTable('channel_wallets'))
                        \DB::table('channel_wallets')->whereIn('channel_partner_id', $partnerIds)->delete();
                    if (\DB::getSchemaBuilder()->hasTable('partner_commission_entries'))
                        \DB::table('partner_commission_entries')->whereIn('partner_id', $partnerIds)->delete();
                }
                \DB::table('channel_partners')->where('institute_id', $id)->delete();

                // Wallets
                foreach (['wallet_extension_requests','institute_wallets'] as $tbl) {
                    if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->where('institute_id', $id)->delete();
                }

                // Academic structure — course_streams → course_id FK (academic_session_id nahi)
                $courseIdsForStreams = \DB::table('courses')->where('institute_id', $id)->pluck('id');
                if ($courseIdsForStreams->isNotEmpty()) {
                    $streamIds = \DB::table('course_streams')->whereIn('course_id', $courseIdsForStreams)->pluck('id');
                    if ($streamIds->isNotEmpty()) {
                        foreach (['stream_year_subject_rules','course_stream_subjects','stream_session_limits'] as $tbl) {
                            if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->whereIn('course_stream_id', $streamIds)->delete();
                        }
                        \DB::table('course_streams')->whereIn('course_id', $courseIdsForStreams)->delete();
                    }
                }
                // fee_assignments ka direct institute_id hai
                if (\DB::getSchemaBuilder()->hasTable('fee_assignments')) \DB::table('fee_assignments')->where('institute_id', $id)->delete();
                // academic_sessions ka direct institute_id hai
                \DB::table('academic_sessions')->where('institute_id', $id)->delete();

                // Courses & Subjects
                $courseIds = \DB::table('courses')->where('institute_id', $id)->pluck('id');
                if ($courseIds->isNotEmpty()) \DB::table('course_parts')->whereIn('course_id', $courseIds)->delete();
                $subjectIds = \DB::table('subjects')->where('institute_id', $id)->pluck('id');
                if ($subjectIds->isNotEmpty()) {
                    foreach (['subject_components','subject_fee_rules'] as $tbl) {
                        if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->whereIn('subject_id', $subjectIds)->delete();
                    }
                }
                foreach (['subjects','course_fee_rules','courses','course_types','student_types'] as $tbl) {
                    if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->where('institute_id', $id)->delete();
                }

                // Fee Plans & Types
                $feePlanIds = \DB::table('fee_plans')->where('institute_id', $id)->pluck('id');
                if ($feePlanIds->isNotEmpty()) \DB::table('fee_plan_installments')->whereIn('fee_plan_id', $feePlanIds)->delete();
                foreach (['fee_plans','fee_types'] as $tbl) {
                    if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->where('institute_id', $id)->delete();
                }

                // Bank Accounts & Permissions
                foreach (['payment_mode_permissions','institute_bank_accounts'] as $tbl) {
                    if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->where('institute_id', $id)->delete();
                }

                // Certificates
                foreach (['certificate_types','certificate_settings'] as $tbl) {
                    if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->where('institute_id', $id)->delete();
                }

                // Documents
                $docCatIds = \DB::table('document_categories')->where('institute_id', $id)->pluck('id');
                if ($docCatIds->isNotEmpty()) {
                    $docTypeIds = \DB::table('document_types')->whereIn('document_category_id', $docCatIds)->pluck('id');
                    if ($docTypeIds->isNotEmpty()) {
                        \DB::table('document_upload_rules')->whereIn('document_type_id', $docTypeIds)->delete();
                        \DB::table('document_types')->whereIn('document_category_id', $docCatIds)->delete();
                    }
                }
                if (\DB::getSchemaBuilder()->hasTable('document_categories')) \DB::table('document_categories')->where('institute_id', $id)->delete();

                // Notices, SMS
                $noticeIds = \DB::table('notices')->where('institute_id', $id)->pluck('id');
                if ($noticeIds->isNotEmpty()) \DB::table('notice_reads')->whereIn('notice_id', $noticeIds)->delete();
                foreach (['notices','sms_logs','sms_due_reminder_settings','sms_provider_settings'] as $tbl) {
                    if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->where('institute_id', $id)->delete();
                }

                // Counters & Settings
                foreach (['admission_form_settings','admission_counters','fee_invoice_counters','audit_logs'] as $tbl) {
                    if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->where('institute_id', $id)->delete();
                }

                // Users (institute login only — not super admin)
                \DB::table('users')->where('institute_id', $id)->delete();
            });

            \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        } catch (\Throwable $e) {
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
            \Log::error('Institute clean-data failed', ['institute_id' => $institute->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Kuch galat hua: ' . $e->getMessage());
        }

        return back()->with('success', '"' . $institute->name . '" ka sabhi data successfully delete ho gaya. Institute fresh hai ab.');
    }

    private function generateInstituteUID(): string
    {
        $year = now()->year;

        $count = Institute::whereYear('created_at', $year)->lockForUpdate()->count() + 1;

        return 'GT/' . $year . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
