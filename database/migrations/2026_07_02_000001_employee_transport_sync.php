<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. transport_role on designations
        if (!Schema::hasColumn('employee_designations', 'transport_role')) {
            Schema::table('employee_designations', function (Blueprint $table) {
                $table->string('transport_role', 20)->nullable()->after('status');
            });
        }

        // 2. license fields on employees
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'license_no')) {
                $table->string('license_no', 80)->nullable()->after('notes');
            }
            if (!Schema::hasColumn('employees', 'license_expiry')) {
                $table->date('license_expiry')->nullable()->after('license_no');
            }
        });

        // 3. employee_id FK on transport_drivers
        if (!Schema::hasColumn('transport_drivers', 'employee_id')) {
            Schema::table('transport_drivers', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('id');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            });
        }

        // 4. employee_id FK on transport_helpers
        if (!Schema::hasColumn('transport_helpers', 'employee_id')) {
            Schema::table('transport_helpers', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('id');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('transport_helpers', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });

        Schema::table('transport_drivers', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropColumn('employee_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['license_no', 'license_expiry']);
        });

        Schema::table('employee_designations', function (Blueprint $table) {
            $table->dropColumn('transport_role');
        });
    }
};
