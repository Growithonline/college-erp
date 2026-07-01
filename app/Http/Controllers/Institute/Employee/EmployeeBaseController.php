<?php

namespace App\Http\Controllers\Institute\Employee;

use App\Http\Controllers\Controller;

abstract class EmployeeBaseController extends Controller
{
    protected function instituteId(): int
    {
        foreach (['staff', 'web'] as $guard) {
            $user = auth()->guard($guard)->user();
            if ($user && $user->institute_id) {
                return (int) $user->institute_id;
            }
        }
        abort(403, 'Institute context missing.');
    }
}
