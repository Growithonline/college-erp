<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            // 'fixed' | 'lifetime' — null = not configured yet, blocks Group-Admin institute creation
            $table->string('institute_subscription_type')->nullable()->after('per_institute_student_limit');
            // only used when institute_subscription_type = 'fixed'
            $table->date('institute_subscription_end')->nullable()->after('institute_subscription_type');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['institute_subscription_type', 'institute_subscription_end']);
        });
    }
};
