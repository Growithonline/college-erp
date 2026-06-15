<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('institute_bank_accounts', function (Blueprint $table) {
            // e.g. "upi,online,cheque,neft,rtgs" — cash always separate
            $table->string('allowed_payment_modes', 200)
                  ->default('upi,online,cheque,dd,neft,rtgs')
                  ->after('display_label');
        });
    }
    public function down(): void {
        Schema::table('institute_bank_accounts', function (Blueprint $table) {
            $table->dropColumn('allowed_payment_modes');
        });
    }
};