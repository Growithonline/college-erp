@php
    $filters       = $filters ?? [];
    $isInitialLoad = $isInitialLoad ?? false;
    $activeFilters = collect($filters)->filter(fn($v) => trim((string) $v) !== '');
    $resultCount   = ($students && method_exists($students, 'total')) ? $students->total() : ($students?->count() ?? 0);
@endphp

@if($isInitialLoad)
<div class="mb-2 small text-muted">
    <i class="bi bi-clock-history me-1"></i> Showing <strong>{{ $resultCount }}</strong> recently admitted students — type in any column to filter
</div>
@elseif($activeFilters->isNotEmpty())
<div class="mb-2 small text-muted">
    Showing <strong>{{ number_format($resultCount) }}</strong> result(s)
</div>
@endif

<style>
.gs-table { font-size: 11.5px; }
.gs-table th { font-size: 11px; font-weight: 700; white-space: nowrap; padding: 5px 7px !important; }
.gs-table td { padding: 5px 7px !important; vertical-align: middle; }
.gs-table .form-control-sm { font-size: 11px; padding: 2px 5px; height: 26px; }
.gs-table .badge { font-size: 10px; }
.gs-table .btn-xs { padding: 2px 6px; font-size: 11px; line-height: 1.4; }
</style>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-0 gs-table">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:36px;">#</th>
                        <th style="min-width:100px;">Student ID</th>
                        <th style="min-width:130px;">Name</th>
                        <th style="min-width:110px;">Father</th>
                        <th style="min-width:110px;">Mother</th>
                        <th style="min-width:95px;">Mobile</th>
                        <th style="min-width:140px;">Email</th>
                        <th style="min-width:90px;">UIN No</th>
                        <th style="min-width:90px;">Enroll No</th>
                        <th style="min-width:80px;">Roll No</th>
                        <th style="min-width:130px;">Programme</th>
                        <th style="min-width:80px;">Session</th>
                        <th style="min-width:75px;">Status</th>
                        <th class="text-center" style="min-width:80px;">Actions</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th>
                            <input type="text" name="student_id" class="form-control form-control-sm global-search-input"
                                   value="{{ $filters['student_id'] ?? '' }}" placeholder="Search...">
                        </th>
                        <th>
                            <input type="text" name="student_name" class="form-control form-control-sm global-search-input"
                                   value="{{ $filters['student_name'] ?? '' }}" placeholder="Search...">
                        </th>
                        <th>
                            <input type="text" name="father_name" class="form-control form-control-sm global-search-input"
                                   value="{{ $filters['father_name'] ?? '' }}" placeholder="Search...">
                        </th>
                        <th>
                            <input type="text" name="mother_name" class="form-control form-control-sm global-search-input"
                                   value="{{ $filters['mother_name'] ?? '' }}" placeholder="Search...">
                        </th>
                        <th>
                            <input type="text" name="mobile" class="form-control form-control-sm global-search-input"
                                   value="{{ $filters['mobile'] ?? '' }}" placeholder="Search...">
                        </th>
                        <th>
                            <input type="text" name="email" class="form-control form-control-sm global-search-input"
                                   value="{{ $filters['email'] ?? '' }}" placeholder="Search...">
                        </th>
                        <th>
                            <input type="text" name="uin_no" class="form-control form-control-sm global-search-input"
                                   value="{{ $filters['uin_no'] ?? '' }}" placeholder="Search...">
                        </th>
                        <th>
                            <input type="text" name="enrollment_no" class="form-control form-control-sm global-search-input"
                                   value="{{ $filters['enrollment_no'] ?? '' }}" placeholder="Search...">
                        </th>
                        <th>
                            <input type="text" name="roll_no" class="form-control form-control-sm global-search-input"
                                   value="{{ $filters['roll_no'] ?? '' }}" placeholder="Search...">
                        </th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @if($students && $students->count() > 0)
                        @foreach($students as $student)
                        <tr>
                            <td class="text-center text-muted" style="font-size:10.5px;">
                                @if(method_exists($students, 'firstItem'))
                                    {{ $students->firstItem() + $loop->index }}
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </td>
                            <td style="white-space:nowrap;">
                                <a href="{{ route($profileRoute ?? 'admissions.show', $student->id) }}"
                                   class="text-primary fw-semibold text-decoration-none" style="font-size:11px;">
                                    {{ $student->student_uid ?? '—' }}
                                </a>
                            </td>
                            <td class="fw-semibold">{{ $student->name }}</td>
                            <td>{{ $student->father_name ?? '—' }}</td>
                            <td>{{ $student->mother_name ?? '—' }}</td>
                            <td style="white-space:nowrap;">{{ $student->mobile ?? '—' }}</td>
                            <td style="word-break:break-all;">{{ $student->email ?? '—' }}</td>
                            <td>{{ $student->uin_no ?? '—' }}</td>
                            <td>{{ $student->enrollment_no ?? '—' }}</td>
                            <td>{{ $student->roll_no ?? '—' }}</td>
                            <td>
                                <div style="font-size:11px;">{{ $student->stream->course->name ?? '—' }}</div>
                                @if($student->stream?->name)
                                    <div class="text-muted" style="font-size:10px;">{{ $student->stream->name }}</div>
                                @endif
                                @if($student->coursePart?->year_label)
                                    <div class="text-muted" style="font-size:10px;">{{ $student->coursePart->year_label }}</div>
                                @endif
                            </td>
                            <td style="white-space:nowrap;">{{ $student->session?->name ?? '—' }}</td>
                            <td>
                                @php
                                    $statusColor = match($student->status) {
                                        'active'   => 'bg-success-subtle text-success border border-success-subtle',
                                        'pending'  => 'bg-warning-subtle text-warning border border-warning-subtle',
                                        'inactive' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                        'detained' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        default    => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                    };
                                @endphp
                                <span class="badge {{ $statusColor }}">{{ ucfirst($student->status ?? 'pending') }}</span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center flex-wrap">
                                    <a href="{{ route($profileRoute ?? 'admissions.show', $student->id) }}"
                                       class="btn btn-primary btn-xs" title="Full Profile" style="padding:2px 7px;font-size:11px;">
                                        <i class="bi bi-person-badge"></i>
                                    </a>
                                    @if(!empty($showWalletAction) && !empty($walletRoute))
                                    <a href="{{ route($walletRoute, $student->id) }}"
                                       class="btn btn-outline-info btn-xs" title="Wallet" style="padding:2px 7px;font-size:11px;">
                                        <i class="bi bi-wallet2"></i>
                                    </a>
                                    @endif
                                    @if(!empty($showHistoryAction) && !empty($historyRoute))
                                    <a href="{{ route($historyRoute, $student->id) }}"
                                       class="btn btn-outline-secondary btn-xs" title="Fee History" style="padding:2px 7px;font-size:11px;">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                    @endif
                                    @if(!empty($showCollectFeeAction) && !empty($collectFeeRoute))
                                    <a href="{{ route($collectFeeRoute, ['student_id' => $student->id]) }}"
                                       class="btn btn-outline-success btn-xs" title="Collect Fee" style="padding:2px 7px;font-size:11px;">
                                        <i class="bi bi-cash-coin"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    @else
                    <tr>
                        <td colspan="14" class="text-center py-4 text-muted">
                            <i class="bi bi-search fs-1 d-block mb-2"></i>
                            Is keyword se koi student nahi mila.
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($students && method_exists($students, 'hasPages') && $students->hasPages())
<div class="mt-3">{{ $students->links('pagination::bootstrap-5') }}</div>
@endif
