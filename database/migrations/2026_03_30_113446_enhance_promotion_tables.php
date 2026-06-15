<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // promotion_logs mein reversal support
        Schema::table('promotion_logs', function (Blueprint $table) {
            $table->boolean('is_reversed')->default(false)->after('promoted_by_role');
            $table->unsignedBigInteger('reversed_by_log_id')->nullable()->after('is_reversed');
            $table->timestamp('reversed_at')->nullable()->after('reversed_by_log_id');
            $table->string('reversed_by')->nullable()->after('reversed_at');
        });

        // student_academic_identity mein extra fields
        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->string('admission_type')->default('new')->after('source')
                ->comment('new, lateral, transfer, re_admission, gap_year');
            $table->string('transfer_from')->nullable()->after('admission_type')
                ->comment('Previous college name for transfer students');
            $table->unsignedInteger('gap_years')->default(0)->after('transfer_from');
            $table->date('gap_from')->nullable()->after('gap_years');
            $table->date('gap_to')->nullable()->after('gap_from');
            $table->string('gap_reason')->nullable()->after('gap_to');
        });
    }

    public function down(): void
    {
        Schema::table('promotion_logs', function (Blueprint $table) {
            $table->dropColumn(['is_reversed','reversed_by_log_id','reversed_at','reversed_by']);
        });
        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->dropColumn(['admission_type','transfer_from','gap_years','gap_from','gap_to','gap_reason']);
        });
    }
};