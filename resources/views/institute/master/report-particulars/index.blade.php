@extends('institute.layout')
@section('title','Report Particulars')
@section('breadcrumb','Master / Daily Register / Report Particulars')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h4 class="mb-0 fw-bold">Report Particulars</h4><small class="text-muted">Rows shown on the Daily Register report — courses, fee types, income &amp; expense categories</small></div>
    <a href="{{ route('master.report-particulars.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Particular</a>
</div>

@if($particulars->isEmpty())
<div class="card border-0 shadow-sm text-center py-5">
    <div class="card-body">
        <i class="bi bi-list-columns" style="font-size:3rem;color:#94a3b8;"></i>
        <h5 class="mt-3 text-muted">No Report Particulars Yet</h5>
        <a href="{{ route('master.report-particulars.create') }}" class="btn btn-primary mt-2"><i class="bi bi-plus-lg me-1"></i>Add First Particular</a>
    </div>
</div>
@else
@foreach(['income' => 'Income Rows', 'expense' => 'Expense Rows'] as $section => $label)
@if($particulars->has($section))
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-semibold">{{ $label }}</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Name</th><th>Source</th><th>Reference</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach($particulars[$section] as $i => $p)
                <tr>
                    <td class="text-muted small">{{ $i+1 }}</td>
                    <td class="fw-semibold">{{ $p->name }}</td>
                    <td><span class="badge bg-info-subtle text-info border border-info-subtle">{{ $sourceLabels[$p->source_type] ?? $p->source_type }}</span></td>
                    <td class="text-muted small">
                        @if($p->course_id)
                            {{ $p->course?->name }} — Year {{ $p->year_number }}
                        @elseif($p->fee_type_id)
                            {{ $p->feeType?->name }}
                        @elseif($p->item_type)
                            {{ ucfirst($p->item_type) }} (item type)
                        @elseif($p->income_category_id)
                            {{ $p->incomeCategory?->name }}
                        @elseif($p->expense_category_l1_id)
                            {{ $p->expenseCategoryL1?->name }}
                        @elseif($p->salary_scope)
                            {{ ucfirst(str_replace('_',' ', $p->salary_scope)) }}
                        @elseif($p->source_type === 'expense')
                            Uncategorized
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('master.report-particulars.toggle', $p) }}">@csrf
                            <button class="btn btn-sm {{ $p->is_active ? 'btn-success' : 'btn-secondary' }}">
                                <i class="bi bi-{{ $p->is_active ? 'check-circle' : 'x-circle' }}"></i>
                                {{ $p->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('master.report-particulars.edit', $p) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
                            @if(!$p->is_system)
                            <form method="POST" action="{{ route('master.report-particulars.destroy', $p) }}" onsubmit="return confirm('Delete?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endforeach
@endif
@endsection
