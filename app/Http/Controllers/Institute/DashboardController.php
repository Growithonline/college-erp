<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Expense;
use App\Models\FeeInvoice;
use App\Models\Library\LibraryFinePayment;
use App\Models\Notice;
use App\Models\SalaryRecord;
use App\Models\Student;
use App\Models\StudentWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index()
    {
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::viewSession($instituteId);
        $sessionId     = $activeSession?->id;

        // ── STAT CARDS ───────────────────────────────────────────────────

        // Total Students (active session)
        $totalStudents = Student::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->count();

        // Total Admissions this session
        $totalAdmissions = $totalStudents;
        $pendingAdmissions = Student::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->where('status', 'pending')
            ->count();

        // Fee Collected Today
        $feeToday = FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->whereDate('payment_date', today())
            ->sum('paid_amount');

        // Fee Collected This Month
        $feeThisMonth = FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->whereYear('payment_date',  now()->year)
            ->whereMonth('payment_date', now()->month)
            ->sum('paid_amount');

        // Total Fee Collected (active session)
        $feeTotalSession = FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->sum('paid_amount');

        // Library Fine Collections — add to totals
        if (Schema::hasTable('library_fine_payments')) {
            $feeToday += (float) LibraryFinePayment::where('institute_id', $instituteId)
                ->whereDate('payment_date', today())
                ->sum('amount');

            $feeThisMonth += (float) LibraryFinePayment::where('institute_id', $instituteId)
                ->whereYear('payment_date',  now()->year)
                ->whereMonth('payment_date', now()->month)
                ->sum('amount');

            $feeTotalSession += (float) LibraryFinePayment::where('institute_id', $instituteId)
                ->when($activeSession?->start_date, fn($q, $d) => $q->where('payment_date', '>=', $d))
                ->sum('amount');
        }

        $financeReady = Schema::hasTable('expenses');
        $expenseToday = 0;
        $expenseThisMonth = 0;
        $expenseTotalSession = 0;
        $pendingExpensePostings = 0;
        $recentExpenses = collect();
        $expenseByAccount = collect();
        $monthlyFinanceData = collect();

        if ($financeReady) {
            $expenseToday = (float) Expense::where('institute_id', $instituteId)
                ->where('is_reversed', false)
                ->whereDate('expense_date', today())
                ->sum('amount');

            $expenseThisMonth = (float) Expense::where('institute_id', $instituteId)
                ->where('is_reversed', false)
                ->whereYear('expense_date', now()->year)
                ->whereMonth('expense_date', now()->month)
                ->sum('amount');

            $expenseTotalSession = (float) Expense::where('institute_id', $instituteId)
                ->where('is_reversed', false)
                ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                ->sum('amount');

            // Add salary payments to expense totals
            if (Schema::hasTable('salary_records')) {
                $expenseToday += (float) SalaryRecord::where('institute_id', $instituteId)
                    ->where('status', SalaryRecord::STATUS_PAID)
                    ->whereDate('payment_date', today())
                    ->sum('paid_amount');

                $expenseThisMonth += (float) SalaryRecord::where('institute_id', $instituteId)
                    ->where('status', SalaryRecord::STATUS_PAID)
                    ->whereYear('payment_date', now()->year)
                    ->whereMonth('payment_date', now()->month)
                    ->sum('paid_amount');

                $expenseTotalSession += (float) SalaryRecord::where('institute_id', $instituteId)
                    ->where('status', SalaryRecord::STATUS_PAID)
                    ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                    ->sum('paid_amount');
            }

            $pendingExpensePostings = Expense::where('institute_id', $instituteId)
                ->where('is_reversed', false)
                ->whereNull('journal_entry_id')
                ->count();

            $recentExpenses = Expense::with(['expenseAccount', 'bankAccount'])
                ->where('institute_id', $instituteId)
                ->where('is_reversed', false)
                ->latest('expense_date')
                ->latest('id')
                ->limit(8)
                ->get();

            $expenseByAccount = Expense::with('expenseAccount')
                ->where('institute_id', $instituteId)
                ->where('is_reversed', false)
                ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                ->selectRaw('expense_account_id, COUNT(*) as count, SUM(amount) as total')
                ->groupBy('expense_account_id')
                ->orderByDesc('total')
                ->limit(6)
                ->get();
        }

        // Fee Due / Pending (negative wallet balances)
        $feeDue = StudentWallet::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->where('main_b', '<', 0)
            ->sum(DB::raw('ABS(main_b)'));

        // Students with dues
        $studentsWithDue = StudentWallet::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->where('main_b', '<', 0)
            ->count();

        // ── MONTHLY FEE CHART (last 6 months) ───────────────────────────
        $monthlyFee = FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->where('payment_date', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw('YEAR(payment_date) as yr, MONTH(payment_date) as mn, SUM(paid_amount) as total')
            ->groupBy('yr', 'mn')
            ->orderBy('yr')->orderBy('mn')
            ->get()
            ->map(fn($r) => [
                'label'  => Carbon::create($r->yr, $r->mn, 1)->format('M Y'),
                'amount' => (float) $r->total,
            ]);

        // Fill missing months with 0
        $monthlyData = collect();
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $label = $month->format('M Y');
            $found = $monthlyFee->firstWhere('label', $label);
            $monthlyData->push([
                'label'  => $month->format('M'),
                'amount' => $found ? $found['amount'] : 0,
            ]);
        }

        if ($financeReady) {
            $monthlyExpense = Expense::where('institute_id', $instituteId)
                ->where('is_reversed', false)
                ->where('expense_date', '>=', now()->subMonths(5)->startOfMonth())
                ->selectRaw('YEAR(expense_date) as yr, MONTH(expense_date) as mn, SUM(amount) as total')
                ->groupBy('yr', 'mn')
                ->orderBy('yr')
                ->orderBy('mn')
                ->get()
                ->map(fn($r) => [
                    'label'  => Carbon::create($r->yr, $r->mn, 1)->format('M Y'),
                    'amount' => (float) $r->total,
                ]);

            $monthlySalary = collect();
            if (Schema::hasTable('salary_records')) {
                $monthlySalary = SalaryRecord::where('institute_id', $instituteId)
                    ->where('status', SalaryRecord::STATUS_PAID)
                    ->where('payment_date', '>=', now()->subMonths(5)->startOfMonth())
                    ->selectRaw('YEAR(payment_date) as yr, MONTH(payment_date) as mn, SUM(paid_amount) as total')
                    ->groupBy('yr', 'mn')
                    ->get()
                    ->map(fn($r) => [
                        'label'  => Carbon::create($r->yr, $r->mn, 1)->format('M Y'),
                        'amount' => (float) $r->total,
                    ]);
            }

            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $label = $month->format('M Y');
                $income  = $monthlyFee->firstWhere('label', $label);
                $expense = $monthlyExpense->firstWhere('label', $label);
                $salary  = $monthlySalary->firstWhere('label', $label);

                $incomeAmount  = $income  ? $income['amount']  : 0;
                $expenseAmount = ($expense ? $expense['amount'] : 0) + ($salary ? $salary['amount'] : 0);

                $monthlyFinanceData->push([
                    'label'   => $month->format('M'),
                    'income'  => $incomeAmount,
                    'expense' => $expenseAmount,
                    'net'     => $incomeAmount - $expenseAmount,
                ]);
            }
        }

        // ── COURSE-WISE STUDENTS (pie chart) ────────────────────────────
        $courseWise = Student::where('students.institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('students.academic_session_id', $sessionId))
            ->join('course_streams', 'students.course_stream_id', '=', 'course_streams.id')
            ->join('courses', 'course_streams.course_id', '=', 'courses.id')
            ->selectRaw('courses.name as course_name, COUNT(students.id) as count')
            ->groupBy('courses.id', 'courses.name')
            ->orderByDesc('count')
            ->limit(6)
            ->get();

        // ── PAYMENT MODE WISE ────────────────────────────────────────────
        $paymentModes = FeeInvoice::where('institute_id', $instituteId)
            ->where('is_cancelled', false)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->selectRaw('payment_mode, COUNT(*) as count, SUM(paid_amount) as total')
            ->groupBy('payment_mode')
            ->get();

        // ── RECENT ADMISSIONS ────────────────────────────────────────────
        $recentAdmissions = Student::with(['stream.course', 'session'])
            ->where('institute_id', $instituteId)
            ->latest()
            ->limit(8)
            ->get();

        // ── ALL SESSIONS for switcher ────────────────────────────────────
        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderBy('name')->get();

        $dashboardNotices = Notice::forRole($instituteId, 'staff')->limit(5)->get();

        return view('institute.dashboard', compact(
            'activeSession', 'sessions',
            'totalStudents', 'totalAdmissions', 'pendingAdmissions',
            'feeToday', 'feeThisMonth', 'feeTotalSession',
            'financeReady', 'expenseToday', 'expenseThisMonth', 'expenseTotalSession',
            'pendingExpensePostings', 'recentExpenses', 'expenseByAccount', 'monthlyFinanceData',
            'feeDue', 'studentsWithDue',
            'monthlyData', 'courseWise', 'paymentModes',
            'recentAdmissions', 'dashboardNotices'
        ));
    }
}
