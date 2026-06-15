@extends($layout ?? 'institute.layout')
@section('title', 'Day Book')
@section('breadcrumb', 'Finance / Day Book')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-journal-richtext me-2 text-primary"></i>Day Book</h4>
        <small class="text-muted">Date range ke saare posted journal entries ek jagah dekho</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-filetype-csv me-1"></i> Export CSV
        </a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-printer me-1"></i> Print / PDF
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Session</label>
                <select name="session_id" class="form-select form-select-sm">
                    <option value="">All Sessions</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ (string) $sessionId === (string) $session->id ? 'selected' : '' }}>
                            {{ $session->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search me-1"></i> Apply
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Total Debit</div>
                <div class="fw-bold fs-4 text-primary">Rs {{ number_format($totalDebit, 2) }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3">
                <div class="small text-muted">Total Credit</div>
                <div class="fw-bold fs-4 text-success">Rs {{ number_format($totalCredit, 2) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Date</th>
                    <th>Narration</th>
                    <th>Reference</th>
                    <th>Lines</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end pe-3">Credit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                <tr>
                    <td class="ps-3 text-muted">{{ $entry->date?->format('d M Y') }}</td>
                    <td>
                        <div class="fw-semibold">{{ $entry->narration ?: 'Journal Entry' }}</div>
                        <div class="small text-muted">{{ ucfirst(str_replace('_', ' ', $entry->reference_type ?? 'manual')) }}</div>
                    </td>
                    <td class="small text-muted">{{ $entry->reference_id ? '#' . $entry->reference_id : '-' }}</td>
                    <td>
                        @foreach($entry->lines as $line)
                            <div class="small">
                                {{ $line->account?->code }} - {{ $line->account?->name }}
                                <span class="text-muted">({{ ucfirst($line->entry_type) }} {{ number_format($line->amount, 2) }})</span>
                            </div>
                        @endforeach
                    </td>
                    <td class="text-end text-primary fw-semibold">{{ number_format($entry->total_debit, 2) }}</td>
                    <td class="text-end pe-3 text-success fw-semibold">{{ number_format($entry->total_credit, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="bi bi-journal-richtext fs-2 d-block mb-2"></i>
                        Is date range me koi journal entries nahi mili.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
