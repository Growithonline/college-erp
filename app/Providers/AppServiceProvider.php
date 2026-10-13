<?php

namespace App\Providers;

use App\Models\Employee;
use App\Models\StaffMember;
use App\Models\Student;
use App\Models\TransportDriver;
use App\Models\TransportHelper;
use App\Services\LibraryManagementService;
use App\Support\LoginScope;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
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

        // Institute-scoped login/OTP pages: hand the scoped institute (if any) to the existing views.
        // Global pages get nulls and render exactly as before.
        foreach (LoginScope::portals() as $portalKey => $cfg) {
            View::composer([$cfg['view'], $cfg['otp']], function ($view) use ($portalKey) {
                $institute = LoginScope::institute($portalKey);

                $view->with([
                    'scopedInstitute' => $institute,
                    'scopedLogoUrl'   => $institute ? LoginScope::logoUrl($institute) : null,
                    'scopedLoginUrl'  => $institute ? LoginScope::loginUrl($portalKey, $institute) : null,
                ]);
            });
        }

        Student::saved(function (Student $student) {
            LibraryManagementService::syncStudentMember($student);
        });

        StaffMember::saved(function (StaffMember $staffMember) {
            $staffMember->loadMissing('role');
            LibraryManagementService::syncStaffMember($staffMember);
        });

        Employee::saved(function (Employee $employee) {
            $employee->loadMissing('designation');
            $role = $employee->designation?->transport_role;

            if ($role === 'driver') {
                TransportDriver::updateOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'institute_id'   => $employee->institute_id,
                        'name'           => $employee->name,
                        'mobile'         => $employee->phone,
                        'license_no'     => $employee->license_no,
                        'license_expiry' => $employee->license_expiry,
                        'status'         => $employee->status === 'active',
                    ]
                );
                TransportHelper::where('employee_id', $employee->id)->update(['employee_id' => null]);
            } elseif ($role === 'helper') {
                TransportHelper::updateOrCreate(
                    ['employee_id' => $employee->id],
                    [
                        'institute_id' => $employee->institute_id,
                        'name'         => $employee->name,
                        'mobile'       => $employee->phone,
                        'status'       => $employee->status === 'active',
                    ]
                );
                TransportDriver::where('employee_id', $employee->id)->update(['employee_id' => null]);
            } else {
                TransportDriver::where('employee_id', $employee->id)->update(['employee_id' => null]);
                TransportHelper::where('employee_id', $employee->id)->update(['employee_id' => null]);
            }
        });
    }
}
