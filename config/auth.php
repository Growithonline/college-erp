<?php

return [

    'defaults' => [
        'guard'     => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
        'super_admin' => [
            'driver'   => 'session',
            'provider' => 'super_admins',
        ],
        // ── Phase 7: Role-based guards ─────────────────────────
        'center' => [
            'driver'   => 'session',
            'provider' => 'centers',
        ],
        'staff' => [
            'driver'   => 'session',
            'provider' => 'staff_members',
        ],
        'partner' => [
            'driver'   => 'session',
            'provider' => 'channel_partners',
        ],
        'library_staff' => [
            'driver'   => 'session',
            'provider' => 'library_staff',
        ],
        'student' => [
            'driver'   => 'session',
            'provider' => 'students',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => env('AUTH_MODEL', App\Models\User::class),
        ],
        'super_admins' => [
            'driver' => 'eloquent',
            'model'  => App\Models\SuperAdmin::class,
        ],
        // ── Phase 7 providers ──────────────────────────────────
        'centers' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Center::class,
        ],
        'staff_members' => [
            'driver' => 'eloquent',
            'model'  => App\Models\StaffMember::class,
        ],
        'channel_partners' => [
            'driver' => 'eloquent',
            'model'  => App\Models\ChannelPartner::class,
        ],
        'library_staff' => [
            'driver' => 'eloquent',
            'model'  => App\Models\LibraryStaff::class,
        ],
        'students' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Student::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];