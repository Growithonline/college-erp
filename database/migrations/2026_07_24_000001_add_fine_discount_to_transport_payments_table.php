<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_payments', function (Blueprint $table) {
            $table->decimal('fine', 12, 2)->default(0)->after('amount');
            $table->decimal('discount', 12, 2)->default(0)->after('fine');
        });
    }

    public function down(): void
    {
        Schema::table('transport_payments', function (Blueprint $table) {
            $table->dropColumn(['fine', 'discount']);
        });
    }
};
