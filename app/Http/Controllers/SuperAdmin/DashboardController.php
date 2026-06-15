<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\Student;

class DashboardController extends Controller
{
    public function index()
    {
        $institutes = Institute::withCount('students')->orderByDesc('id')->get();

        $total       = $institutes->count();
        $active      = $institutes->where('status', 'active')->count();
        $expired     = $institutes->filter(fn($i) => $i->subscription_end && now()->gt($i->subscription_end))->count();
        $expiringSoon= $institutes->filter(fn($i) =>
            $i->subscription_end &&
            now()->lte($i->subscription_end) &&
            now()->addDays(30)->gte($i->subscription_end)
        )->count();

        return view('super_admin.dashboard', compact(
            'institutes', 'total', 'active', 'expired', 'expiringSoon'
        ));
    }
}
