<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_discount_percent')->default(100)->after('bank_ifsc');
        });
    }

    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn('max_discount_percent');
        });
    }
};
