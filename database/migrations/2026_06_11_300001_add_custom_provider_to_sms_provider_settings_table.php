<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_provider_settings', function (Blueprint $table) {
            $table->string('custom_endpoint', 500)->nullable()->after('sender_id');
            $table->string('custom_method', 10)->default('POST')->after('custom_endpoint');
            $table->text('custom_headers_json')->nullable()->after('custom_method');
            $table->text('custom_body_template')->nullable()->after('custom_headers_json');
            $table->string('custom_success_key', 100)->nullable()->after('custom_body_template');
            $table->string('custom_success_value', 100)->nullable()->after('custom_success_key');
            $table->text('custom_credentials_json')->nullable()->after('custom_success_value'); // encrypted

            // api_key nullable for custom providers
            $table->text('api_key')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sms_provider_settings', function (Blueprint $table) {
            $table->dropColumn([
                'custom_endpoint',
                'custom_method',
                'custom_headers_json',
                'custom_body_template',
                'custom_success_key',
                'custom_success_value',
                'custom_credentials_json',
            ]);
            $table->text('api_key')->nullable(false)->change();
        });
    }
};
