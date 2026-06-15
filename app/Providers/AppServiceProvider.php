<?php

namespace App\Providers;

use App\Models\StaffMember;
use App\Models\Student;
use App\Services\LibraryManagementService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Student::saved(function (Student $student) {
            LibraryManagementService::syncStudentMember($student);
        });

        StaffMember::saved(function (StaffMember $staffMember) {
            $staffMember->loadMissing('role');
            LibraryManagementService::syncStaffMember($staffMember);
        });
    }
}
