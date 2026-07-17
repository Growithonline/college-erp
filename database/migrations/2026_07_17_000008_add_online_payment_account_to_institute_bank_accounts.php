<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institute_bank_accounts', function (Blueprint $table) {
            $table->boolean('is_online_payment_account')->default(false)->after('upi_id');
        });
    }

    public function down(): void
    {
        Schema::table('institute_bank_accounts', function (Blueprint $table) {
            $table->dropColumn('is_online_payment_account');
        });
    }
};
