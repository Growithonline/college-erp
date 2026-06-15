<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\ContraEntry;
use App\Models\InstituteBankAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContraEntryController extends Controller
{
    private function instituteId(): int
    {
        return (int) auth()->user()->institute_id;
    }

    public function index(Request $request)
    {
        $instituteId = $this->instituteId();

        $sessions  = AcademicSession::where('institute_id', $instituteId)->orderByDesc('start_date')->get();
        $sessionId = $request->input('session_id')
            ?? AcademicSession::where('institute_id', $instituteId)->where('is_active', true)->value('id');

        $from = $request->input('from');
        $to   = $request->input('to');

        $entries = ContraEntry::with('bankAccount', 'session')
            ->where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to,   fn($q) => $q->whereDate('entry_date', '<=', $to))
            ->orderByDesc('entry_date')->orderByDesc('id')
            ->paginate(30)->withQueryString();

        $totalAmount = ContraEntry::where('institute_id', $instituteId)
            ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
            ->when($from, fn($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to,   fn($q) => $q->whereDate('entry_date', '<=', $to))
            ->sum('amount');

        $bankAccounts = InstituteBankAccount::where('institute_id', $instituteId)
            ->where('is_active', true)->orderBy('sort_order')->get();

        return view('institute.finance.wallet.contra.index',
            compact('sessions', 'sessionId', 'from', 'to', 'entries', 'totalAmount', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $instituteId = $this->instituteId();

        $data = $request->validate([
            'entry_date'        => 'required|date',
            'amount'            => 'required|numeric|min:1',
            'to_bank_account_id'=> ['required', Rule::exists('institute_bank_accounts', 'id')->where('institute_id', $instituteId)],
            'slip_no'           => 'nullable|string|max:80',
            'description'       => 'nullable|string|max:500',
            'session_id'        => ['required', Rule::exists('academic_sessions', 'id')->where('institute_id', $instituteId)],
        ]);

        ContraEntry::create([
            'institute_id'        => $instituteId,
            'academic_session_id' => $data['session_id'],
            'entry_date'          => $data['entry_date'],
            'amount'              => $data['amount'],
            'to_bank_account_id'  => $data['to_bank_account_id'],
            'slip_no'             => $data['slip_no'] ?? null,
            'description'         => $data['description'] ?? null,
            'created_by'          => auth()->id(),
        ]);

        return back()->with('success', 'Contra entry saved — cash deposited to bank.');
    }

    public function destroy(ContraEntry $contraEntry)
    {
        abort_if($contraEntry->institute_id !== $this->instituteId(), 403);
        $contraEntry->delete();
        return back()->with('success', 'Contra entry deleted.');
    }
}
