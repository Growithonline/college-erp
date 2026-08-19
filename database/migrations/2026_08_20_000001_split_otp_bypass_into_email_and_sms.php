<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['staff_members', 'centers', 'channel_partners'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('email_otp_bypass')->default(false)->after('otp_bypass');
                $t->boolean('sms_otp_bypass')->default(false)->after('email_otp_bypass');
            });

            DB::table($table)->where('otp_bypass', true)->update([
                'email_otp_bypass' => true,
                'sms_otp_bypass'   => true,
            ]);

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('otp_bypass');
            });
        }

        Schema::table('students', function (Blueprint $t) {
            $t->boolean('email_otp_bypass')->default(false)->after('login_blocked');
            $t->boolean('sms_otp_bypass')->default(false)->after('email_otp_bypass');
        });
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->boolean('otp_bypass')->default(false);
            });

            DB::table($table)
                ->where('email_otp_bypass', true)
                ->orWhere('sms_otp_bypass', true)
                ->update(['otp_bypass' => true]);

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['email_otp_bypass', 'sms_otp_bypass']);
            });
        }

        Schema::table('students', function (Blueprint $t) {
            $t->dropColumn(['email_otp_bypass', 'sms_otp_bypass']);
        });
    }
};
