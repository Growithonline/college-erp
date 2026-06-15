@extends($libraryLayout)
@section('title', 'Library Reservations')
@section('breadcrumb', 'Library / Reservations')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Reservations & Hold Queue</h4>
        <small class="text-muted">Pending waitlist, queue priority aur fulfilment yahin manage karo.</small>
    </div>
    <a href="{{ route($libraryRoutePrefix . '.circulation.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left-right me-1"></i>Circulation</a>
</div>

@if(!$reservationsEnabled)
    <div class="alert alert-warning">
        Reservation table abhi database me create nahi hui hai. Pehle latest library migrations run karni hongi, tab reservation queue fully work karegi.
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold">Create Reservation</span></div>
            <div class="card-body">
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-6">
                        <input type="text" name="member_search" value="{{ $memberSearch }}" class="form-control" placeholder="Member search">
                    </div>
                    <div class="col-6">
                        <input type="text" name="book_search" value="{{ $bookSearch }}" class="form-control" placeholder="Book search">
                    </div>
                </form>

                <form method="POST" action="{{ route($libraryRoutePrefix . '.reservations.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Member</label>
                        <select name="library_member_id" class="form-select" required>
                            <option value="">Select member</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}">{{ $member->member_code }} - {{ $member->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Book Title</label>
                        <select name="book_id" class="form-select" required>
                            <option value="">Select book</option>
                            @foreach($books as $book)
                                <option value="{{ $book->id }}">{{ $book->title }} ({{ $book->copies->where('status', 'available')->count() }} available)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Expiry Date</label>
                            <input type="date" name="expires_on" value="{{ now()->addDays(3)->toDateString() }}" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3" @disabled(!$reservationsEnabled)><i class="bi bi-bookmark-plus me-1"></i>Create Reservation</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold">Queue Snapshot</span></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Member</th>
                            <th>Book</th>
                            <th>Status</th>
                            <th>Queue</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($reservations as $reservation)
                        @php
                            $queue = $reservations->where('book_id', $reservation->book_id)->where('status', 'pending')->sortBy('reserved_on')->values();
                            $position = optional($queue->search(fn($item) => $item->id === $reservation->id));
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $reservation->member->name ?? '-' }}</div>
                                <small class="text-muted">{{ $reservation->member->member_code ?? '-' }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $reservation->book->title ?? '-' }}</div>
                                <small class="text-muted">Expires: {{ optional($reservation->expires_on)->format('d-m-Y') ?: '-' }}</small>
                            </td>
                            <td><span class="badge bg-{{ $reservation->status === 'pending' ? 'warning text-dark' : ($reservation->status === 'fulfilled' ? 'success' : 'secondary') }}">{{ ucfirst($reservation->status) }}</span></td>
                            <td>{{ $reservation->status === 'pending' ? (($position ?? -1) + 1) : '-' }}</td>
                            <td class="d-flex gap-2">
                                @if($reservation->status === 'pending')
                                    <form method="POST" action="{{ route($libraryRoutePrefix . '.reservations.fulfill', $reservation) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-sm">Fulfill</button>
                                    </form>
                                    <form method="POST" action="{{ route($libraryRoutePrefix . '.reservations.cancel', $reservation) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Cancel</button>
                                    </form>
                                @else
                                    <span class="text-muted small">Closed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">Abhi koi reservation nahi hai.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($reservations->hasPages())
                <div class="card-footer bg-white">{{ $reservations->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
