<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: institute_id nullable add karo
        Schema::table('course_types', function (Blueprint $table) {
            $table->unsignedBigInteger('institute_id')->nullable()->after('id');
            $table->unsignedTinyInteger('sort_order')->default(0)->after('name');
            $table->boolean('is_active')->default(true)->after('sort_order');
        });

        // Step 2: Existing global types ko har institute ke liye duplicate karo
        $globalTypes = DB::table('course_types')->whereNull('institute_id')->get();
        $institutes  = DB::table('institutes')->pluck('id');

        foreach ($institutes as $instituteId) {
            foreach ($globalTypes as $i => $type) {
                $newId = DB::table('course_types')->insertGetId([
                    'institute_id' => $instituteId,
                    'name'         => $type->name,
                    'sort_order'   => $i + 1,
                    'is_active'    => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                // Step 3: Is institute ke courses ka course_type_id update karo
                DB::table('courses')
                    ->where('institute_id', $instituteId)
                    ->where('course_type_id', $type->id)
                    ->update(['course_type_id' => $newId]);

                // Step 4: Is institute ke students ka course_type_id update karo
                DB::table('students')
                    ->where('institute_id', $instituteId)
                    ->where('course_type_id', $type->id)
                    ->update(['course_type_id' => $newId]);
            }
        }

        // Step 5: Old global types delete karo (institute_id = null)
        DB::table('course_types')->whereNull('institute_id')->delete();

        // Step 6: institute_id NOT NULL karo
        Schema::table('course_types', function (Blueprint $table) {
            $table->unsignedBigInteger('institute_id')->nullable(false)->change();
            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('course_types', function (Blueprint $table) {
            $table->dropForeign(['institute_id']);
            $table->dropColumn(['institute_id', 'sort_order', 'is_active']);
        });
    }
};
