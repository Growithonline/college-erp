<div class="card border-0 bg-light rounded-3 p-3 mb-4">
    <div class="fw-semibold small mb-2 text-dark">
        <i class="bi bi-percent me-1 text-warning"></i>Fee Discount Limit
    </div>
    <div class="row g-2 align-items-center mb-3">
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Default Max Discount (%)</label>
            <div class="input-group input-group-sm">
                <input type="number" name="max_discount_percent" min="0" max="100"
                       value="{{ old('max_discount_percent', $staffMember->max_discount_percent ?? 100) }}"
                       class="form-control @error('max_discount_percent') is-invalid @enderror"
                       placeholder="0-100">
                <span class="input-group-text">%</span>
            </div>
            @error('max_discount_percent')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-8">
            <small class="text-muted d-block mt-3">
                100% = no limit (default). Jis fee item ka specific permission nahi set, uspe yahi limit apply hogi.
            </small>
        </div>
    </div>

    <div class="row g-2 align-items-center mb-1">
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Max Custom Fee Amount (₹)</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text">₹</span>
                <input type="number" name="max_custom_fee_amount" min="0" step="0.01"
                       value="{{ old('max_custom_fee_amount', $staffMember->max_custom_fee_amount ?? '') }}"
                       class="form-control @error('max_custom_fee_amount') is-invalid @enderror"
                       placeholder="Blank = no limit">
            </div>
            @error('max_custom_fee_amount')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-8">
            <small class="text-muted d-block mt-3">
                Custom fee items above this amount are held for admin approval instead of being collected immediately. Blank = no limit.
            </small>
        </div>
    </div>

    @if(isset($feeTypes) && $feeTypes->isNotEmpty())
    <style>
        .discount-tbl tbody tr:has(input[type=checkbox]:checked) {
            background-color: #d1fae5 !important;
        }
        .discount-tbl tbody tr:has(input[type=checkbox]:checked) td:first-child {
            font-weight: 600;
            color: #065f46;
        }
        .discount-tbl tbody tr:has(input[type=checkbox]:not(:checked)) td:first-child {
            color: #9ca3af;
        }
    </style>
    <div class="border-top pt-3">
        <div class="small fw-semibold text-dark mb-2">Per Fee Item - Discount Allow/Block</div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 align-middle discount-tbl" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th>Fee Item</th>
                        <th class="text-center" style="width:160px;">Discount Allowed?</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($feeTypes as $ft)
                @php
                    $isAllowed = old("fee_discount_allowed.{$ft->id}",
                        isset($feeDiscountPermissions) && in_array($ft->id, $feeDiscountPermissions->toArray()) ? '1' : null
                    );
                @endphp
                <tr>
                    <td>{{ $ft->name }}</td>
                    <td class="text-center">
                        <div class="form-check form-switch d-flex align-items-center justify-content-center gap-2 mb-0">
                            <input type="checkbox"
                                   class="form-check-input"
                                   role="switch"
                                   name="fee_discount_allowed[{{ $ft->id }}]"
                                   value="1"
                                   {{ $isAllowed ? 'checked' : '' }}>
                            <span class="small {{ $isAllowed ? 'text-success fw-semibold' : 'text-danger' }}">
                                {{ $isAllowed ? 'Yes' : 'No' }}
                            </span>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <small class="text-muted d-block mt-2">
            Toggle ON = us fee item pe discount allowed (global default % tak). Toggle OFF = discount bilkul nahi.
            Agar koi bhi toggle set nahi kiya to global default sabhi items pe apply hogi.
        </small>
    </div>
    @endif
<script>
document.querySelectorAll('.discount-tbl input[type=checkbox]').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var label = this.closest('.form-check').querySelector('span');
        if (!label) return;
        label.textContent = this.checked ? 'Yes' : 'No';
        label.className = 'small ' + (this.checked ? 'text-success fw-semibold' : 'text-danger');
    });
});
</script>
</div>
