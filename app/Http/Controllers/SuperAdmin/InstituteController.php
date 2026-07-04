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

        try {
            // ── Step 1: Clean existing data for this institute ────────────
            $this->runCleanForInstitute($institute->id);

            // ── Step 2: Parse & execute SQL statements ────────────────────
            $restored = 0;
            $buffer   = '';

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
            return back()->with('error', 'Restore failed: ' . $e->getMessage());
        }

        return back()->with('success',
            "\"{$institute->name}\" ka data successfully restore ho gaya. " .
            number_format($restored) . " rows restore hue."
        );
    }

    private function runCleanForInstitute(int $id): void
    {
        // Reuse same delete logic as cleanData — inline to avoid duplication via closure
        $schema = \DB::getSchemaBuilder();

        // Library
        $libStaffIds = \DB::table('library_staff')->where('institute_id', $id)->pluck('id');
        if ($libStaffIds->isNotEmpty()) {
            foreach (['library_staff_activity_logs','library_login_logs','library_staff_permissions'] as $t) {
                if ($schema->hasTable($t)) \DB::table($t)->whereIn('library_staff_id', $libStaffIds)->delete();
            }
        }
        foreach (['library_fine_payments','library_reservations','library_transactions','library_members',
                  'library_book_copies','library_books','library_racks','library_rule_sets',
                  'library_publishers','library_authors','library_categories','library_subjects',
                  'library_vendors','library_staff'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }

        // Transport
        foreach (['transport_payments','transport_monthly_charges','transport_maintenance_logs'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }
        $vIds = \DB::table('transport_vehicles')->where('institute_id', $id)->pluck('id');
        if ($vIds->isNotEmpty()) \DB::table('transport_vehicle_documents')->whereIn('transport_vehicle_id', $vIds)->delete();
        $dIds = \DB::table('transport_drivers')->where('institute_id', $id)->pluck('id');
        if ($dIds->isNotEmpty() && $schema->hasTable('transport_driver_documents'))
            \DB::table('transport_driver_documents')->whereIn('transport_driver_id', $dIds)->delete();
        $rIds = \DB::table('transport_routes')->where('institute_id', $id)->pluck('id');
        if ($rIds->isNotEmpty()) \DB::table('transport_route_stops')->whereIn('route_id', $rIds)->delete();
        foreach (['transport_vehicles','transport_drivers','transport_routes'] as $t) {
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
            if ($schema->hasTable('student_academic_identities'))
                \DB::table('student_academic_identities')->whereIn('student_id', $stuIds)->delete();
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
        foreach (['staff_members','staff_roles','attendance_lock_records'] as $t) {
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
        }
        \DB::table('channel_partners')->where('institute_id', $id)->delete();

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
        \DB::table('academic_sessions')->where('institute_id', $id)->delete();
        $subjIds = \DB::table('subjects')->where('institute_id', $id)->pluck('id');
        if ($subjIds->isNotEmpty()) {
            foreach (['subject_components','subject_fee_rules'] as $t) {
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
        foreach (['notice_reads','notices','sms_logs','sms_due_reminder_settings',
                  'sms_provider_settings','admission_form_settings','admission_counters',
                  'fee_invoice_counters','audit_logs'] as $t) {
            if ($schema->hasTable($t)) \DB::table($t)->where('institute_id', $id)->delete();
        }
        \DB::table('users')->where('institute_id', $id)->delete();
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
                foreach (['transport_payments','transport_maintenance_logs'] as $tbl) {
                    if (\DB::getSchemaBuilder()->hasTable($tbl)) \DB::table($tbl)->where('institute_id', $id)->delete();
                }
                $vehicleIds = \DB::table('transport_vehicles')->where('institute_id', $id)->pluck('id');
                if ($vehicleIds->isNotEmpty()) \DB::table('transport_vehicle_documents')->whereIn('transport_vehicle_id', $vehicleIds)->delete();
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
                foreach (['notice_reads','notices','sms_logs','sms_due_reminder_settings','sms_provider_settings'] as $tbl) {
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
                'attendance_lock_records', 'audit_logs', 'centers', 'certificate_settings',
                'certificate_types', 'channel_partners', 'cheque_payments', 'contra_entries',
                'course_fee_rules', 'course_types', 'courses', 'document_categories',
                'expense_approval_limits', 'expense_categories_l1', 'expense_categories_l2',
                'expense_vendors', 'expenses', 'fee_assignments', 'fee_invoice_counters',
                'fee_invoices', 'fee_plans', 'fee_types', 'finance_settings',
                'institute_bank_accounts', 'institute_income_categories', 'institute_manual_incomes',
                'institute_transactions', 'institute_wallets', 'journal_entries',
                'library_authors', 'library_book_copies', 'library_books', 'library_categories',
                'library_fine_payments', 'library_login_logs', 'library_members',
                'library_publishers', 'library_racks', 'library_reservations', 'library_rule_sets',
                'library_staff', 'library_staff_activity_logs', 'library_staff_permissions',
                'library_subjects', 'library_transactions', 'library_vendors', 'notices',
                'payment_mode_permissions', 'practical_fee_token_batches', 'salary_records',
                'sms_due_reminder_settings', 'sms_logs', 'sms_provider_settings',
                'staff_members', 'staff_roles', 'student_types', 'students', 'subjects',
                'transport_drivers', 'transport_maintenance_logs',
                'transport_payments', 'transport_routes', 'transport_vehicles',
                'users', 'wallet_extension_requests',
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
            }

            // student children
            $studentIds = \DB::table('students')->where('institute_id', $id)->pluck('id');
            if ($studentIds->isNotEmpty()) {
                foreach (['student_education_details','student_subjects','student_transactions',
                          'student_wallets','student_attendance','certificates','admission_documents',
                          'student_academic_change_logs','subject_change_logs','promotion_logs',
                          'student_academic_identities'] as $t) {
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
                $chIds = \DB::table('channel_wallets')->whereIn('channel_partner_id', $partnerIds)->pluck('id');
                if ($chIds->isNotEmpty())
                    $this->streamTableInserts('channel_wallet_transactions', fn($q) => $q->whereIn('channel_wallet_id', $chIds));
            }

            // transport children
            $vehicleIds = \DB::table('transport_vehicles')->where('institute_id', $id)->pluck('id');
            if ($vehicleIds->isNotEmpty())
                $this->streamTableInserts('transport_vehicle_documents', fn($q) => $q->whereIn('transport_vehicle_id', $vehicleIds));
            $driverIds = \DB::table('transport_drivers')->where('institute_id', $id)->pluck('id');
            if ($driverIds->isNotEmpty())
                $this->streamTableInserts('transport_driver_documents', fn($q) => $q->whereIn('transport_driver_id', $driverIds));
            $routeIds = \DB::table('transport_routes')->where('institute_id', $id)->pluck('id');
            if ($routeIds->isNotEmpty())
                $this->streamTableInserts('transport_route_stops', fn($q) => $q->whereIn('route_id', $routeIds));

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

    private function generateInstituteUID(): string
    {
        $year = now()->year;

        $count = Institute::whereYear('created_at', $year)->lockForUpdate()->count() + 1;

        return 'GT/' . $year . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
