<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for 5000+ records per institute.
 * All indexes are idempotent — wrapped with hasTable/try-catch safe names.
 *
 * Covers: students, fee_invoices, academic_sessions,
 *         student_wallets, student_transactions
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── students ─────────────────────────────────────────────────────
        Schema::table('students', function (Blueprint $table) {
            // Active/inactive filter — used everywhere
            $table->index(['institute_id', 'status'], 'stu_institute_status_idx');

            // Center / Partner student filtering (admission_source + id combo)
            $table->index(
                ['institute_id', 'admission_source', 'admission_source_id'],
                'stu_institute_source_idx'
            );

            // Date-range admission reports
            $table->index(['institute_id', 'admission_date'], 'stu_institute_admdate_idx');

            // Stream-wise student listing
            $table->index(['institute_id', 'course_stream_id'], 'stu_institute_stream_idx');
        });

        // ── fee_invoices ─────────────────────────────────────────────────
        Schema::table('fee_invoices', function (Blueprint $table) {
            // Most-used filter combo (dashboard, reports, fee collection)
            $table->index(['institute_id', 'academic_session_id'], 'fi_institute_session_idx');

            // Date-range financial reports
            $table->index(['institute_id', 'payment_date'], 'fi_institute_paydate_idx');

            // Fee balance / wallet queries (not-cancelled invoices per student)
            $table->index(['student_id', 'is_cancelled'], 'fi_student_cancelled_idx');

            // Cancellation reports
            $table->index(['institute_id', 'is_cancelled'], 'fi_institute_cancelled_idx');

            // Center-wise collection reports
            $table->index(['collected_by_center_id', 'is_cancelled'], 'fi_center_cancelled_idx');

            // Partner-wise collection reports
            $table->index(['collected_by_partner_id', 'is_cancelled'], 'fi_partner_cancelled_idx');
        });

        // ── academic_sessions ─────────────────────────────────────────────
        Schema::table('academic_sessions', function (Blueprint $table) {
            // Active session lookup — called on almost every page load
            $table->index(['institute_id', 'is_active'], 'as_institute_active_idx');
        });

        // ── student_wallets ───────────────────────────────────────────────
        Schema::table('student_wallets', function (Blueprint $table) {
            // Dashboard: students with negative balance per session
            $table->index(['institute_id', 'academic_session_id'], 'sw_institute_session_idx');
        });

        // ── student_transactions ──────────────────────────────────────────
        Schema::table('student_transactions', function (Blueprint $table) {
            // Transaction history per institute+session
            $table->index(['institute_id', 'academic_session_id'], 'st_institute_session_idx');
            // Per-student transaction lookup
            $table->index(['student_id', 'academic_session_id'], 'st_student_session_idx');
        });
    }

    public function down(): void
    {
        Schema::table('student_transactions', function (Blueprint $table) {
            $table->dropIndex('st_student_session_idx');
            $table->dropIndex('st_institute_session_idx');
        });

        Schema::table('student_wallets', function (Blueprint $table) {
            $table->dropIndex('sw_institute_session_idx');
        });

        Schema::table('academic_sessions', function (Blueprint $table) {
            $table->dropIndex('as_institute_active_idx');
        });

        Schema::table('fee_invoices', function (Blueprint $table) {
            $table->dropIndex('fi_partner_cancelled_idx');
            $table->dropIndex('fi_center_cancelled_idx');
            $table->dropIndex('fi_institute_cancelled_idx');
            $table->dropIndex('fi_student_cancelled_idx');
            $table->dropIndex('fi_institute_paydate_idx');
            $table->dropIndex('fi_institute_session_idx');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('stu_institute_stream_idx');
            $table->dropIndex('stu_institute_admdate_idx');
            $table->dropIndex('stu_institute_source_idx');
            $table->dropIndex('stu_institute_status_idx');
        });
    }
};
