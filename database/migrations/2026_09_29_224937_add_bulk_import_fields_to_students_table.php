<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Marks a student as having originated from Bulk Excel Import, so the
            // dedicated "Bulk Import Pending Review" queue can be scoped to exactly
            // these rows and excluded from the regular Admissions Approval list.
            $table->boolean('is_bulk_import')->default(false)->after('is_quick_admission');

            // The status the row should be switched to once its fee-history review is
            // approved (e.g. 'active', 'passed_out', 'detained', 'transferred',
            // 'cancelled' — whatever "Student Status" was set to in the Excel file).
            // The actual `status` column is forced to 'pending' at import time so the
            // student is held back (login blocked, hidden from active lists, fee
            // collection blocked) exactly like an online admission awaiting approval,
            // until a staff member confirms how much of each past semester was paid.
            $table->string('bulk_import_target_status', 20)->nullable()->after('is_bulk_import');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->index(['institute_id', 'is_bulk_import', 'status'], 'students_bulk_import_pending_idx');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('students_bulk_import_pending_idx');
            $table->dropColumn(['is_bulk_import', 'bulk_import_target_status']);
        });
    }
};
