<?php

namespace App\Http\Controllers\Institute\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataExportController extends Controller
{
    public function download()
    {
        $user      = Auth::user();
        $id        = (int) $user->institute_id;
        $institute = DB::table('institutes')->where('id', $id)->first();

        abort_if(! $institute, 403);

        $filename = 'backup_' . Str::slug($institute->institute_uid) . '_' . now()->format('Ymd_His') . '.sql';

        return response()->stream(function () use ($id, $institute) {
            echo "-- ================================================================\n";
            echo "-- Institute Data Backup: {$institute->name}\n";
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
                'transport_drivers', 'transport_maintenance_logs', 'transport_monthly_charges',
                'transport_payments', 'transport_routes', 'transport_vehicles',
                'wallet_extension_requests',
            ];
            foreach ($direct as $table) {
                $this->streamTableInserts($table, fn($q) => $q->where('institute_id', $id));
            }

            // ── Child tables ─────────────────────────────────────────────
            $invoiceIds = DB::table('fee_invoices')->where('institute_id', $id)->pluck('id');
            if ($invoiceIds->isNotEmpty())
                $this->streamTableInserts('fee_invoice_items', fn($q) => $q->whereIn('fee_invoice_id', $invoiceIds));

            $planIds = DB::table('fee_plans')->where('institute_id', $id)->pluck('id');
            if ($planIds->isNotEmpty())
                $this->streamTableInserts('fee_plan_installments', fn($q) => $q->whereIn('fee_plan_id', $planIds));

            $journalIds = DB::table('journal_entries')->where('institute_id', $id)->pluck('id');
            if ($journalIds->isNotEmpty())
                $this->streamTableInserts('journal_entry_lines', fn($q) => $q->whereIn('journal_entry_id', $journalIds));

            $courseIds = DB::table('courses')->where('institute_id', $id)->pluck('id');
            if ($courseIds->isNotEmpty()) {
                $this->streamTableInserts('course_parts',   fn($q) => $q->whereIn('course_id', $courseIds));
                $this->streamTableInserts('course_streams', fn($q) => $q->whereIn('course_id', $courseIds));
                $streamIds = DB::table('course_streams')->whereIn('course_id', $courseIds)->pluck('id');
                if ($streamIds->isNotEmpty()) {
                    $this->streamTableInserts('course_stream_subjects',    fn($q) => $q->whereIn('course_stream_id', $streamIds));
                    $this->streamTableInserts('stream_year_subject_rules', fn($q) => $q->whereIn('course_stream_id', $streamIds));
                    $this->streamTableInserts('stream_session_limits',     fn($q) => $q->whereIn('course_stream_id', $streamIds));
                }
            }

            $subjectIds = DB::table('subjects')->where('institute_id', $id)->pluck('id');
            if ($subjectIds->isNotEmpty()) {
                $this->streamTableInserts('subject_components', fn($q) => $q->whereIn('subject_id', $subjectIds));
                $this->streamTableInserts('subject_fee_rules',  fn($q) => $q->whereIn('subject_id', $subjectIds));
            }

            $studentIds = DB::table('students')->where('institute_id', $id)->pluck('id');
            if ($studentIds->isNotEmpty()) {
                foreach (['student_education_details','student_subjects','student_transactions',
                          'student_wallets','student_attendance','certificates','admission_documents',
                          'student_academic_change_logs','subject_change_logs','promotion_logs',
                          'student_academic_identities'] as $t) {
                    $this->streamTableInserts($t, fn($q) => $q->whereIn('student_id', $studentIds));
                }
            }

            $staffIds = DB::table('staff_members')->where('institute_id', $id)->pluck('id');
            if ($staffIds->isNotEmpty()) {
                foreach (['staff_attendance','staff_loans','staff_course_permissions',
                          'staff_fee_collection_permissions','staff_fee_discount_permissions',
                          'staff_permission_overrides'] as $t) {
                    $this->streamTableInserts($t, fn($q) => $q->whereIn('staff_member_id', $staffIds));
                }
            }

            $centerIds = DB::table('centers')->where('institute_id', $id)->pluck('id');
            if ($centerIds->isNotEmpty()) {
                $this->streamTableInserts('center_fee_collection_permissions', fn($q) => $q->whereIn('center_id', $centerIds));
                $this->streamTableInserts('center_fee_discount_permissions',   fn($q) => $q->whereIn('center_id', $centerIds));
                $this->streamTableInserts('center_wallets',                    fn($q) => $q->whereIn('center_id', $centerIds));
                $cwIds = DB::table('center_wallets')->whereIn('center_id', $centerIds)->pluck('id');
                if ($cwIds->isNotEmpty())
                    $this->streamTableInserts('center_wallet_transactions', fn($q) => $q->whereIn('center_wallet_id', $cwIds));
            }

            $partnerIds = DB::table('channel_partners')->where('institute_id', $id)->pluck('id');
            if ($partnerIds->isNotEmpty()) {
                $this->streamTableInserts('partner_commission_entries', fn($q) => $q->whereIn('partner_id', $partnerIds));
                $this->streamTableInserts('channel_wallets',            fn($q) => $q->whereIn('channel_partner_id', $partnerIds));
                $chIds = DB::table('channel_wallets')->whereIn('channel_partner_id', $partnerIds)->pluck('id');
                if ($chIds->isNotEmpty())
                    $this->streamTableInserts('channel_wallet_transactions', fn($q) => $q->whereIn('channel_wallet_id', $chIds));
            }

            $vehicleIds = DB::table('transport_vehicles')->where('institute_id', $id)->pluck('id');
            if ($vehicleIds->isNotEmpty())
                $this->streamTableInserts('transport_vehicle_documents', fn($q) => $q->whereIn('vehicle_id', $vehicleIds));

            $driverIds = DB::table('transport_drivers')->where('institute_id', $id)->pluck('id');
            if ($driverIds->isNotEmpty())
                $this->streamTableInserts('transport_driver_documents', fn($q) => $q->whereIn('driver_id', $driverIds));

            $routeIds = DB::table('transport_routes')->where('institute_id', $id)->pluck('id');
            if ($routeIds->isNotEmpty())
                $this->streamTableInserts('transport_route_stops', fn($q) => $q->whereIn('route_id', $routeIds));

            $bookIds = DB::table('library_books')->where('institute_id', $id)->pluck('id');
            if ($bookIds->isNotEmpty())
                $this->streamTableInserts('library_book_author', fn($q) => $q->whereIn('book_id', $bookIds));

            $noticeIds = DB::table('notices')->where('institute_id', $id)->pluck('id');
            if ($noticeIds->isNotEmpty())
                $this->streamTableInserts('notice_reads', fn($q) => $q->whereIn('notice_id', $noticeIds));

            $batchIds = DB::table('practical_fee_token_batches')->where('institute_id', $id)->pluck('id');
            if ($batchIds->isNotEmpty())
                $this->streamTableInserts('practical_fee_token_entries', fn($q) => $q->whereIn('batch_id', $batchIds));

            $docCatIds = DB::table('document_categories')->where('institute_id', $id)->pluck('id');
            if ($docCatIds->isNotEmpty()) {
                $this->streamTableInserts('document_types', fn($q) => $q->whereIn('document_category_id', $docCatIds));
                $docTypeIds = DB::table('document_types')->whereIn('document_category_id', $docCatIds)->pluck('id');
                if ($docTypeIds->isNotEmpty())
                    $this->streamTableInserts('document_upload_rules', fn($q) => $q->whereIn('document_type_id', $docTypeIds));
            }

            echo "\nSET FOREIGN_KEY_CHECKS=1;\n";
            echo "-- Backup complete.\n";
        }, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'X-Accel-Buffering'   => 'no',
        ]);
    }

    private function streamTableInserts(string $table, \Closure $scope): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        $rows = $scope(DB::table($table))->get();

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
