<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\InstituteBankAccount;
use App\Models\PaymentModePermission;
use App\Models\Center;
use App\Models\StaffMember;
use App\Models\ChannelPartner;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    // ── List all bank accounts ────────────────────────────────────────
    public function index()
    {
        $accounts = InstituteBankAccount::where('institute_id', $this->instituteId())
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        return view('institute.master.bank-accounts.index', compact('accounts'));
    }

    // ── Create form ───────────────────────────────────────────────────
    public function create()
    {
        return view('institute.master.bank-accounts.create');
    }

    // ── Store ─────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'bank_name'     => 'required|string|max:100',
            'account_name'  => 'required|string|max:150',
            'account_no'    => 'required|string|max:50',
            'ifsc_code'     => 'nullable|string|max:20',
            'branch'        => 'nullable|string|max:100',
            'upi_id'        => 'nullable|string|max:100',
            'display_label' => 'nullable|string|max:100',
        ]);

        // Checkboxes → comma separated string, cash always included
        $modes = $request->input('allowed_payment_modes', []);
        if (!in_array('cash', $modes)) array_unshift($modes, 'cash');
        $modesStr = implode(',', array_filter($modes));

        InstituteBankAccount::create([
            'institute_id'           => $this->instituteId(),
            'bank_name'              => strtoupper($request->bank_name),
            'account_name'           => strtoupper($request->account_name),
            'account_no'             => $request->account_no,
            'ifsc_code'              => $request->ifsc_code ? strtoupper($request->ifsc_code) : null,
            'branch'                 => $request->branch ? strtoupper($request->branch) : null,
            'upi_id'                 => $request->upi_id,
            'display_label'          => $request->display_label ? strtoupper($request->display_label) : null,
            'allowed_payment_modes'  => $modesStr,
            'is_active'              => true,
            'sort_order'             => (InstituteBankAccount::where('institute_id', $this->instituteId())->max('sort_order') ?? -1) + 1,
        ]);

        return redirect()->route('master.bank-accounts.index')
            ->with('success', 'Bank account added successfully!');
    }

    // ── Edit form ─────────────────────────────────────────────────────
    public function edit(InstituteBankAccount $bankAccount)
    {
        abort_if($bankAccount->institute_id !== $this->instituteId(), 403);
        return view('institute.master.bank-accounts.edit', compact('bankAccount'));
    }

    // ── Update ────────────────────────────────────────────────────────
    public function update(Request $request, InstituteBankAccount $bankAccount)
    {
        abort_if($bankAccount->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'bank_name'     => 'required|string|max:100',
            'account_name'  => 'required|string|max:150',
            'account_no'    => 'required|string|max:50',
            'ifsc_code'     => 'nullable|string|max:20',
            'branch'        => 'nullable|string|max:100',
            'upi_id'        => 'nullable|string|max:100',
            'display_label' => 'nullable|string|max:100',
        ]);

        $modes = $request->input('allowed_payment_modes', []);
        if (!in_array('cash', $modes)) array_unshift($modes, 'cash');
        $modesStr = implode(',', array_filter($modes));

        $bankAccount->update([
            'bank_name'             => strtoupper($request->bank_name),
            'account_name'          => strtoupper($request->account_name),
            'account_no'            => $request->account_no,
            'ifsc_code'             => $request->ifsc_code ? strtoupper($request->ifsc_code) : null,
            'branch'                => $request->branch ? strtoupper($request->branch) : null,
            'upi_id'                => $request->upi_id,
            'display_label'         => $request->display_label ? strtoupper($request->display_label) : null,
            'allowed_payment_modes' => $modesStr,
        ]);

        return redirect()->route('master.bank-accounts.index')
            ->with('success', 'Bank account updated!');
    }

    // ── Toggle active ─────────────────────────────────────────────────
    public function toggle(InstituteBankAccount $bankAccount)
    {
        abort_if($bankAccount->institute_id !== $this->instituteId(), 403);
        $bankAccount->update(['is_active' => !$bankAccount->is_active]);
        return back()->with('success', 'Status updated!');
    }

    // ── Set as the account used for the online admission payment QR ────
    // Only one account per institute can be the online-payment default at a time.
    public function setOnlineDefault(InstituteBankAccount $bankAccount)
    {
        abort_if($bankAccount->institute_id !== $this->instituteId(), 403);
        abort_unless($bankAccount->upi_id, 422, 'This account has no UPI ID set.');

        InstituteBankAccount::where('institute_id', $this->instituteId())
            ->where('id', '!=', $bankAccount->id)
            ->update(['is_online_payment_account' => false]);

        $bankAccount->update(['is_online_payment_account' => true]);

        return back()->with('success', 'This account will now be used for online admission payments.');
    }

    // ── Delete ────────────────────────────────────────────────────────
    public function destroy(InstituteBankAccount $bankAccount)
    {
        abort_if($bankAccount->institute_id !== $this->instituteId(), 403);
        $bankAccount->delete();
        return back()->with('success', 'Bank account deleted!');
    }

    // ── Payment Mode Permissions page ─────────────────────────────────
    public function permissions()
    {
        $instituteId = $this->instituteId();
        $accounts    = InstituteBankAccount::where('institute_id', $instituteId)
            ->where('is_active', true)->orderBy('sort_order')->get();

        $staff   = StaffMember::where('institute_id', $instituteId)->where('status', true)->get();
        $centers = Center::where('institute_id', $instituteId)->where('status', true)->get();
        $partners= ChannelPartner::where('institute_id', $instituteId)->where('status', true)->get();

        // Load existing permissions
        $perms = PaymentModePermission::where('institute_id', $instituteId)
            ->get()->keyBy(fn($p) => $p->user_type.'-'.$p->user_id);

        $allModes = [
            'cash'          => 'Cash',
            'upi'           => 'UPI',
            'cheque'        => 'Cheque',
            'dd'            => 'DD',
            'neft'          => 'NEFT',
            'rtgs'          => 'RTGS',
            'online'        => 'Online',
        ];

        return view('institute.master.bank-accounts.permissions',
            compact('accounts','staff','centers','partners','perms','allModes'));
    }

    // ── Save permissions ──────────────────────────────────────────────
    public function savePermissions(Request $request)
    {
        $instituteId = $this->instituteId();
        $users = $request->input('users', []);

        foreach ($users as $key => $data) {
            [$type, $id] = explode('-', $key);

            PaymentModePermission::updateOrCreate(
                ['user_type' => $type, 'user_id' => (int)$id],
                [
                    'institute_id'     => $instituteId,
                    'allowed_modes'    => $data['modes'] ?? [],
                    'allowed_bank_ids' => array_map('intval', $data['banks'] ?? []),
                ]
            );
        }

        return back()->with('success', 'Payment permissions saved!');
    }
}
