<?php

namespace App\Http\Controllers\Institute\Fee;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\CenterWallet;
use App\Models\ChannelPartner;
use App\Models\ChannelWallet;
use App\Models\WalletExtensionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeeWalletController extends Controller
{
    private function instituteId(): int
    {
        return (int) Auth::user()->institute_id;
    }

    // ── Center Wallets ────────────────────────────────────────────────────

    public function centerIndex()
    {
        $centers = Center::where('institute_id', $this->instituteId())
            ->with('wallet')
            ->orderBy('name')
            ->get();

        $pendingCount = WalletExtensionRequest::where('institute_id', $this->instituteId())
            ->where('status', 'pending')
            ->count();

        return view('institute.fee.wallets.center-index', compact('centers', 'pendingCount'));
    }

    public function createCenter(Center $center)
    {
        abort_unless($center->institute_id === $this->instituteId(), 403);
        $wallet = $center->wallet;
        return view('institute.fee.wallets.form', compact('center', 'wallet') + ['type' => 'center']);
    }

    public function storeCenter(Request $request, Center $center)
    {
        abort_unless($center->institute_id === $this->instituteId(), 403);

        $data = $request->validate([
            'amount'     => 'required|numeric|min:1',
            'expires_at' => 'required|date|after:today',
            'notes'      => 'nullable|string|max:500',
        ]);

        $wallet = $center->wallet;

        if ($wallet) {
            $added = (float) $data['amount'];
            $wallet->update([
                'total_tokens'     => (float) $wallet->total_tokens + $added,
                'remaining_tokens' => (float) $wallet->remaining_tokens + $added,
                'expires_at'       => $data['expires_at'],
                'status'           => 'active',
                'notes'            => $data['notes'] ?? $wallet->notes,
            ]);
            $wallet->transactions()->create([
                'type'          => 'credit',
                'amount'        => $added,
                'balance_after' => $wallet->fresh()->remaining_tokens,
                'note'          => 'Admin token top-up',
                'created_by'    => Auth::id(),
            ]);
        } else {
            $wallet = CenterWallet::create([
                'center_id'        => $center->id,
                'institute_id'     => $this->instituteId(),
                'total_tokens'     => $data['amount'],
                'used_tokens'      => 0,
                'remaining_tokens' => $data['amount'],
                'expires_at'       => $data['expires_at'],
                'status'           => 'active',
                'notes'            => $data['notes'] ?? null,
                'created_by'       => Auth::id(),
            ]);
            $wallet->transactions()->create([
                'type'          => 'credit',
                'amount'        => $data['amount'],
                'balance_after' => $data['amount'],
                'note'          => 'Initial token allocation',
                'created_by'    => Auth::id(),
            ]);
        }

        return redirect()->route('fee-wallets.centers')
            ->with('success', "Wallet updated for {$center->name}.");
    }

    public function centerTopup(Request $request, CenterWallet $wallet)
    {
        abort_unless($wallet->institute_id === $this->instituteId(), 403);

        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'note'   => 'nullable|string|max:255',
        ]);

        $wallet->credit((float) $data['amount'], $data['note'] ?? 'Admin top-up', Auth::id());

        return back()->with('success', 'Tokens added successfully.');
    }

    public function centerExtend(Request $request, CenterWallet $wallet)
    {
        abort_unless($wallet->institute_id === $this->instituteId(), 403);

        $data = $request->validate([
            'expires_at' => 'required|date|after:today',
        ]);

        $wallet->update(['expires_at' => $data['expires_at'], 'status' => 'active']);

        return back()->with('success', 'Expiry date updated.');
    }

    public function centerToggle(CenterWallet $wallet)
    {
        abort_unless($wallet->institute_id === $this->instituteId(), 403);
        $wallet->update(['status' => $wallet->status === 'active' ? 'suspended' : 'active']);
        return back()->with('success', 'Wallet status updated.');
    }

    public function centerTransactions(CenterWallet $wallet)
    {
        abort_unless($wallet->institute_id === $this->instituteId(), 403);
        $transactions = $wallet->transactions()->with('invoice')->orderByDesc('id')->paginate(30);
        return view('institute.fee.wallets.show', compact('wallet', 'transactions') + ['type' => 'center']);
    }

    // ── Channel Wallets ───────────────────────────────────────────────────

    public function channelIndex()
    {
        $partners = ChannelPartner::where('institute_id', $this->instituteId())
            ->with('wallet')
            ->orderBy('name')
            ->get();

        $pendingCount = WalletExtensionRequest::where('institute_id', $this->instituteId())
            ->where('status', 'pending')
            ->count();

        return view('institute.fee.wallets.channel-index', compact('partners', 'pendingCount'));
    }

    public function createChannel(ChannelPartner $channelPartner)
    {
        abort_unless($channelPartner->institute_id === $this->instituteId(), 403);
        $wallet = $channelPartner->wallet;
        return view('institute.fee.wallets.form', ['entity' => $channelPartner, 'wallet' => $wallet, 'type' => 'channel']);
    }

    public function storeChannel(Request $request, ChannelPartner $channelPartner)
    {
        abort_unless($channelPartner->institute_id === $this->instituteId(), 403);

        $data = $request->validate([
            'amount'     => 'required|numeric|min:1',
            'expires_at' => 'required|date|after:today',
            'notes'      => 'nullable|string|max:500',
        ]);

        $wallet = $channelPartner->wallet;

        if ($wallet) {
            $added = (float) $data['amount'];
            $wallet->update([
                'total_tokens'     => (float) $wallet->total_tokens + $added,
                'remaining_tokens' => (float) $wallet->remaining_tokens + $added,
                'expires_at'       => $data['expires_at'],
                'status'           => 'active',
                'notes'            => $data['notes'] ?? $wallet->notes,
            ]);
            $wallet->transactions()->create([
                'type'          => 'credit',
                'amount'        => $added,
                'balance_after' => $wallet->fresh()->remaining_tokens,
                'note'          => 'Admin token top-up',
                'created_by'    => Auth::id(),
            ]);
        } else {
            $wallet = ChannelWallet::create([
                'channel_partner_id' => $channelPartner->id,
                'institute_id'       => $this->instituteId(),
                'total_tokens'       => $data['amount'],
                'used_tokens'        => 0,
                'remaining_tokens'   => $data['amount'],
                'expires_at'         => $data['expires_at'],
                'status'             => 'active',
                'notes'              => $data['notes'] ?? null,
                'created_by'         => Auth::id(),
            ]);
            $wallet->transactions()->create([
                'type'          => 'credit',
                'amount'        => $data['amount'],
                'balance_after' => $data['amount'],
                'note'          => 'Initial token allocation',
                'created_by'    => Auth::id(),
            ]);
        }

        return redirect()->route('fee-wallets.channels')
            ->with('success', "Wallet updated for {$channelPartner->name}.");
    }

    public function channelTopup(Request $request, ChannelWallet $wallet)
    {
        abort_unless($wallet->institute_id === $this->instituteId(), 403);

        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'note'   => 'nullable|string|max:255',
        ]);

        $wallet->credit((float) $data['amount'], $data['note'] ?? 'Admin top-up', Auth::id());

        return back()->with('success', 'Tokens added successfully.');
    }

    public function channelExtend(Request $request, ChannelWallet $wallet)
    {
        abort_unless($wallet->institute_id === $this->instituteId(), 403);

        $data = $request->validate([
            'expires_at' => 'required|date|after:today',
        ]);

        $wallet->update(['expires_at' => $data['expires_at'], 'status' => 'active']);

        return back()->with('success', 'Expiry date updated.');
    }

    public function channelToggle(ChannelWallet $wallet)
    {
        abort_unless($wallet->institute_id === $this->instituteId(), 403);
        $wallet->update(['status' => $wallet->status === 'active' ? 'suspended' : 'active']);
        return back()->with('success', 'Wallet status updated.');
    }

    public function channelTransactions(ChannelWallet $wallet)
    {
        abort_unless($wallet->institute_id === $this->instituteId(), 403);
        $transactions = $wallet->transactions()->with('invoice')->orderByDesc('id')->paginate(30);
        return view('institute.fee.wallets.show', compact('wallet', 'transactions') + ['type' => 'channel']);
    }

    // ── Extension Requests ────────────────────────────────────────────────

    public function extensionRequests()
    {
        $requests = WalletExtensionRequest::where('institute_id', $this->instituteId())
            ->orderByRaw("FIELD(status,'pending','approved','rejected')")
            ->orderByDesc('id')
            ->paginate(30);

        return view('institute.fee.wallets.extension-requests', compact('requests'));
    }

    public function approveRequest(Request $request, WalletExtensionRequest $extensionRequest)
    {
        abort_unless($extensionRequest->institute_id === $this->instituteId(), 403);
        abort_unless($extensionRequest->status === 'pending', 422, 'Request already processed.');

        $data = $request->validate(['admin_note' => 'nullable|string|max:500']);

        if ($extensionRequest->request_type === 'expiry_extension') {
            $data2 = $request->validate(['new_expires_at' => 'required|date|after:today']);
            if ($extensionRequest->entity_type === 'center') {
                $wallet = CenterWallet::where('center_id', $extensionRequest->entity_id)->first();
                $wallet?->update(['expires_at' => $data2['new_expires_at'], 'status' => 'active']);
            } else {
                $wallet = ChannelWallet::where('channel_partner_id', $extensionRequest->entity_id)->first();
                $wallet?->update(['expires_at' => $data2['new_expires_at'], 'status' => 'active']);
            }
        } else {
            $data2 = $request->validate(['approved_amount' => 'required|numeric|min:1']);
            if ($extensionRequest->entity_type === 'center') {
                $wallet = CenterWallet::where('center_id', $extensionRequest->entity_id)->first();
                $wallet?->credit((float) $data2['approved_amount'], 'Admin top-up (request approved)', Auth::id());
            } else {
                $wallet = ChannelWallet::where('channel_partner_id', $extensionRequest->entity_id)->first();
                $wallet?->credit((float) $data2['approved_amount'], 'Admin top-up (request approved)', Auth::id());
            }
        }

        $extensionRequest->update([
            'status'       => 'approved',
            'admin_note'   => $data['admin_note'] ?? null,
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Request approved and wallet updated.');
    }

    public function rejectRequest(Request $request, WalletExtensionRequest $extensionRequest)
    {
        abort_unless($extensionRequest->institute_id === $this->instituteId(), 403);
        abort_unless($extensionRequest->status === 'pending', 422, 'Request already processed.');

        $data = $request->validate(['admin_note' => 'nullable|string|max:500']);

        $extensionRequest->update([
            'status'       => 'rejected',
            'admin_note'   => $data['admin_note'] ?? null,
            'processed_by' => Auth::id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', 'Request rejected.');
    }
}
