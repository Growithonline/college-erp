<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'UG',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'PG',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Diploma',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'PhD',      'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Certificate', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'ITI',      'created_at' => now(), 'updated_at' => now()],
        ];

        // Duplicate avoid karo
        foreach ($types as $type) {
            DB::table('course_types')->updateOrInsert(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
