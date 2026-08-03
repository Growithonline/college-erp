<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiry_follow_ups', function (Blueprint $table) {
            $table->enum('status', ['open', 'closed'])->default('closed')->after('next_follow_up_at');
        });
    }

    public function down(): void
    {
        Schema::table('enquiry_follow_ups', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
