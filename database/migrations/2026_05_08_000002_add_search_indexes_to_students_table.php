<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->index(['institute_id', 'name'],        'idx_stu_inst_name');
            $table->index(['institute_id', 'father_name'], 'idx_stu_inst_father');
            $table->index(['institute_id', 'mother_name'], 'idx_stu_inst_mother');
            $table->index(['institute_id', 'mobile'],      'idx_stu_inst_mobile');
            $table->index(['institute_id', 'email'],       'idx_stu_inst_email');
            $table->index('enrollment_no',                 'idx_stu_enrollment_no');
            $table->index('roll_no',                       'idx_stu_roll_no');
        });

        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->index('roll_no',  'idx_sai_roll_no');
            $table->index('form_no',  'idx_sai_form_no');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex('idx_stu_inst_name');
            $table->dropIndex('idx_stu_inst_father');
            $table->dropIndex('idx_stu_inst_mother');
            $table->dropIndex('idx_stu_inst_mobile');
            $table->dropIndex('idx_stu_inst_email');
            $table->dropIndex('idx_stu_enrollment_no');
            $table->dropIndex('idx_stu_roll_no');
        });

        Schema::table('student_academic_identity', function (Blueprint $table) {
            $table->dropIndex('idx_sai_roll_no');
            $table->dropIndex('idx_sai_form_no');
        });
    }
};
