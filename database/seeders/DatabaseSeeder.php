<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,  // Super admin login
            CourseTypeSeeder::class,  // UG, PG, Diploma etc
            InstituteSeeder::class,   // Blooming Buds Academy
        ]);
    }
}
