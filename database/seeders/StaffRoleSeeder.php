<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StaffRole;
use App\Models\Institute;

class StaffRoleSeeder extends Seeder
{
    // Ye seeder tab chalao jab institute create ho
    // InstituteController me call karo

    public static function createDefaultRoles(int $instituteId): void
    {
        $roles = [
            [
                'name'        => 'Manager',
                'is_system'   => true,
                'permissions' => [
                    'dashboard'         => true,
                    'admission_add'     => true,
                    'admission_approve' => true,
                    'admission_view'    => true,
                    'fee_collect'       => true,
                    'fee_view'          => true,
                    'fee_reports'       => true,
                    'student_view'      => true,
                    'student_edit'      => true,
                    'notice_post'       => true,
                    'staff_manage'      => false,
                    'reports'           => true,
                    'report_export'     => true,
                    'expense_approve'   => true,
                ],
            ],
            [
                'name'        => 'Accountant',
                'is_system'   => true,
                'permissions' => [
                    'dashboard'         => true,
                    'admission_add'     => false,
                    'admission_approve' => false,
                    'admission_view'    => true,
                    'fee_collect'       => true,
                    'fee_view'          => true,
                    'fee_reports'       => true,
                    'student_view'      => true,
                    'student_edit'      => false,
                    'notice_post'       => false,
                    'staff_manage'      => false,
                    'reports'           => true,
                    'report_export'     => true,
                    'expense_approve'   => true,
                ],
            ],
            [
                'name'        => 'Receptionist',
                'is_system'   => true,
                'permissions' => [
                    'dashboard'         => true,
                    'admission_add'     => true,
                    'admission_approve' => false,
                    'admission_view'    => true,
                    'fee_collect'       => false,
                    'fee_view'          => false,
                    'fee_reports'       => false,
                    'student_view'      => true,
                    'student_edit'      => false,
                    'notice_post'       => false,
                    'staff_manage'      => false,
                    'reports'           => false,
                    'report_export'     => false,
                    'expense_approve'   => false,
                ],
            ],
            [
                'name'        => 'Data Entry',
                'is_system'   => true,
                'permissions' => [
                    'dashboard'         => true,
                    'admission_add'     => true,
                    'admission_approve' => false,
                    'admission_view'    => true,
                    'fee_collect'       => false,
                    'fee_view'          => false,
                    'fee_reports'       => false,
                    'student_view'      => true,
                    'student_edit'      => false,
                    'notice_post'       => false,
                    'staff_manage'      => false,
                    'reports'           => false,
                    'report_export'     => false,
                    'expense_approve'   => false,
                ],
            ],
        ];

        foreach ($roles as $role) {
            StaffRole::firstOrCreate(
                ['institute_id' => $instituteId, 'name' => $role['name']],
                [
                    'is_system'   => $role['is_system'],
                    'permissions' => $role['permissions'],
                    'status'      => true,
                ]
            );
        }
    }
}
