@extends($libraryLayout)
@section('title', $book->title)
@section('breadcrumb', 'Library / Books / ' . $book->title)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">{{ $book->title }}</h4>
        <small class="text-muted">{{ $book->authors->pluck('name')->implode(', ') ?: ($book->author_text ?: 'No author info') }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route($libraryRoutePrefix . '.books.labels', $book) }}" class="btn btn-outline-dark btn-sm"><i class="bi bi-upc-scan me-1"></i>Labels</a>
        <a href="{{ route($libraryRoutePrefix . '.books.edit', $book) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit Title</a>
        <a href="{{ route($libraryRoutePrefix . '.books.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>
</div>

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

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold">Book Snapshot</span></div>
            <div class="card-body">
                <div class="mb-2"><span class="text-muted small">Category</span><div class="fw-semibold">{{ $book->category->name ?? '-' }}</div></div>
                <div class="mb-2"><span class="text-muted small">Publisher</span><div class="fw-semibold">{{ $book->publisher->name ?? '-' }}</div></div>
                <div class="mb-2"><span class="text-muted small">ISBN</span><div class="fw-semibold">{{ $book->isbn ?: '-' }}</div></div>
                <div class="mb-2"><span class="text-muted small">Edition</span><div class="fw-semibold">{{ $book->edition ?: '-' }}</div></div>
                <div class="mb-2"><span class="text-muted small">Language</span><div class="fw-semibold">{{ $book->language ?: '-' }}</div></div>
                <div class="mb-2"><span class="text-muted small">Subject</span><div class="fw-semibold">{{ $book->subject->name ?? ($book->subject_name ?: '-') }}</div></div>
                <div><span class="text-muted small">Description</span><div>{{ $book->description ?: '-' }}</div></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Add Book Copies</span>
                <span class="badge bg-primary-subtle text-primary border">Bulk supported</span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route($libraryRoutePrefix . '.books.copies.store', $book) }}">
                    @csrf

                    {{-- Accession Number Config --}}
                    <div class="border rounded-3 p-3 mb-3 bg-light">
                        <div class="fw-semibold small mb-2 text-muted">Accession Number</div>
                        <div class="row g-2">
                            <div class="col-7">
                                <label class="form-label small fw-semibold">Prefix <span class="text-danger">*</span></label>
                                <input type="text" name="accession_prefix" id="accPrefix"
                                       class="form-control" placeholder="e.g. ACC-" required
                                       oninput="updatePreview()">
                                <div class="form-text">Book title code ya serial prefix</div>
                            </div>
                            <div class="col-5">
                                <label class="form-label small fw-semibold">No. of Copies <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" id="accQty"
                                       class="form-control" value="1" min="1" max="50" required
                                       oninput="updatePreview()">
                            </div>
                            <div class="col-7">
                                <label class="form-label small fw-semibold">Start From <span class="text-danger">*</span></label>
                                <input type="text" name="accession_start" id="accStart"
                                       class="form-control" placeholder="001" pattern="\d+"
                                       title="Sirf numbers dalein" required
                                       oninput="updatePreview()">
                                <div class="form-text">Leading zeros rakhe jaaenge (001, 002...)</div>
                            </div>
                        </div>

                        {{-- Live Preview --}}
                        <div id="accPreview" class="mt-2 p-2 rounded bg-white border small text-muted" style="min-height:32px;">
                            <span class="fw-semibold text-dark">Preview:</span> —
                        </div>
                    </div>

                    {{-- Common fields for all copies --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Rack</label>
                        <select name="rack_id" class="form-select">
                            <option value="">Select</option>
                            @foreach($racks as $rack)
                                <option value="{{ $rack->id }}">{{ $rack->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Vendor</label>
                        <select name="vendor_id" class="form-select">
                            <option value="">Select</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Purchase Date</label>
                            <input type="date" name="purchase_date" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Price per Copy</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            @foreach(['available','issued','reserved','lost','damaged','withdrawn'] as $s)
                                <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Condition Note</label>
                        <input type="text" name="condition_note" class="form-control" placeholder="Optional">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-plus-circle me-1"></i>Add Copies
                    </button>
                </form>
            </div>
        </div>

        <script>
        function updatePreview() {
            const prefix = document.getElementById('accPrefix').value;
            const startRaw = document.getElementById('accStart').value.trim();
            const qty = parseInt(document.getElementById('accQty').value) || 1;
            const el = document.getElementById('accPreview');

            if (!prefix || !startRaw || !/^\d+$/.test(startRaw)) {
                el.innerHTML = '<span class="fw-semibold text-dark">Preview:</span> —';
                return;
            }

            const start = parseInt(startRaw);
            const pad = startRaw.length;
            const shown = Math.min(qty, 5);
            const parts = [];
            for (let i = 0; i < shown; i++) {
                parts.push(prefix + String(start + i).padStart(pad, '0'));
            }
            let preview = parts.map(p => `<code class="bg-primary-subtle rounded px-1">${p}</code>`).join(' ');
            if (qty > shown) preview += ` <span class="text-muted">... +${qty - shown} more</span>`;

            el.innerHTML = `<span class="fw-semibold text-dark">Preview (${qty} cop${qty === 1 ? 'y' : 'ies'}):</span> ${preview}`;
        }
        </script>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Physical Copies</span>
                <span class="badge bg-secondary-subtle text-secondary border">{{ $book->copies->count() }} total</span>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Access No</th>
                            <th>Rack</th>
                            <th>Status</th>
                            <th>Details</th>
                            <th>Update</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($book->copies as $copy)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $copy->accession_no }}</div>
                                <small class="text-muted">{{ $copy->barcode ?: 'No barcode' }}</small>
                            </td>
                            <td>{{ $copy->rack->display_name ?? '-' }}</td>
                            <td><span class="badge bg-{{ $copy->status === 'available' ? 'success' : ($copy->status === 'issued' ? 'primary' : 'secondary') }}">{{ ucfirst($copy->status) }}</span></td>
                            <td>
                                <small class="d-block">Purchase: {{ optional($copy->purchase_date)->format('d-m-Y') ?: '-' }}</small>
                                <small class="d-block text-muted">Price: Rs {{ number_format((float) $copy->price, 2) }}</small>
                                <small class="d-block text-muted">Vendor: {{ $copy->vendor->name ?? '-' }}</small>
                            </td>
                            <td>
                                <form method="POST" action="{{ route($libraryRoutePrefix . '.books.copies.update', [$book, $copy]) }}" class="row g-2">
                                    @csrf
                                    @method('PUT')
                                    <div class="col-12">
                                        <select name="rack_id" class="form-select form-select-sm">
                                            <option value="">Select rack</option>
                                            @foreach($racks as $rack)
                                                <option value="{{ $rack->id }}" @selected($copy->rack_id == $rack->id)>{{ $rack->display_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <select name="vendor_id" class="form-select form-select-sm">
                                            <option value="">Select vendor</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}" @selected($copy->vendor_id == $vendor->id)>{{ $vendor->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <input type="hidden" name="accession_no" value="{{ $copy->accession_no }}">
                                    <input type="hidden" name="barcode" value="{{ $copy->barcode }}">
                                    <input type="hidden" name="purchase_date" value="{{ optional($copy->purchase_date)->format('Y-m-d') }}">
                                    <input type="hidden" name="price" value="{{ $copy->price }}">
                                    <input type="hidden" name="condition_note" value="{{ $copy->condition_note }}">
                                    <div class="col-8">
                                        <select name="status" class="form-select form-select-sm">
                                            @foreach(['available','issued','reserved','lost','damaged','withdrawn'] as $status)
                                                <option value="{{ $status }}" @selected($copy->status === $status)>{{ ucfirst($status) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <button type="submit" class="btn btn-outline-primary btn-sm w-100">Save</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Abhi koi copy add nahi hui.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom"><span class="fw-semibold">Recent Movement</span></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Member</th>
                            <th>Copy</th>
                            <th>Status</th>
                            <th>Issue</th>
                            <th>Return</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->member->name ?? '-' }}</td>
                            <td>{{ $transaction->copy->accession_no ?? '-' }}</td>
                            <td>{{ ucfirst($transaction->current_status) }}</td>
                            <td>{{ optional($transaction->issued_on)->format('d-m-Y') }}</td>
                            <td>{{ optional($transaction->returned_on)->format('d-m-Y') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Abhi koi transaction nahi hai.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
