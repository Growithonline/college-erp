<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('transport_route_assignments', 'transport_helper_id')) {
            return;
        }

        Schema::table('transport_route_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('transport_helper_id')->nullable()->after('transport_driver_id');
            $table->foreign('transport_helper_id')->references('id')->on('transport_helpers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('transport_route_assignments', function (Blueprint $table) {
            $table->dropForeign(['transport_helper_id']);
            $table->dropColumn('transport_helper_id');
        });
    }
};
