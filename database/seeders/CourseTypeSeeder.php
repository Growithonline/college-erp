<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['UG', 'PG', 'Diploma', 'PhD', 'Certificate', 'ITI'];

        $institutes = DB::table('institutes')->pluck('id');

        if ($institutes->isEmpty()) {
            $this->command->warn('No institutes found — skipping CourseTypeSeeder.');
            return;
        }

        foreach ($institutes as $instituteId) {
            foreach ($types as $i => $name) {
                DB::table('course_types')->updateOrInsert(
                    ['institute_id' => $instituteId, 'name' => $name],
                    [
                        'institute_id' => $instituteId,
                        'name'         => $name,
                        'sort_order'   => $i + 1,
                        'is_active'    => true,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]
                );
            }
        }
    }
}
