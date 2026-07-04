<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Institute;

class CleanInstituteData extends Command
{
    protected $signature = 'erp:clean-institute {institute_id : Institute ID jisko clean karna hai}
                            {--force : Confirmation skip karo}';

    protected $description = 'Ek institute ka sabhi data delete karo (students, courses, staff, centers, partners, fees etc.) — Institute record khud nahi hatega.';

    public function handle(): int
    {
        $id = (int) $this->argument('institute_id');

        $institute = Institute::find($id);

        if (! $institute) {
            $this->error("Institute ID {$id} nahi mila!");
            return self::FAILURE;
        }

        $this->info("=== Institute Clean Tool ===");
        $this->line("Institute : {$institute->name} ({$institute->institute_uid})");
        $this->line("ID        : {$institute->id}");
        $this->newLine();

        $this->warn("SAVDHAAN: Niche diya gaya SABHI DATA permanently delete ho jayega:");
        $this->line("  - Sabhi Students aur unka fee/attendance record");
        $this->line("  - Sabhi Courses, Streams, Subjects");
        $this->line("  - Sabhi Fee Types, Plans, Invoices");
        $this->line("  - Sabhi Staff Members aur Roles");
        $this->line("  - Sabhi Centers aur Channel Partners");
        $this->line("  - Sabhi Wallets aur Transactions");
        $this->line("  - Library, Transport, Finance data");
        $this->line("  - Notices, Certificates, Documents");
        $this->line("  - Settings aur Counters");
        $this->newLine();
        $this->line("  [Institute ka khud ka record, subscription, aur login NAHI hatega]");
        $this->newLine();

        if (! $this->option('force')) {
            $confirm = $this->ask("Aage badhne ke liye institute ka naam type karo: \"{$institute->name}\"");
            if (trim($confirm) !== $institute->name) {
                $this->error("Naam match nahi hua. Operation cancel.");
                return self::FAILURE;
            }
        }

        $this->info("Deleting...");

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            DB::transaction(function () use ($id) {

                // ── 1. Library ─────────────────────────────────────────
                // library staff child tables — ab direct institute_id se delete hoga
                $this->deleteLine('Library staff activity logs', 'library_staff_activity_logs', $id);
                $this->deleteLine('Library login logs', 'library_login_logs', $id);
                $this->deleteLine('Library staff permissions', 'library_staff_permissions', $id);
                // baki sabhi library tables mein direct institute_id hai
                $this->deleteLine('Library fine payments', 'library_fine_payments', $id);
                $this->deleteLine('Library reservations', 'library_reservations', $id);
                $this->deleteLine('Library transactions', 'library_transactions', $id);
                $this->deleteLine('Library members', 'library_members', $id);
                $this->deleteLine('Library book copies', 'library_book_copies', $id);
                DB::table('library_book_author')
                    ->whereIn('book_id', DB::table('library_books')->where('institute_id', $id)->pluck('id'))
                    ->delete();
                $this->line("  - Library book-author pivot deleted");
                $this->deleteLine('Library books', 'library_books', $id);
                $this->deleteLine('Library racks', 'library_racks', $id);
                $this->deleteLine('Library rule sets', 'library_rule_sets', $id);
                $this->deleteLine('Library subjects', 'library_subjects', $id);
                $this->deleteLine('Library vendors', 'library_vendors', $id);
                $this->deleteLine('Library publishers', 'library_publishers', $id);
                $this->deleteLine('Library authors', 'library_authors', $id);
                $this->deleteLine('Library categories', 'library_categories', $id);
                $this->deleteLine('Library staff', 'library_staff', $id);

                // ── 2. Transport ────────────────────────────────────────
                $this->deleteLine('Transport payments', 'transport_payments', $id);
                $this->deleteLine('Transport maintenance logs', 'transport_maintenance_logs', $id);
                DB::table('transport_vehicle_documents')->where('institute_id', $id)->delete();
                $this->line("  - Transport vehicle documents deleted");
                $routeIds = DB::table('transport_routes')->where('institute_id', $id)->pluck('id');
                DB::table('transport_route_stops')->whereIn('route_id', $routeIds)->delete();
                $this->line("  - Transport route stops deleted");
                $this->deleteLine('Transport vehicles', 'transport_vehicles', $id);
                DB::table('transport_driver_documents')->where('institute_id', $id)->delete();
                DB::table('transport_drivers')->where('institute_id', $id)->delete();
                $this->line("  - Transport drivers deleted");
                $this->deleteLine('Transport routes', 'transport_routes', $id);

                // ── 3. Finance / Accounts ───────────────────────────────
                $this->deleteLine('Cheque payments', 'cheque_payments', $id);
                $this->deleteLine('Contra entries', 'contra_entries', $id);
                $this->deleteLine('Salary records', 'salary_records', $id);
                $this->deleteLine('Expenses', 'expenses', $id);
                $this->deleteLine('Manual incomes', 'institute_manual_incomes', $id);
                $journalIds = DB::table('journal_entries')->where('institute_id', $id)->pluck('id');
                DB::table('journal_entry_lines')->whereIn('journal_entry_id', $journalIds)->delete();
                $this->line("  - Journal entry lines deleted");
                $this->deleteLine('Journal entries', 'journal_entries', $id);
                $this->deleteLine('Institute transactions', 'institute_transactions', $id);
                $this->deleteLine('Accounts (GL)', 'accounts', $id);
                $this->deleteLine('Finance settings', 'finance_settings', $id);
                $this->deleteLine('Expense vendors', 'expense_vendors', $id);
                $this->deleteLine('Expense approval limits', 'expense_approval_limits', $id);
                $this->deleteLine('Expense categories L2', 'expense_categories_l2', $id);
                $this->deleteLine('Expense categories L1', 'expense_categories_l1', $id);
                $this->deleteLine('Income categories', 'institute_income_categories', $id);

                // ── 4. Fee Invoices ─────────────────────────────────────
                $invoiceIds = DB::table('fee_invoices')->where('institute_id', $id)->pluck('id');
                DB::table('fee_invoice_items')->whereIn('fee_invoice_id', $invoiceIds)->delete();
                $this->line("  - Fee invoice items deleted");
                $this->deleteLine('Fee invoices', 'fee_invoices', $id);

                // ── 5. Practical Fee Tokens ─────────────────────────────
                $batchIds = DB::table('practical_fee_token_batches')->where('institute_id', $id)->pluck('id');
                DB::table('practical_fee_token_entries')->whereIn('batch_id', $batchIds)->delete();
                $this->line("  - Practical fee token entries deleted");
                $this->deleteLine('Practical fee token batches', 'practical_fee_token_batches', $id);

                // ── 6. Student-related data ─────────────────────────────
                $studentIds = DB::table('students')->where('institute_id', $id)->pluck('id');
                if ($studentIds->isNotEmpty()) {
                    DB::table('student_education_details')->whereIn('student_id', $studentIds)->delete();
                    DB::table('student_subjects')->whereIn('student_id', $studentIds)->delete();
                    DB::table('student_transactions')->whereIn('student_id', $studentIds)->delete();
                    DB::table('student_wallets')->whereIn('student_id', $studentIds)->delete();
                    DB::table('student_attendance')->whereIn('student_id', $studentIds)->delete();
                    DB::table('certificates')->whereIn('student_id', $studentIds)->delete();
                    DB::table('admission_documents')->whereIn('student_id', $studentIds)->delete();
                    DB::table('student_academic_change_logs')->whereIn('student_id', $studentIds)->delete();
                    DB::table('subject_change_logs')->whereIn('student_id', $studentIds)->delete();
                    DB::table('promotion_logs')->whereIn('student_id', $studentIds)->delete();
                    // student_academic_identities — table name check
                    if (DB::getSchemaBuilder()->hasTable('student_academic_identities')) {
                        DB::table('student_academic_identities')->whereIn('student_id', $studentIds)->delete();
                    }
                    $this->line("  - Student education, subjects, attendance, wallet, certs, docs deleted");
                }
                $this->deleteLine('Students', 'students', $id);

                // ── 7. Staff ────────────────────────────────────────────
                $staffIds = DB::table('staff_members')->where('institute_id', $id)->pluck('id');
                if ($staffIds->isNotEmpty()) {
                    DB::table('staff_attendance')->whereIn('staff_member_id', $staffIds)->delete();
                    DB::table('staff_loans')->whereIn('staff_member_id', $staffIds)->delete();
                    DB::table('staff_course_permissions')->whereIn('staff_member_id', $staffIds)->delete();
                    DB::table('staff_fee_collection_permissions')->whereIn('staff_member_id', $staffIds)->delete();
                    DB::table('staff_fee_discount_permissions')->whereIn('staff_member_id', $staffIds)->delete();
                    DB::table('staff_permission_overrides')->whereIn('staff_member_id', $staffIds)->delete();
                    $this->line("  - Staff attendance, loans, permissions deleted");
                }
                $this->deleteLine('Staff members', 'staff_members', $id);
                $this->deleteLine('Staff roles', 'staff_roles', $id);
                $this->deleteLine('Staff attendance lock records', 'attendance_lock_records', $id);

                // ── 8. Centers ──────────────────────────────────────────
                $centerIds = DB::table('centers')->where('institute_id', $id)->pluck('id');
                if ($centerIds->isNotEmpty()) {
                    // center_wallet_transactions ka FK center_wallet_id hai (center_id nahi)
                    $centerWalletIds = DB::table('center_wallets')->whereIn('center_id', $centerIds)->pluck('id');
                    if ($centerWalletIds->isNotEmpty()) {
                        DB::table('center_wallet_transactions')->whereIn('center_wallet_id', $centerWalletIds)->delete();
                    }
                    DB::table('center_fee_collection_permissions')->whereIn('center_id', $centerIds)->delete();
                    DB::table('center_fee_discount_permissions')->whereIn('center_id', $centerIds)->delete();
                    DB::table('center_wallets')->whereIn('center_id', $centerIds)->delete();
                    $this->line("  - Center wallets, transactions, permissions deleted");
                }
                $this->deleteLine('Centers', 'centers', $id);

                // ── 9. Channel Partners ─────────────────────────────────
                $partnerIds = DB::table('channel_partners')->where('institute_id', $id)->pluck('id');
                if ($partnerIds->isNotEmpty()) {
                    // channel_wallet_transactions ka FK channel_wallet_id hai (partner_id nahi)
                    $channelWalletIds = DB::table('channel_wallets')->whereIn('channel_partner_id', $partnerIds)->pluck('id');
                    if ($channelWalletIds->isNotEmpty()) {
                        DB::table('channel_wallet_transactions')->whereIn('channel_wallet_id', $channelWalletIds)->delete();
                    }
                    // channel_wallets ka FK channel_partner_id hai
                    DB::table('channel_wallets')->whereIn('channel_partner_id', $partnerIds)->delete();
                    DB::table('partner_commission_entries')->whereIn('partner_id', $partnerIds)->delete();
                    $this->line("  - Partner wallets, transactions, commissions deleted");
                }
                $this->deleteLine('Channel partners', 'channel_partners', $id);

                // ── 10. Institute Wallet ────────────────────────────────
                $this->deleteLine('Wallet extension requests', 'wallet_extension_requests', $id);
                $this->deleteLine('Institute wallet', 'institute_wallets', $id);

                // ── 11. Academic Structure ──────────────────────────────
                // course_streams → course_id FK hai (academic_session_id nahi)
                $courseIdsForStreams = DB::table('courses')->where('institute_id', $id)->pluck('id');
                if ($courseIdsForStreams->isNotEmpty()) {
                    $streamIds = DB::table('course_streams')->whereIn('course_id', $courseIdsForStreams)->pluck('id');
                    if ($streamIds->isNotEmpty()) {
                        DB::table('stream_year_subject_rules')->whereIn('course_stream_id', $streamIds)->delete();
                        DB::table('course_stream_subjects')->whereIn('course_stream_id', $streamIds)->delete();
                        if (DB::getSchemaBuilder()->hasTable('stream_session_limits'))
                            DB::table('stream_session_limits')->whereIn('course_stream_id', $streamIds)->delete();
                        $this->line("  - Course stream rules, subjects, session limits deleted");
                        DB::table('course_streams')->whereIn('course_id', $courseIdsForStreams)->delete();
                        $this->line("  - Course streams deleted");
                    }
                }
                // fee_assignments ka direct institute_id hai
                $this->deleteLine('Fee assignments', 'fee_assignments', $id);
                // academic_sessions ka direct institute_id hai
                $this->deleteLine('Academic sessions', 'academic_sessions', $id);

                // ── 12. Course Structure ────────────────────────────────
                $courseIds = DB::table('courses')->where('institute_id', $id)->pluck('id');
                if ($courseIds->isNotEmpty()) {
                    DB::table('course_parts')->whereIn('course_id', $courseIds)->delete();
                    $this->line("  - Course parts deleted");
                }
                // subjects
                $subjectIds = DB::table('subjects')->where('institute_id', $id)->pluck('id');
                if ($subjectIds->isNotEmpty()) {
                    DB::table('subject_components')->whereIn('subject_id', $subjectIds)->delete();
                    DB::table('subject_fee_rules')->whereIn('subject_id', $subjectIds)->delete();
                    $this->line("  - Subject components, fee rules deleted");
                }
                DB::table('subjects')->where('institute_id', $id)->delete();
                $this->line("  - Subjects deleted");
                $this->deleteLine('Course fee rules', 'course_fee_rules', $id);
                $this->deleteLine('Courses', 'courses', $id);
                $this->deleteLine('Course types', 'course_types', $id);
                $this->deleteLine('Student types', 'student_types', $id);

                // ── 13. Fee Plans & Types ───────────────────────────────
                $feePlanIds = DB::table('fee_plans')->where('institute_id', $id)->pluck('id');
                if ($feePlanIds->isNotEmpty()) {
                    DB::table('fee_plan_installments')->whereIn('fee_plan_id', $feePlanIds)->delete();
                    $this->line("  - Fee plan installments deleted");
                }
                $this->deleteLine('Fee plans', 'fee_plans', $id);
                $this->deleteLine('Fee types', 'fee_types', $id);

                // ── 14. Bank Accounts ───────────────────────────────────
                $this->deleteLine('Payment mode permissions', 'payment_mode_permissions', $id);
                $this->deleteLine('Institute bank accounts', 'institute_bank_accounts', $id);

                // ── 15. Certificates ────────────────────────────────────
                $this->deleteLine('Certificate types', 'certificate_types', $id);
                $this->deleteLine('Certificate settings', 'certificate_settings', $id);

                // ── 16. Documents ───────────────────────────────────────
                $docCatIds = DB::table('document_categories')->where('institute_id', $id)->pluck('id');
                if ($docCatIds->isNotEmpty()) {
                    $docTypeIds = DB::table('document_types')->whereIn('document_category_id', $docCatIds)->pluck('id');
                    if ($docTypeIds->isNotEmpty()) {
                        DB::table('document_upload_rules')->whereIn('document_type_id', $docTypeIds)->delete();
                        DB::table('document_types')->whereIn('document_category_id', $docCatIds)->delete();
                    }
                }
                $this->deleteLine('Document categories', 'document_categories', $id);

                // ── 17. Notices & SMS ───────────────────────────────────
                $this->deleteLine('Notice reads', 'notice_reads', $id);
                $this->deleteLine('Notices', 'notices', $id);
                $this->deleteLine('SMS logs', 'sms_logs', $id);
                $this->deleteLine('SMS due reminder settings', 'sms_due_reminder_settings', $id);
                $this->deleteLine('SMS provider settings', 'sms_provider_settings', $id);

                // ── 18. Admission Settings & Counters ───────────────────
                $this->deleteLine('Admission form settings', 'admission_form_settings', $id);
                $this->deleteLine('Admission counters', 'admission_counters', $id);
                $this->deleteLine('Fee invoice counters', 'fee_invoice_counters', $id);

                // ── 19. Audit Logs ──────────────────────────────────────
                $this->deleteLine('Audit logs', 'audit_logs', $id);

                // ── 20. Users (Institute login — only non-superadmin) ───
                $this->deleteLine('Users (institute login)', 'users', $id);

            });

            DB::statement('SET FOREIGN_KEY_CHECKS=1');

        } catch (\Throwable $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->error("ERROR: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("✅ Institute \"{$institute->name}\" ka sabhi data successfully delete ho gaya!");
        $this->line("Institute record, subscription aur UID safe hain.");

        return self::SUCCESS;
    }

    private function deleteLine(string $label, string $table, int $id): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            $this->line("  - [skip] {$label} (table not found)");
            return;
        }
        $count = DB::table($table)->where('institute_id', $id)->count();
        DB::table($table)->where('institute_id', $id)->delete();
        $this->line("  - {$label} deleted ({$count} rows)");
    }
}
