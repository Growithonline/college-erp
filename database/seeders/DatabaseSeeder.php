<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class, // Super admin login
            InstituteSeeder::class,  // Blooming Buds Academy (staff roles + accounting bhi setup karta hai)
        ]);
    }
}
