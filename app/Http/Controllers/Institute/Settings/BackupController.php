<?php

namespace App\Http\Controllers\Institute\Settings;

use App\Exports\Institute\FinancialReportExport;
use App\Exports\Institute\StudentReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class BackupController extends Controller
{
    public function index()
    {
        $user      = Auth::user();
        $institute = DB::table('institutes')->where('id', $user->institute_id)->first();

        abort_if(! $institute, 403);

        return view('institute.settings.backup', compact('institute'));
    }

    public function downloadStudentExcel()
    {
        $user      = Auth::user();
        $institute = DB::table('institutes')->where('id', $user->institute_id)->first();

        abort_if(! $institute, 403);

        $filename = 'Student_Report_' . Str::slug($institute->institute_uid) . '_' . now()->format('Ymd') . '.xlsx';

        return Excel::download(new StudentReportExport((int) $institute->id), $filename);
    }

    public function downloadFinancialExcel()
    {
        $user      = Auth::user();
        $institute = DB::table('institutes')->where('id', $user->institute_id)->first();

        abort_if(! $institute, 403);

        $filename = 'Financial_Report_' . Str::slug($institute->institute_uid) . '_' . now()->format('Ymd') . '.xlsx';

        return Excel::download(new FinancialReportExport((int) $institute->id), $filename);
    }
}
