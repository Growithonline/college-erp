<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Institute;
use App\Models\User;
use App\Services\AccountingSetupService;

class InstituteSeeder extends Seeder
{
    public function run(): void
    {
        // Already exists to avoid duplicate
        if (Institute::where('email', 'cm5674715@gmail.com')->exists()) {
            $this->command->info('Institute already exists — skipping.');
            return;
        }

        $uid = 'INST-' . strtoupper(Str::random(6));
        $plainPassword = 'Admin@123';

        $institute = Institute::create([
            'institute_uid'    => $uid,
            'name'             => 'Blooming Buds Academy',
            'short_name'       => 'BBA',
            'mobile'           => '7887242778',
            'email'            => 'cm5674715@gmail.com',
            'address'          => 'Khalilabad',
            'city'             => 'Khalilabad',
            'state'            => 'Uttar Pradesh',
            'pincode'          => null,
            'owner_name'       => 'Chandra Mohan',
            'owner_mobile'     => '7887242778',
            'owner_email'      => 'cm5674715@gmail.com',
            'owner_whatsapp'   => '7887242778',
            'student_limit'    => 1000,
            'subscription_start' => now(),
            'subscription_end'   => now()->addYear(),
            'status'           => 'active',
        ]);

        // Institute admin user
        User::create([
            'institute_id' => $institute->id,
            'name'         => 'Chandra Mohan',
            'email'        => 'cm5674715@gmail.com',
            'mobile'       => '7887242778',
            'password'     => Hash::make($plainPassword),
            'role'         => 'institute_admin',
        ]);

        // Default staff roles
        StaffRoleSeeder::createDefaultRoles($institute->id);
        AccountingSetupService::bootstrapInstitute($institute->id);

        $this->command->info('✅ Institute created!');
        $this->command->info("   Login Email : cm5674715@gmail.com");
        $this->command->info("   Password    : {$plainPassword}");
        $this->command->info("   Institute ID: {$uid}");
    }
}
