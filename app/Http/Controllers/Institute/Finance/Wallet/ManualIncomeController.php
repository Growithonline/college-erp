<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\InstituteIncomeCategory;
use App\Models\InstituteManualIncome;
use App\Services\InstituteWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManualIncomeController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();

        $sessionId = $request->input('session_id')
            ?? AcademicSession::viewSessionId($instituteId);

        $sessions = AcademicSession::where('institute_id', $instituteId)->orderByDesc('start_date')->get();

        $incomes = InstituteManualIncome::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->with('category')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('institute.finance.wallet.manual-income.index', compact('incomes', 'sessions', 'sessionId'));
    }

    public function create()
    {
        $instituteId = $this->instituteId();

        $categories = InstituteIncomeCategory::where('institute_id', $instituteId)
            ->active()
            ->orderBy('name')
            ->get();

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('start_date')
            ->get();

        $activeSessionId = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->value('id');

        return view('institute.finance.wallet.manual-income.create', compact('categories', 'sessions', 'activeSessionId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'academic_session_id' => 'required|exists:academic_sessions,id',
            'income_category_id'  => 'required|exists:institute_income_categories,id',
            'amount'              => 'required|numeric|min:0.01',
            'date'                => 'required|date',
            'receipt_no'          => 'nullable|string|max:80',
            'description'         => 'nullable|string|max:500',
        ]);

        $instituteId = $this->instituteId();

        // Verify category belongs to this institute
        $category = InstituteIncomeCategory::where('id', $data['income_category_id'])
            ->where('institute_id', $instituteId)
            ->firstOrFail();

        // Verify session belongs to this institute
        AcademicSession::where('id', $data['academic_session_id'])
            ->where('institute_id', $instituteId)
            ->firstOrFail();

        DB::transaction(function () use ($data, $instituteId, $category) {
            $income = InstituteManualIncome::create([
                'institute_id'        => $instituteId,
                'academic_session_id' => $data['academic_session_id'],
                'income_category_id'  => $data['income_category_id'],
                'amount'              => $data['amount'],
                'date'                => $data['date'],
                'receipt_no'          => $data['receipt_no'] ?? null,
                'description'         => $data['description'] ?? null,
                'created_by'          => auth()->id(),
            ]);

            $income->setRelation('category', $category);

            InstituteWalletService::creditManualIncome($income);
        });

        return redirect()->route('finance.wallet.manual-income.index')
            ->with('success', 'Manual income add ho gayi aur wallet me credit ho gaya.');
    }
}
