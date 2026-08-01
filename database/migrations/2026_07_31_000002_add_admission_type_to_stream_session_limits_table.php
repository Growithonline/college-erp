<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Optional, opt-in Lateral Entry seat quota. A stream+session can now have an EXTRA
// StreamSessionLimit row scoped to admission_type='lateral', on top of its existing
// general row (admission_type='all'). Institutes who never set one keep today's exact
// behaviour — one pooled limit shared by every admission, lateral included.
// Uses the non-null 'all' sentinel (matching CourseFeeRule/SubjectFeeRule's convention)
// rather than NULL, since MySQL unique indexes treat NULL as distinct-from-itself and
// would silently stop enforcing "one general row per stream+session" otherwise.
//
// Every step is guarded — see the 2026_07_31_000001 migration's comment for why
// (MySQL DDL isn't transactional, so a retry after a partial failure must be safe).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('stream_session_limits', 'admission_type')) {
            Schema::table('stream_session_limits', function (Blueprint $table) {
                $table->string('admission_type', 20)->default('all')->after('academic_session_id');
            });
        }

        $hasNewIndex = collect(DB::select("SHOW INDEX FROM stream_session_limits WHERE Key_name = 'ssl_stream_session_type_unique'"))->isNotEmpty();

        if (!$hasNewIndex) {
            $hasOldIndex = collect(DB::select("SHOW INDEX FROM stream_session_limits WHERE Key_name = 'ssl_stream_session_unique'"))->isNotEmpty();

            if ($hasOldIndex) {
                Schema::table('stream_session_limits', function (Blueprint $table) {
                    $table->dropUnique('ssl_stream_session_unique');
                });
            }

            Schema::table('stream_session_limits', function (Blueprint $table) {
                $table->unique(
                    ['course_stream_id', 'academic_session_id', 'admission_type'],
                    'ssl_stream_session_type_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::table('stream_session_limits', function (Blueprint $table) {
            $table->dropUnique('ssl_stream_session_type_unique');
        });

        Schema::table('stream_session_limits', function (Blueprint $table) {
            $table->unique(['course_stream_id', 'academic_session_id'], 'ssl_stream_session_unique');
        });

        Schema::table('stream_session_limits', function (Blueprint $table) {
            $table->dropColumn('admission_type');
        });
    }
};
