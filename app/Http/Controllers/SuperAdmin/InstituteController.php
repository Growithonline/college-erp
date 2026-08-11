<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInstituteRequest;
use App\Http\Requests\UpdateInstituteRequest;
use App\Mail\InstituteCredentialMail;
use App\Mail\LoginIdNotificationMail;
use App\Models\Center;
use App\Models\ChannelPartner;
use App\Models\Group;
use App\Models\Institute;
use App\Models\LibraryStaff;
use App\Models\StaffMember;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\InstituteMailer;
use App\Services\InstituteProvisioningService;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $institute->load(['policyAcceptances' => function ($query) {
            $query->latest('accepted_at')->with('acceptedBy:id,name,email');
        }, 'group']);
        $groups = Group::where('status', true)->orderBy('name')->get(['id', 'name']);
        return view('super_admin.institutes.show', compact('institute', 'groups'));
    }

    public function edit(Institute $institute)
    {
        return view('super_admin.institutes.edit', compact('institute'));
    }

    public function update(UpdateInstituteRequest $request, Institute $institute)
    {
        $data = $request->validated();
        unset($data['image'], $data['owner_identity_proof']);

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

        AuditLogService::log($institute->id, 'institute', 'details_updated', 'Institute details updated by Super Admin.', $institute, [
            'changed_fields' => array_keys($data),
        ]);

        return redirect()->route('super_admin.institutes.show', $institute->id)
            ->with('success', 'Institute updated successfully.');
    }

    public function consentPdf(Institute $institute)
    {
        $accepted = $institute->policyAcceptances()
            ->latest('accepted_at')
            ->with('acceptedBy:id,name,email')
            ->get()
            ->groupBy('document_type')
            ->map->first();

        abort_if($accepted->isEmpty(), 404, 'No policy has been accepted by this institute yet.');

        $documents = collect(config('legal.documents'))
            ->only($accepted->keys())
            ->map(fn ($meta, $type) => $meta + ['acceptance' => $accepted[$type]]);

        $pdf = Pdf::loadView('super_admin.institutes.consent-pdf', [
            'institute' => $institute,
            'documents' => $documents,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('consent-' . Str::slug($institute->institute_uid) . '.pdf');
    }

    public function toggle(Institute $institute)
    {
        $institute->update(['status' => $institute->status === 'active' ? 'inactive' : 'active']);
        return back()->with('success', 'Institute status updated.');
    }

    public function assignGroup(Request $request, Institute $institute)
    {
        $request->validate(['group_id' => 'nullable|exists:groups,id']);

        $institute->update(['group_id' => $request->group_id]);

        return back()->with('success', $request->group_id ? 'Institute assigned to group.' : 'Institute removed from group.');
    }

    /**
     * Emails every active Staff/Channel-Partner/Center/Library-Staff member of this
     * institute their new Login ID — same underlying mail as the per-institute
     * "Notify Login ID" buttons, triggerable centrally when the platform operator
     * doesn't hold the institute-owner's own login.
     */
    public function notifyAllLoginIds(Institute $institute)
    {
        $jobs = [
            'staff'         => [StaffMember::class, 'staff_uid', 'Staff Portal', 'Staff ID', 'staff.login'],
            'partner'       => [ChannelPartner::class, 'partner_uid', 'Channel Partner Portal', 'Partner ID', 'partner.login'],
            'center'        => [Center::class, 'center_uid', 'Center Portal', 'Center ID', 'center.login'],
            'library_staff' => [LibraryStaff::class, 'employee_id', 'Library Staff Portal', 'Employee ID', 'library_staff.login'],
        ];

        $sent = ['staff' => 0, 'partner' => 0, 'center' => 0, 'library_staff' => 0];
        $failed = 0;

        foreach ($jobs as $key => [$modelClass, $uidColumn, $portalLabel, $idLabel, $routeName]) {
            $records = $modelClass::where('institute_id', $institute->id)
                ->where('status', true)
                ->whereNotNull($uidColumn)
                ->get();

            foreach ($records as $record) {
                try {
                    InstituteMailer::send($institute->id, $record->email, new LoginIdNotificationMail(
                        institute: $institute,
                        recipientName: $record->name,
                        portalLabel: $portalLabel,
                        loginIdLabel: $idLabel,
                        loginId: $record->{$uidColumn},
                        loginUrl: route($routeName),
                    ));
                    $sent[$key]++;
                } catch (\Throwable $e) {
                    \Log::warning('Login-ID notification failed', ['model' => $modelClass, 'id' => $record->id, 'error' => $e->getMessage()]);
                    $failed++;
                }
            }
        }

        $message = "Login ID emailed to {$sent['staff']} staff, {$sent['partner']} partners, {$sent['center']} centers, {$sent['library_staff']} library staff.";
        if ($failed > 0) {
            $message .= " {$failed} email(s) failed to send — check logs.";
        }

        return back()->with($failed > 0 ? 'error' : 'success', $message);
    }

    public function updateBranding(Request $request, Institute $institute)
    {
        $request->validate([
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'image'         => ['nullable', 'file', 'max:2048', 'extensions:jpg,jpeg,png'],
        ]);

        $data = ['primary_color' => $request->primary_color];
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('institutes/images', 'public');
        }

        $institute->update($data);

        return back()->with('success', 'Branding updated.');
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

        AuditLogService::log($institute->id, 'institute', 'owner_password_reset', 'Institute-owner password reset by Super Admin.', $user, [
            'notify_email' => $request->boolean('notify_email'),
        ]);

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
        $data = $request->validated();
        $data['group_id']             = null;
        $data['image']                = $request->file('image');
        $data['owner_identity_proof'] = $request->file('owner_identity_proof');

        try {
            app(InstituteProvisioningService::class)->create($data);
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Something went wrong. ' . $e->getMessage()]);
        }

        return redirect()
            ->route('super_admin.institutes.create')
            ->with('success', 'Institute created and credentials sent to email.');
    }

    public function resendCredentials(Institute $institute)
    {
        $plainPassword = Str::random(10);

        $user = User::where('institute_id', $institute->id)
            ->where('role', 'institute_admin')
            ->first();

        if ($user) {
            $user->update(['password' => Hash::make($plainPassword)]);
        } else {
            // User was accidentally deleted (e.g. via data clean before the bug was fixed) — recreate it
            $user = User::create([
                'institute_id' => $institute->id,
                'name'         => $institute->owner_name,
                'email'        => $institute->owner_email,
                'mobile'       => $institute->owner_mobile,
                'password'     => Hash::make($plainPassword),
            ]);
            $user->role = 'institute_admin';
            $user->save();
        }

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

    public function restoreData(Request $request, Institute $institute)
    {
        $mode = $request->input('restore_mode', 'upload');

        if ($mode === 'path') {
            $request->validate([
                'server_path' => ['required', 'string', 'max:500'],
            ]);

            $path = $request->input('server_path');

            // Only allow storage/app/restores/ directory — prevent path traversal
            $allowed = storage_path('app/restores');
            $real    = realpath($path);
            if (! $real || ! str_starts_with($real, $allowed) || ! is_file($real)) {
                return back()->with('error',
                    'Invalid server path. File must be inside: storage/app/restores/'
                );
            }

            $content  = file_get_contents($real);
            $pathToDelete = $real; // delete after restore

        } else {
            $request->validate([
                'backup_file' => ['required', 'file', 'mimes:sql,txt', 'max:512000'], // 500 MB
            ]);
            $content      = file_get_contents($request->file('backup_file')->getPathname());
            $pathToDelete = null;
        }

        // ── Verify backup is for this institute ───────────────────────────
        $uid = $institute->institute_uid;
        if (! str_contains($content, "UID: {$uid}") && ! str_contains($content, "institute_id = {$institute->id}")) {
            return back()->with('error', "Backup file is invalid or belongs to a different institute (expected UID: {$uid}).");
        }

        // ── Convert INSERT INTO → INSERT IGNORE INTO ─────────────────────
        // INSERT IGNORE skips rows whose primary key already exists in another
        // institute's data — safe because we clean THIS institute's rows first.
        $content = preg_replace('/\bINSERT INTO\b/', 'INSERT IGNORE INTO', $content);

        \DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $restored = 0;

        try {
            // Clean + restore ek hi transaction me — beech me koi statement fail
            // hua to poora rollback, institute ka original data safe rehta hai.
            \DB::transaction(function () use ($institute, $content, &$restored) {
                // ── Step 1: Clean existing data for this institute ────────
                $this->runCleanForInstitute($institute->id);

                // ── Step 2: Parse & execute SQL statements ────────────────
                $buffer = '';

                foreach (explode("\n", $content) as $line) {
                    $trimmed = trim($line);

                    // Skip comments and empty lines
                    if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                        continue;
                    }

                    $buffer .= ' ' . $trimmed;

                    // Statement complete when line ends with ;
                    if (str_ends_with(rtrim($trimmed), ';')) {
                        $stmt = trim($buffer);
                        $buffer = '';

                        if ($stmt === ';' || $stmt === '') {
                            continue;
                        }

                        // Only execute data statements — skip SET, CREATE, DROP
                        if (preg_match('/^(INSERT IGNORE INTO|SET\s+(FOREIGN_KEY_CHECKS|NAMES))/i', $stmt)) {
                            \DB::statement($stmt);
                            if (stripos($stmt, 'INSERT') === 0) {
                                $restored++;
                            }
                        }
                    }
                }
            });

            \DB::statement('SET FOREIGN_KEY_CHECKS=1');

            // Server path se restore tha — file delete karo (security)
            if ($pathToDelete && is_file($pathToDelete)) {
                @unlink($pathToDelete);
            }

        } catch (\Throwable $e) {
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
            \Log::error('Institute restore failed', [
                'institute_id' => $institute->id,
                'error'        => $e->getMessage(),
            ]);
            return back()->with('error', 'Restore failed: ' . $e->getMessage() . ' — koi data change nahi hua, poora rollback ho gaya.');
        }

        AuditLogService::log($institute->id, 'institute', 'data_restored', 'Institute data restored from backup by Super Admin.', $institute, [
            'rows_restored' => $restored,
        ]);

        return back()->with('success',
            "\"{$institute->name}\" ka data successfully restore ho gaya. " .
            number_format($restored) . " rows restore hue."
        );
    }

    private function runCleanForInstitute(int $id): void
    {
        // Shared by cleanData() and restoreData() — single source of truth for
        // which tables count as "institute data" so both stay in sync.
        $schema = \DB::getSchemaBuilder();

        // Library
        $libStaffIds = \DB::table('library_staff')->where('institute_id', $id)->pluck('id');
        if ($libStaffIds->isNotEmpty()) {
            foreach (['library_staff_activity_logs','library_login_logs','library_staff_permissions'] as $t) {
                if ($schema->hasTable($t)) \DB::table($t)->whereIn('library_staff_id', $libStaffIds)->delete();
            }
        }
        $libBookIds = \DB::table('library_books')->where('institute_id', $id)->pluck('id');
        if ($libBookIds->isNotEmpty() && $schema->hasTable('library_book_author')) {
            \DB::table('library_book_author')->whereIn('book_id', $libBookIds)->delete();
        }
        foreach (['library_fine_payments','library_reservations','library_transactions','library_members',
                  'library_book_copies','library_books','library_racks','library_rule_sets',
                  'library_publishers','library_authors','library_categories','library_subjects',
                  'library_vendors','library_staff'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }

        // Transport
        foreach (['transport_payments','transport_monthly_charges','transport_maintenance_logs',
                  'transport_allocations'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }
        if ($schema->hasTable('transport_vehicle_documents'))
            \DB::table('transport_vehicle_documents')->where('institute_id', $id)->delete();
        if ($schema->hasTable('transport_driver_documents'))
            \DB::table('transport_driver_documents')->where('institute_id', $id)->delete();
        $rIds = \DB::table('transport_routes')->where('institute_id', $id)->pluck('id');
        if ($rIds->isNotEmpty()) {
            \DB::table('transport_route_stops')->whereIn('transport_route_id', $rIds)->delete();
            if ($schema->hasTable('transport_route_assignments'))
                \DB::table('transport_route_assignments')->whereIn('transport_route_id', $rIds)->delete();
        }
        foreach (['transport_vehicles','transport_drivers','transport_routes',
                  'transport_helpers','transport_vehicle_types','institute_transport_settings'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }

        // Finance
        foreach (['cheque_payments','contra_entries','salary_records','expenses',
                  'institute_manual_incomes','institute_transactions'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }
        $jIds = \DB::table('journal_entries')->where('institute_id', $id)->pluck('id');
        if ($jIds->isNotEmpty()) \DB::table('journal_entry_lines')->whereIn('journal_entry_id', $jIds)->delete();
        foreach (['journal_entries','accounts','finance_settings','expense_vendors',
                  'expense_approval_limits','expense_categories_l2','expense_categories_l1',
                  'institute_income_categories'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }

        // Fee Invoices
        $invIds = \DB::table('fee_invoices')->where('institute_id', $id)->pluck('id');
        if ($invIds->isNotEmpty()) \DB::table('fee_invoice_items')->whereIn('fee_invoice_id', $invIds)->delete();
        \DB::table('fee_invoices')->where('institute_id', $id)->delete();

        // Practical tokens
        $bIds = \DB::table('practical_fee_token_batches')->where('institute_id', $id)->pluck('id');
        if ($bIds->isNotEmpty()) \DB::table('practical_fee_token_entries')->whereIn('batch_id', $bIds)->delete();
        if ($schema->hasTable('practical_fee_token_batches'))
            \DB::table('practical_fee_token_batches')->where('institute_id', $id)->delete();

        // Students
        $stuIds = \DB::table('students')->where('institute_id', $id)->pluck('id');
        if ($stuIds->isNotEmpty()) {
            foreach (['student_education_details','student_subjects','student_transactions',
                      'student_wallets','student_attendance','certificates','admission_documents',
                      'student_academic_change_logs','subject_change_logs','promotion_logs'] as $t) {
                if ($schema->hasTable($t)) \DB::table($t)->whereIn('student_id', $stuIds)->delete();
            }
            // Table is singular ("student_academic_identity"), not plural — despite the name
            // every other sibling table in this method uses.
            if ($schema->hasTable('student_academic_identity'))
                \DB::table('student_academic_identity')->whereIn('student_id', $stuIds)->delete();
        }
        \DB::table('students')->where('institute_id', $id)->delete();

        // Staff
        $stfIds = \DB::table('staff_members')->where('institute_id', $id)->pluck('id');
        if ($stfIds->isNotEmpty()) {
            foreach (['staff_attendance','staff_loans','staff_course_permissions',
                      'staff_fee_collection_permissions','staff_fee_discount_permissions',
                      'staff_permission_overrides'] as $t) {
                if ($schema->hasTable($t)) \DB::table($t)->whereIn('staff_member_id', $stfIds)->delete();
            }
        }
        foreach (['staff_bonuses','staff_documents','staff_members','staff_roles','attendance_lock_records'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }

        // Employees (separate HR module from Staff)
        $empIds = $schema->hasTable('employees')
            ? \DB::table('employees')->where('institute_id', $id)->pluck('id')
            : collect();
        if ($empIds->isNotEmpty() && $schema->hasTable('employee_salary_components')) {
            \DB::table('employee_salary_components')->whereIn('employee_id', $empIds)->delete();
        }
        foreach (['employee_documents','employee_salary_disbursements','employee_bonuses','employee_advances'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }
        foreach (['employees','employee_designations','employee_departments'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }

        // Centers
        $cIds = \DB::table('centers')->where('institute_id', $id)->pluck('id');
        if ($cIds->isNotEmpty()) {
            $cwIds = \DB::table('center_wallets')->whereIn('center_id', $cIds)->pluck('id');
            if ($cwIds->isNotEmpty() && $schema->hasTable('center_wallet_transactions'))
                \DB::table('center_wallet_transactions')->whereIn('center_wallet_id', $cwIds)->delete();
            foreach (['center_fee_collection_permissions','center_fee_discount_permissions','center_wallets'] as $t) {
                if ($schema->hasTable($t)) \DB::table($t)->whereIn('center_id', $cIds)->delete();
            }
        }
        \DB::table('centers')->where('institute_id', $id)->delete();

        // Channel Partners
        $pIds = \DB::table('channel_partners')->where('institute_id', $id)->pluck('id');
        if ($pIds->isNotEmpty()) {
            $chIds = \DB::table('channel_wallets')->whereIn('channel_partner_id', $pIds)->pluck('id');
            if ($chIds->isNotEmpty() && $schema->hasTable('channel_wallet_transactions'))
                \DB::table('channel_wallet_transactions')->whereIn('channel_wallet_id', $chIds)->delete();
            if ($schema->hasTable('channel_wallets'))
                \DB::table('channel_wallets')->whereIn('channel_partner_id', $pIds)->delete();
            if ($schema->hasTable('partner_commission_entries'))
                \DB::table('partner_commission_entries')->whereIn('partner_id', $pIds)->delete();
            foreach (['channel_partner_fee_discount_permissions','channel_partner_fee_collection_permissions'] as $t) {
                if ($schema->hasTable($t)) \DB::table($t)->whereIn('channel_partner_id', $pIds)->delete();
            }
        }
        \DB::table('channel_partners')->where('institute_id', $id)->delete();

        // Enquiries (online admission funnel)
        $enqIds = $schema->hasTable('enquiries')
            ? \DB::table('enquiries')->where('institute_id', $id)->pluck('id')
            : collect();
        if ($enqIds->isNotEmpty() && $schema->hasTable('enquiry_follow_ups')) {
            \DB::table('enquiry_follow_ups')->whereIn('enquiry_id', $enqIds)->delete();
        }
        if ($schema->hasTable('enquiries')) \DB::table('enquiries')->where('institute_id', $id)->delete();

        // Student payment claims (online fee payment verification)
        if ($schema->hasTable('payment_claims')) \DB::table('payment_claims')->where('institute_id', $id)->delete();

        // Wallets & Academic
        foreach (['wallet_extension_requests','institute_wallets'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }
        $csIds = \DB::table('courses')->where('institute_id', $id)->pluck('id');
        if ($csIds->isNotEmpty()) {
            $streamIds = \DB::table('course_streams')->whereIn('course_id', $csIds)->pluck('id');
            if ($streamIds->isNotEmpty()) {
                foreach (['stream_year_subject_rules','course_stream_subjects','stream_session_limits'] as $t) {
                    if ($schema->hasTable($t)) \DB::table($t)->whereIn('course_stream_id', $streamIds)->delete();
                }
                \DB::table('course_streams')->whereIn('course_id', $csIds)->delete();
            }
            \DB::table('course_parts')->whereIn('course_id', $csIds)->delete();
        }
        if ($schema->hasTable('fee_assignments')) \DB::table('fee_assignments')->where('institute_id', $id)->delete();
        if ($schema->hasTable('course_document_fees')) \DB::table('course_document_fees')->where('institute_id', $id)->delete();
        if ($schema->hasTable('report_particulars')) \DB::table('report_particulars')->where('institute_id', $id)->delete();
        if ($schema->hasTable('daily_report_headers')) \DB::table('daily_report_headers')->where('institute_id', $id)->delete();
        \DB::table('academic_sessions')->where('institute_id', $id)->delete();
        $subjIds = \DB::table('subjects')->where('institute_id', $id)->pluck('id');
        if ($subjIds->isNotEmpty()) {
            foreach (['subject_components','subject_fee_rules','course_part_subject'] as $t) {
                if ($schema->hasTable($t)) \DB::table($t)->whereIn('subject_id', $subjIds)->delete();
            }
        }
        foreach (['subjects','course_fee_rules','courses','course_types','student_types'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }
        $fpIds = \DB::table('fee_plans')->where('institute_id', $id)->pluck('id');
        if ($fpIds->isNotEmpty()) \DB::table('fee_plan_installments')->whereIn('fee_plan_id', $fpIds)->delete();
        foreach (['fee_plans','fee_types','payment_mode_permissions','institute_bank_accounts'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }
        foreach (['certificate_types','certificate_settings'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }
        $dcIds = \DB::table('document_categories')->where('institute_id', $id)->pluck('id');
        if ($dcIds->isNotEmpty()) {
            $dtIds = \DB::table('document_types')->whereIn('document_category_id', $dcIds)->pluck('id');
            if ($dtIds->isNotEmpty()) {
                \DB::table('document_upload_rules')->whereIn('document_type_id', $dtIds)->delete();
                \DB::table('document_types')->whereIn('document_category_id', $dcIds)->delete();
            }
        }
        if ($schema->hasTable('document_categories')) \DB::table('document_categories')->where('institute_id', $id)->delete();

        // Marksheet/degree dispatch batches
        $docBatchIds = $schema->hasTable('document_batches')
            ? \DB::table('document_batches')->where('institute_id', $id)->pluck('id')
            : collect();
        if ($docBatchIds->isNotEmpty() && $schema->hasTable('document_batch_students')) {
            \DB::table('document_batch_students')->whereIn('document_batch_id', $docBatchIds)->delete();
        }
        if ($schema->hasTable('document_batches')) \DB::table('document_batches')->where('institute_id', $id)->delete();

        foreach (['notice_reads','notices','sms_logs','sms_due_reminder_settings',
                  'sms_provider_settings','admission_form_settings','admission_counters',
                  'fee_invoice_counters','audit_logs','institute_master_otps',
                  'staff_id_counters','partner_id_counters','center_id_counters','library_staff_id_counters'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }
        // Users intentionally NOT deleted — institute login must remain valid after clean.
        // institute_policy_acceptances intentionally NOT deleted — legal/compliance consent
        // record (ToS/privacy acceptance) should survive a data clean, same as the login.
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
                $this->runCleanForInstitute($institute->id);
            });

            \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        } catch (\Throwable $e) {
            \DB::statement('SET FOREIGN_KEY_CHECKS=1');
            \Log::error('Institute clean-data failed', ['institute_id' => $institute->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Kuch galat hua: ' . $e->getMessage());
        }

        AuditLogService::log($institute->id, 'institute', 'data_cleaned', 'All institute data deleted by Super Admin.', $institute);

        return back()->with('success', '"' . $institute->name . '" ka sabhi data successfully delete ho gaya. Institute fresh hai ab.');
    }

    public function exportData(Institute $institute)
    {
        $id       = $institute->id;
        $filename = 'institute_' . Str::slug($institute->institute_uid) . '_' . now()->format('Ymd_His') . '.sql';

        return response()->stream(function () use ($id, $institute) {
            echo "-- ================================================================\n";
            echo "-- Institute Data Export: {$institute->name}\n";
            echo "-- UID: {$institute->institute_uid} | ID: {$id}\n";
            echo "-- Exported: " . now()->toDateTimeString() . "\n";
            echo "-- ================================================================\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n";
            echo "SET NAMES utf8mb4;\n\n";

            // ── Direct institute_id wali tables ─────────────────────────
            $direct = [
                'academic_sessions', 'accounts', 'admission_counters', 'admission_form_settings',
                'attendance_lock_records', 'audit_logs', 'centers', 'center_id_counters',
                'certificate_settings', 'certificate_types', 'channel_partners', 'cheque_payments',
                'contra_entries', 'course_document_fees', 'course_fee_rules', 'course_types', 'courses',
                'daily_report_headers', 'document_batches', 'document_categories', 'employees',
                'employee_advances', 'employee_bonuses', 'employee_departments', 'employee_designations',
                'employee_documents', 'employee_salary_disbursements', 'enquiries',
                'expense_approval_limits', 'expense_categories_l1', 'expense_categories_l2',
                'expense_vendors', 'expenses', 'fee_assignments', 'fee_invoice_counters',
                'fee_invoices', 'fee_plans', 'fee_types', 'finance_settings',
                'institute_bank_accounts', 'institute_income_categories', 'institute_manual_incomes',
                'institute_master_otps', 'institute_policy_acceptances', 'institute_transactions',
                'institute_transport_settings', 'institute_wallets', 'journal_entries',
                'library_authors', 'library_book_copies', 'library_books', 'library_categories',
                'library_fine_payments', 'library_login_logs', 'library_members',
                'library_publishers', 'library_racks', 'library_reservations', 'library_rule_sets',
                'library_staff', 'library_staff_activity_logs', 'library_staff_id_counters',
                'library_staff_permissions', 'library_subjects', 'library_transactions', 'library_vendors',
                'notices', 'partner_id_counters', 'payment_claims', 'payment_mode_permissions',
                'practical_fee_token_batches', 'report_particulars', 'salary_records',
                'sms_due_reminder_settings', 'sms_logs', 'sms_provider_settings',
                'staff_bonuses', 'staff_documents', 'staff_id_counters', 'staff_members', 'staff_roles',
                'student_types', 'students', 'subjects', 'transport_allocations', 'transport_drivers',
                'transport_helpers', 'transport_maintenance_logs', 'transport_payments',
                'transport_route_assignments', 'transport_routes', 'transport_vehicle_types',
                'transport_vehicles', 'users', 'wallet_extension_requests',
            ];
            foreach ($direct as $table) {
                $this->streamTableInserts($table, fn($q) => $q->where('institute_id', $id));
            }

            // ── Child tables ─────────────────────────────────────────────
            // fee_invoice_items
            $invoiceIds = \DB::table('fee_invoices')->where('institute_id', $id)->pluck('id');
            if ($invoiceIds->isNotEmpty())
                $this->streamTableInserts('fee_invoice_items', fn($q) => $q->whereIn('fee_invoice_id', $invoiceIds));

            // fee_plan_installments
            $planIds = \DB::table('fee_plans')->where('institute_id', $id)->pluck('id');
            if ($planIds->isNotEmpty())
                $this->streamTableInserts('fee_plan_installments', fn($q) => $q->whereIn('fee_plan_id', $planIds));

            // journal_entry_lines
            $journalIds = \DB::table('journal_entries')->where('institute_id', $id)->pluck('id');
            if ($journalIds->isNotEmpty())
                $this->streamTableInserts('journal_entry_lines', fn($q) => $q->whereIn('journal_entry_id', $journalIds));

            // course_streams + children
            $courseIds = \DB::table('courses')->where('institute_id', $id)->pluck('id');
            if ($courseIds->isNotEmpty()) {
                $this->streamTableInserts('course_parts', fn($q) => $q->whereIn('course_id', $courseIds));
                $this->streamTableInserts('course_streams', fn($q) => $q->whereIn('course_id', $courseIds));
                $streamIds = \DB::table('course_streams')->whereIn('course_id', $courseIds)->pluck('id');
                if ($streamIds->isNotEmpty()) {
                    $this->streamTableInserts('course_stream_subjects',    fn($q) => $q->whereIn('course_stream_id', $streamIds));
                    $this->streamTableInserts('stream_year_subject_rules', fn($q) => $q->whereIn('course_stream_id', $streamIds));
                    $this->streamTableInserts('stream_session_limits',     fn($q) => $q->whereIn('course_stream_id', $streamIds));
                }
            }

            // subject children
            $subjectIds = \DB::table('subjects')->where('institute_id', $id)->pluck('id');
            if ($subjectIds->isNotEmpty()) {
                $this->streamTableInserts('subject_components', fn($q) => $q->whereIn('subject_id', $subjectIds));
                $this->streamTableInserts('subject_fee_rules',  fn($q) => $q->whereIn('subject_id', $subjectIds));
                $this->streamTableInserts('course_part_subject', fn($q) => $q->whereIn('subject_id', $subjectIds));
            }

            // student children
            $studentIds = \DB::table('students')->where('institute_id', $id)->pluck('id');
            if ($studentIds->isNotEmpty()) {
                foreach (['student_education_details','student_subjects','student_transactions',
                          'student_wallets','student_attendance','certificates','admission_documents',
                          'student_academic_change_logs','subject_change_logs','promotion_logs',
                          'student_academic_identity'] as $t) {
                    $this->streamTableInserts($t, fn($q) => $q->whereIn('student_id', $studentIds));
                }
            }

            // staff children
            $staffIds = \DB::table('staff_members')->where('institute_id', $id)->pluck('id');
            if ($staffIds->isNotEmpty()) {
                foreach (['staff_attendance','staff_loans','staff_course_permissions',
                          'staff_fee_collection_permissions','staff_fee_discount_permissions',
                          'staff_permission_overrides'] as $t) {
                    $this->streamTableInserts($t, fn($q) => $q->whereIn('staff_member_id', $staffIds));
                }
            }

            // employee children
            $employeeIds = \DB::table('employees')->where('institute_id', $id)->pluck('id');
            if ($employeeIds->isNotEmpty()) {
                $this->streamTableInserts('employee_salary_components', fn($q) => $q->whereIn('employee_id', $employeeIds));
            }

            // enquiry follow-ups
            $enquiryIds = \DB::table('enquiries')->where('institute_id', $id)->pluck('id');
            if ($enquiryIds->isNotEmpty()) {
                $this->streamTableInserts('enquiry_follow_ups', fn($q) => $q->whereIn('enquiry_id', $enquiryIds));
            }

            // document batch children
            $docBatchIds = \DB::table('document_batches')->where('institute_id', $id)->pluck('id');
            if ($docBatchIds->isNotEmpty()) {
                $this->streamTableInserts('document_batch_students', fn($q) => $q->whereIn('document_batch_id', $docBatchIds));
            }

            // center children
            $centerIds = \DB::table('centers')->where('institute_id', $id)->pluck('id');
            if ($centerIds->isNotEmpty()) {
                $this->streamTableInserts('center_fee_collection_permissions', fn($q) => $q->whereIn('center_id', $centerIds));
                $this->streamTableInserts('center_fee_discount_permissions',   fn($q) => $q->whereIn('center_id', $centerIds));
                $this->streamTableInserts('center_wallets',                    fn($q) => $q->whereIn('center_id', $centerIds));
                $cwIds = \DB::table('center_wallets')->whereIn('center_id', $centerIds)->pluck('id');
                if ($cwIds->isNotEmpty())
                    $this->streamTableInserts('center_wallet_transactions', fn($q) => $q->whereIn('center_wallet_id', $cwIds));
            }

            // channel partner children
            $partnerIds = \DB::table('channel_partners')->where('institute_id', $id)->pluck('id');
            if ($partnerIds->isNotEmpty()) {
                $this->streamTableInserts('partner_commission_entries', fn($q) => $q->whereIn('partner_id', $partnerIds));
                $this->streamTableInserts('channel_wallets',            fn($q) => $q->whereIn('channel_partner_id', $partnerIds));
                $this->streamTableInserts('channel_partner_fee_discount_permissions',   fn($q) => $q->whereIn('channel_partner_id', $partnerIds));
                $this->streamTableInserts('channel_partner_fee_collection_permissions', fn($q) => $q->whereIn('channel_partner_id', $partnerIds));
                $chIds = \DB::table('channel_wallets')->whereIn('channel_partner_id', $partnerIds)->pluck('id');
                if ($chIds->isNotEmpty())
                    $this->streamTableInserts('channel_wallet_transactions', fn($q) => $q->whereIn('channel_wallet_id', $chIds));
            }

            // transport children — both tables have institute_id directly
            $this->streamTableInserts('transport_vehicle_documents', fn($q) => $q->where('institute_id', $id));
            $this->streamTableInserts('transport_driver_documents',  fn($q) => $q->where('institute_id', $id));
            $routeIds = \DB::table('transport_routes')->where('institute_id', $id)->pluck('id');
            if ($routeIds->isNotEmpty()) {
                $this->streamTableInserts('transport_route_stops', fn($q) => $q->whereIn('transport_route_id', $routeIds));
            }

            // library pivot
            $bookIds = \DB::table('library_books')->where('institute_id', $id)->pluck('id');
            if ($bookIds->isNotEmpty())
                $this->streamTableInserts('library_book_author', fn($q) => $q->whereIn('book_id', $bookIds));

            // notices pivot
            $this->streamTableInserts('notice_reads', fn($q) => $q->where('institute_id', $id));

            // practical fee token entries
            $batchIds = \DB::table('practical_fee_token_batches')->where('institute_id', $id)->pluck('id');
            if ($batchIds->isNotEmpty())
                $this->streamTableInserts('practical_fee_token_entries', fn($q) => $q->whereIn('batch_id', $batchIds));

            // document types + upload rules
            $docCatIds = \DB::table('document_categories')->where('institute_id', $id)->pluck('id');
            if ($docCatIds->isNotEmpty()) {
                $this->streamTableInserts('document_types', fn($q) => $q->whereIn('document_category_id', $docCatIds));
                $docTypeIds = \DB::table('document_types')->whereIn('document_category_id', $docCatIds)->pluck('id');
                if ($docTypeIds->isNotEmpty())
                    $this->streamTableInserts('document_upload_rules', fn($q) => $q->whereIn('document_type_id', $docTypeIds));
            }

            echo "\nSET FOREIGN_KEY_CHECKS=1;\n";
            echo "-- Export complete.\n";
        }, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'X-Accel-Buffering'   => 'no',
        ]);
    }

    private function streamTableInserts(string $table, \Closure $scope): void
    {
        if (!\DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        $rows = $scope(\DB::table($table))->get();

        if ($rows->isEmpty()) {
            return;
        }

        $columns = array_keys((array) $rows->first());
        $colList  = implode(', ', array_map(fn($c) => "`{$c}`", $columns));

        echo "-- {$table} ({$rows->count()} rows)\n";

        foreach ($rows as $row) {
            $values = implode(', ', array_map(function ($v) {
                if ($v === null) return 'NULL';
                if (is_int($v) || is_float($v)) return $v;
                return "'" . str_replace(["\\", "'", "\n", "\r"], ["\\\\", "\\'", "\\n", "\\r"], (string) $v) . "'";
            }, (array) $row));

            echo "INSERT INTO `{$table}` ({$colList}) VALUES ({$values});\n";
        }

        echo "\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

}
