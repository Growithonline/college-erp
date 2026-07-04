@extends('super_admin.layout')

@section('title', 'Database Backup')

@section('breadcrumb')
    <li class="breadcrumb-item active">Database Backup</li>
@endsection

@section('content')
<div class="mb-4">
    <h5 class="fw-bold mb-1"><i class="bi bi-database-down me-2 text-primary"></i>Database Backup</h5>
    <p class="text-muted small mb-0">Full system database — all institutes' data included.</p>
</div>

{{-- Download Cards --}}
<div class="row g-3 mb-4">

    {{-- Full Backup --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 p-2" style="background:#eff6ff;">
                        <i class="bi bi-database-fill-down fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Full Database Backup</h6>
                        <small class="text-muted">Schema + All Data — compressed .sql.gz</small>
                    </div>
                </div>
                <p class="text-muted small mb-3">
                    Complete dump of the <strong>entire database</strong> — all institutes, students, fees,
                    transport, library, staff, settings and more. Use this to restore the full system.
                </p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">All Tables</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">All Data</span>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Gzip Compressed</span>
                </div>
                <a href="{{ route('super_admin.backup.full') }}"
                   class="btn btn-primary w-100"
                   onclick="showDownloadToast('Full Backup', 'Full database backup is downloading...')">
                    <i class="bi bi-download me-2"></i>Download Full Backup (.sql.gz)
                </a>
            </div>
        </div>
    </div>

    {{-- Schema Only --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 p-2" style="background:#f5f3ff;">
                        <i class="bi bi-file-earmark-code fs-4" style="color:#7c3aed;"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Schema Only</h6>
                        <small class="text-muted">Table structures — no data — plain .sql</small>
                    </div>
                </div>
                <p class="text-muted small mb-3">
                    Only the <strong>CREATE TABLE</strong> statements — no actual data rows.
                    Use this to set up a blank copy of the database on a new server or for development.
                </p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-purple-subtle border" style="background:#f5f3ff;color:#7c3aed;border-color:#ddd8fe!important;">All Tables</span>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">No Data</span>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Plain SQL</span>
                </div>
                <a href="{{ route('super_admin.backup.schema') }}"
                   class="btn w-100 text-white"
                   style="background:#7c3aed;"
                   onclick="showDownloadToast('Schema Backup', 'Schema-only SQL file is downloading...')">
                    <i class="bi bi-download me-2"></i>Download Schema Only (.sql)
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Scheduled Backups --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2 text-muted"></i>Scheduled Backups</span>
        <small class="text-muted">Daily at 2:00 AM · Last 14 days kept</small>
    </div>
    <div class="card-body p-0">
        @if(count($files) > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">File</th>
                        <th>Size</th>
                        <th>Created</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($files as $file)
                    <tr>
                        <td class="ps-4">
                            <i class="bi bi-file-zip text-success me-2"></i>
                            <span class="font-monospace">{{ $file['name'] }}</span>
                        </td>
                        <td class="text-muted">{{ $file['size'] }}</td>
                        <td class="text-muted">{{ $file['modified'] }}</td>
                        <td class="pe-4 text-end">
                            <a href="{{ route('super_admin.backup.download', $file['name']) }}"
                               class="btn btn-sm btn-outline-success"
                               onclick="showDownloadToast('Scheduled Backup', '{{ $file[\'name\'] }} is downloading...')">
                                <i class="bi bi-download me-1"></i>Download
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-clock-history fs-2 d-block mb-2 opacity-25"></i>
            No scheduled backups yet.<br>
            <small>First automatic backup will run tonight at 2:00 AM.</small>
        </div>
        @endif
    </div>
</div>

{{-- Toast --}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:9999;">
    <div id="downloadToast" class="toast align-items-center text-bg-success border-0 shadow" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <strong id="toastTitle"></strong><br>
                <span id="toastMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
function showDownloadToast(title, message) {
    document.getElementById('toastTitle').textContent   = title + ' — Download Started';
    document.getElementById('toastMessage').textContent = message;
    const toastEl = document.getElementById('downloadToast');
    bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 4000 }).show();
}
</script>
@endsection
