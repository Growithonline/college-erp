<?php

namespace App\Http\Controllers\GroupAdmin;

use App\Http\Controllers\Controller;
use App\Models\FeeInvoice;
use App\Models\Institute;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $groupAdmin = Auth::guard('group_admin')->user();

        $institutes = Institute::where('group_id', $groupAdmin->group_id)
            ->withCount('students')
            ->orderBy('name')
            ->get();

        $instituteIds = $institutes->pluck('id');

        $todayCollected = FeeInvoice::whereIn('institute_id', $instituteIds)
            ->where('is_cancelled', false)
            ->whereDate('payment_date', today())
            ->sum('paid_amount');

        $monthCollected = FeeInvoice::whereIn('institute_id', $instituteIds)
            ->where('is_cancelled', false)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->sum('paid_amount');

        $todayAdmissions = Student::whereIn('institute_id', $instituteIds)
            ->whereDate('admission_date', today())
            ->count();

        $perInstitute = $institutes->map(function (Institute $institute) {
            return [
                'institute'       => $institute,
                'today_collected' => FeeInvoice::where('institute_id', $institute->id)
                    ->where('is_cancelled', false)
                    ->whereDate('payment_date', today())
                    ->sum('paid_amount'),
                'total_students'  => $institute->students_count,
            ];
        });

        return view('group_admin.dashboard', compact(
            'groupAdmin', 'institutes', 'todayCollected', 'monthCollected', 'todayAdmissions', 'perInstitute'
        ));
    }
}
