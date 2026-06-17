@extends('institute.layout')
@section('title', 'Archived Partners')
@section('breadcrumb', 'Master / Channel Partners / Archived')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-archive text-secondary me-2"></i>Archived Partners</h4>
        <small class="text-muted">{{ $partners->count() }} archived partner(s)</small>
    </div>
    <a href="{{ route('master.channel-partners.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Back to Partners
    </a>
</div>

@if($partners->isEmpty())
<div class="card border-0 shadow-sm text-center py-5">
    <div class="card-body">
        <i class="bi bi-archive" style="font-size:3rem;color:#94a3b8;"></i>
        <h5 class="mt-3 text-muted">No Archived Partners</h5>
        <p class="text-muted small">Partners you archive will appear here and can be restored anytime.</p>
        <a href="{{ route('master.channel-partners.index') }}" class="btn btn-outline-primary btn-sm mt-1">
            <i class="bi bi-arrow-left me-1"></i> Back to Partners
        </a>
    </div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">#</th>
                    <th>Partner</th>
                    <th>Contact</th>
                    <th>Commission</th>
                    <th>Archived On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($partners as $i => $partner)
                <tr class="opacity-75">
                    <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                    <td>
                        <div class="fw-semibold">{{ $partner->name }}</div>
                        <small class="text-muted">{{ $partner->email }}</small>
                    </td>
                    <td class="small text-muted">
                        {{ $partner->mobile ?? '—' }}
                        @if($partner->city)
                            <br>{{ $partner->city }}
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            {{ $partner->commission_percent }}%
                        </span>
                    </td>
                    <td class="small text-muted">
                        {{ $partner->deleted_at->format('d M Y, h:i A') }}
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            {{-- Restore --}}
                            <form method="POST" action="{{ route('master.channel-partners.restore', $partner->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Restore Partner">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                                </button>
                            </form>

                            {{-- Permanent Delete --}}
                            <button type="button"
                                class="btn btn-sm btn-outline-danger"
                                title="Permanently Delete"
                                onclick="openForceDeleteModal('{{ route('master.channel-partners.force-delete', $partner->id) }}', '{{ addslashes($partner->name) }}')">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Force Delete Confirm Modal --}}
<div class="modal fade" id="forceDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                <div style="width:56px;height:56px;background:#fef2f2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:24px;"></i>
                </div>
                <h5 class="fw-bold mb-1">Permanently Delete?</h5>
                <p class="text-muted mb-1">You are about to permanently delete <strong id="forceDeleteName"></strong>.</p>
                <p class="text-danger small mb-4">This action cannot be undone. All partner data will be lost forever.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="forceDeleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash3 me-1"></i>Delete Forever
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
var _forceModal = new bootstrap.Modal(document.getElementById('forceDeleteModal'));

function openForceDeleteModal(url, name) {
    document.getElementById('forceDeleteForm').action = url;
    document.getElementById('forceDeleteName').textContent = '"' + name + '"';
    _forceModal.show();
}
</script>
@endpush
@endsection
