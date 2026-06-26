<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\Library\LibraryBook;
use App\Models\Library\LibraryBookCopy;
use App\Models\Library\LibraryMember;
use App\Models\Library\LibraryReservation;
use App\Models\Library\LibraryTransaction;
use App\Services\LibraryManagementService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class LibraryReservationController extends BaseLibraryController
{
    public function index(Request $request)
    {
        $this->ensureLibraryPermission('reservations');

        $instituteId = $this->instituteId();
        $reservationsEnabled = LibraryManagementService::hasReservationsTable();

        if ($reservationsEnabled) {
            LibraryManagementService::expireReservations($instituteId);
        }

        $memberSearch = trim((string) $request->input('member_search', ''));
        $bookSearch   = trim((string) $request->input('book_search', ''));
        $memberLike   = $this->escapeLike($memberSearch);
        $bookLike     = $this->escapeLike($bookSearch);

        $members = LibraryMember::forInstitute($instituteId)
            ->with('ruleSet')
            ->when($memberSearch !== '', function ($query) use ($memberLike) {
                $query->where(function ($builder) use ($memberLike) {
                    $builder->where('member_code', 'like', '%' . $memberLike . '%')
                        ->orWhere('name', 'like', '%' . $memberLike . '%')
                        ->orWhere('mobile', 'like', '%' . $memberLike . '%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        $books = LibraryBook::forInstitute($instituteId)
            ->with('copies')
            ->when($bookSearch !== '', function ($query) use ($bookLike) {
                $query->where(function ($builder) use ($bookLike) {
                    $builder->where('title', 'like', '%' . $bookLike . '%')
                        ->orWhere('isbn', 'like', '%' . $bookLike . '%')
                        ->orWhere('subject_name', 'like', '%' . $bookLike . '%');
                });
            })
            ->orderBy('title')
            ->limit(20)
            ->get();

        $reservations = $reservationsEnabled
            ? LibraryReservation::forInstitute($instituteId)
                ->with(['member', 'book.copies', 'fulfilledCopy'])
                ->latest('id')
                ->paginate(20)
                ->withQueryString()
            : new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);

        return view('institute.library.reservations.index', compact(
            'members',
            'books',
            'reservations',
            'memberSearch',
            'bookSearch',
            'reservationsEnabled'
        ));
    }

    public function store(Request $request)
    {
        $this->ensureLibraryPermission('reservations');

        if (!LibraryManagementService::hasReservationsTable()) {
            return back()->withErrors(['reservation' => 'Reservation feature requires the `library_reservations` table. Please run database migrations.']);
        }

        $data = $request->validate([
            'library_member_id' => 'required|integer',
            'book_id' => 'required|integer',
            'expires_on' => 'nullable|date',
            'remarks' => 'nullable|string|max:255',
        ]);

        $member = LibraryMember::forInstitute($this->instituteId())->with('ruleSet')->findOrFail($data['library_member_id']);
        $book = LibraryBook::forInstitute($this->instituteId())->with('copies')->findOrFail($data['book_id']);

        LibraryManagementService::ensureReservationCanBeCreated($member, (int) $book->id, $this->instituteId());

        if ($book->copies->where('status', 'available')->count() > 0) {
            throw ValidationException::withMessages(['book_id' => 'An available copy exists for this title. Please use the issue workflow directly.']);
        }

        $alreadyPending = LibraryReservation::forInstitute($this->instituteId())
            ->where('library_member_id', $member->id)
            ->where('book_id', $book->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            throw ValidationException::withMessages(['book_id' => 'This member already has a pending reservation for this title.']);
        }

        LibraryReservation::create([
            'institute_id' => $this->instituteId(),
            'library_member_id' => $member->id,
            'book_id' => $book->id,
            'status' => 'pending',
            'reserved_on' => now()->toDateString(),
            'expires_on' => $data['expires_on'] ?: now()->addDays(3)->toDateString(),
            'remarks' => trim((string) ($data['remarks'] ?? '')) ?: null,
        ]);

        return back()->with('success', 'Reservation created successfully.');
    }

    public function fulfill(Request $request, LibraryReservation $reservation)
    {
        $this->ensureLibraryPermission('reservations');

        if (!LibraryManagementService::hasReservationsTable()) {
            return back()->withErrors(['reservation' => 'Reservation feature is not set up in the database yet.']);
        }

        abort_if($reservation->institute_id !== $this->instituteId(), 403);

        LibraryManagementService::ensureReservationCanBeFulfilled($reservation->loadMissing(['book', 'member.ruleSet']));

        DB::transaction(function () use ($request, $reservation) {
            $copy = null;

            if ($request->filled('copy_id')) {
                $copy = LibraryBookCopy::forInstitute($this->instituteId())
                    ->where('book_id', $reservation->book_id)
                    ->findOrFail((int) $request->input('copy_id'));
            } else {
                $copy = LibraryBookCopy::forInstitute($this->instituteId())
                    ->where('book_id', $reservation->book_id)
                    ->where('status', 'available')
                    ->orderBy('id')
                    ->first();
            }

            if (!$copy || $copy->status !== 'available') {
                throw ValidationException::withMessages(['copy_id' => 'No available copy found to fulfill this reservation.']);
            }

            $member = $reservation->member()->with(['ruleSet', 'activeTransactions'])->firstOrFail();
            $rule = LibraryManagementService::ensureMemberCanBorrow($member);

            $issuedOn = Carbon::today();

            LibraryTransaction::create([
                'institute_id' => $this->instituteId(),
                'library_member_id' => $member->id,
                'library_book_copy_id' => $copy->id,
                'academic_session_id' => $this->activeSessionId(),
                'txn_type' => 'issue',
                'current_status' => 'issued',
                'issued_on' => $issuedOn->toDateString(),
                'due_on' => $issuedOn->copy()->addDays($rule->loan_days)->toDateString(),
                'loan_days_snapshot' => $rule->loan_days,
                'fine_per_day_snapshot' => $rule->fine_per_day,
                'grace_days_snapshot' => $rule->grace_days,
                'max_renewals_snapshot' => $rule->max_renewals,
                'rule_name_snapshot' => $rule->name,
                'remarks' => $reservation->remarks,
                'issued_by' => $this->actorName(),
            ]);

            $copy->update(['status' => 'issued']);

            $reservation->update([
                'status' => 'fulfilled',
                'fulfilled_copy_id' => $copy->id,
            ]);
        });

        return back()->with('success', 'Reservation fulfilled and book issued successfully.');
    }

    public function cancel(LibraryReservation $reservation)
    {
        $this->ensureLibraryPermission('reservations');

        if (!LibraryManagementService::hasReservationsTable()) {
            return back()->withErrors(['reservation' => 'Reservation feature is not set up in the database yet.']);
        }

        abort_if($reservation->institute_id !== $this->instituteId(), 403);

        if ($reservation->status !== 'pending') {
            return back()->withErrors(['reservation' => 'Only pending reservations can be cancelled. Current status: ' . $reservation->status]);
        }

        $reservation->update(['status' => 'cancelled']);

        return back()->with('success', 'Reservation cancelled.');
    }
}
