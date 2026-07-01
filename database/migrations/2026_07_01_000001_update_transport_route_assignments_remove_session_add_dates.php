<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transport_route_assignments', function (Blueprint $table) {
            // Drop unique constraint on route+session
            $table->dropUnique('unique_route_session');
        });

        Schema::table('transport_route_assignments', function (Blueprint $table) {
            $table->dropColumn(['academic_session_id', 'status']);
        });

        Schema::table('transport_route_assignments', function (Blueprint $table) {
            $table->date('start_date')->default(DB::raw('CURDATE()'))->after('transport_driver_id');
            $table->date('end_date')->nullable()->after('start_date');
        });

        // Existing records: start_date = DATE(created_at), end_date = NULL (all treated as current)
        DB::statement('UPDATE transport_route_assignments SET start_date = DATE(created_at) WHERE start_date = CURDATE()');
    }

    public function down(): void
    {
        Schema::table('transport_route_assignments', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });

        Schema::table('transport_route_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('academic_session_id')->nullable()->after('transport_driver_id');
            $table->boolean('status')->default(true)->after('academic_session_id');
            $table->unique(['institute_id', 'transport_route_id', 'academic_session_id'], 'unique_route_session');
        });
    }
};
