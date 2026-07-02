<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // library_transactions — overdue queries: WHERE institute_id AND current_status AND due_on < today
        Schema::table('library_transactions', function (Blueprint $table) {
            $table->index(['institute_id', 'current_status', 'due_on'],  'lib_txn_institute_status_due_idx');
            $table->index(['institute_id', 'issued_on'],                  'lib_txn_institute_issued_idx');
            $table->index(['institute_id', 'returned_on'],                'lib_txn_institute_returned_idx');
        });

        // library_members — status filter queries
        Schema::table('library_members', function (Blueprint $table) {
            $table->index(['institute_id', 'status'], 'lib_members_institute_status_idx');
        });

        // library_fine_payments — date-range reporting
        Schema::table('library_fine_payments', function (Blueprint $table) {
            $table->index(['institute_id', 'payment_date'], 'lib_fine_pay_institute_date_idx');
        });

        // library_reservations — pending reservation lookups
        Schema::table('library_reservations', function (Blueprint $table) {
            $table->index(['institute_id', 'status'], 'lib_reservations_institute_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('library_reservations', function (Blueprint $table) {
            $table->dropIndex('lib_reservations_institute_status_idx');
        });

        Schema::table('library_fine_payments', function (Blueprint $table) {
            $table->dropIndex('lib_fine_pay_institute_date_idx');
        });

        Schema::table('library_members', function (Blueprint $table) {
            $table->dropIndex('lib_members_institute_status_idx');
        });

        Schema::table('library_transactions', function (Blueprint $table) {
            $table->dropIndex('lib_txn_institute_returned_idx');
            $table->dropIndex('lib_txn_institute_issued_idx');
            $table->dropIndex('lib_txn_institute_status_due_idx');
        });
    }
};
