<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop unique index only if it exists (may not exist on all environments)
        $indexExists = DB::select(
            "SELECT COUNT(*) as cnt FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'transport_route_assignments'
               AND index_name = 'unique_route_session'"
        );
        if (($indexExists[0]->cnt ?? 0) > 0) {
            Schema::table('transport_route_assignments', function (Blueprint $table) {
                $table->dropUnique('unique_route_session');
            });
        }

        // Drop columns only if they exist
        Schema::table('transport_route_assignments', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('transport_route_assignments', 'academic_session_id')) {
                $columns[] = 'academic_session_id';
            }
            if (Schema::hasColumn('transport_route_assignments', 'status')) {
                $columns[] = 'status';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('transport_route_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('transport_route_assignments', 'start_date')) {
                $table->date('start_date')->default(DB::raw('CURDATE()'))->after('transport_driver_id');
            }
            if (!Schema::hasColumn('transport_route_assignments', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
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
