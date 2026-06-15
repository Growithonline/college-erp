<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institute_id');
            $table->string('name', 100);        // Title Case: "Regular", "Ex-Student"
            $table->string('slug', 100);        // auto slug: "regular", "ex_student"
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['institute_id', 'slug']);
            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
        });

        // Seed default student types for all existing institutes
        $institutes = DB::table('institutes')->pluck('id');
        $defaults = [
            ['name' => 'Regular',       'slug' => 'regular',     'sort_order' => 1],
            ['name' => 'Private',       'slug' => 'private',     'sort_order' => 2],
            ['name' => 'Distance',      'slug' => 'distance',    'sort_order' => 3],
            ['name' => 'Ex-Student',    'slug' => 'ex_student',  'sort_order' => 4],
            ['name' => 'Lateral Entry', 'slug' => 'lateral',     'sort_order' => 5],
        ];

        foreach ($institutes as $instituteId) {
            foreach ($defaults as $d) {
                DB::table('student_types')->insertOrIgnore([
                    'institute_id' => $instituteId,
                    'name'         => $d['name'],
                    'slug'         => $d['slug'],
                    'sort_order'   => $d['sort_order'],
                    'is_active'    => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_types');
    }
};
