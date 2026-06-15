<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard — {{ $student->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body{background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#1e293b;}
        .topbar{background:#2563EB;color:#fff;padding:12px 24px;display:flex;align-items:center;justify-content:space-between;}
        .topbar .brand{font-size:17px;font-weight:700;}
        .topbar .student-name{font-size:13px;opacity:.9;}
        .sidebar{background:#1e293b;min-height:100vh;width:220px;position:fixed;top:0;left:0;padding-top:64px;}
        .sidebar a{display:flex;align-items:center;gap:10px;padding:11px 20px;color:#94a3b8;text-decoration:none;font-size:14px;border-left:3px solid transparent;transition:all .15s;}
        .sidebar a:hover,.sidebar a.active{color:#fff;background:#ffffff10;border-left-color:#2563EB;}
        .sidebar a i{font-size:16px;width:18px;}
        .main{margin-left:220px;padding:80px 28px 28px;}
        .profile-card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;overflow:hidden;}
        .profile-header{background:linear-gradient(135deg,#2563EB,#1d4ed8);padding:28px 24px;color:#fff;display:flex;align-items:center;gap:20px;}
        .profile-avatar{width:80px;height:80px;border-radius:50%;border:3px solid #fff;object-fit:cover;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-size:30px;color:#2563EB;flex-shrink:0;}
        .badge-status{font-size:11px;padding:3px 10px;border-radius:20px;background:#ffffff30;color:#fff;font-weight:600;}
        .section-title{font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.06em;margin:20px 0 10px;}
        .info-row{display:flex;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:14px;}
        .info-row:last-child{border-bottom:none;}
        .info-label{color:#64748b;width:160px;flex-shrink:0;font-size:13px;}
        .info-value{font-weight:500;flex:1;}
        .subject-chip{display:inline-block;background:#eff6ff;color:#2563EB;border-radius:20px;padding:4px 12px;font-size:12px;margin:3px;}
        .tab-nav .nav-link{color:#64748b;border-radius:8px 8px 0 0;font-size:14px;}
        .tab-nav .nav-link.active{color:#2563EB;background:#fff;border-color:#e2e8f0 #e2e8f0 #fff;font-weight:600;}
        .stat-box{background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:18px 20px;text-align:center;}
        .stat-box .num{font-size:26px;font-weight:700;color:#2563EB;}
        .stat-box .lbl{font-size:12px;color:#64748b;margin-top:2px;}
        .notice-card{border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;margin-bottom:10px;background:#fff;transition:box-shadow .15s;}
        .notice-card:hover{box-shadow:0 2px 12px rgba(0,0,0,.07);}
        .notice-card.pinned{border-left:4px solid #f59e0b;}
        .notice-card.unread{background:#eff6ff;}
        .notice-type{font-size:11px;padding:2px 9px;border-radius:20px;font-weight:600;}
        .type-general{background:#f1f5f9;color:#475569;}
        .type-exam{background:#fef3c7;color:#92400e;}
        .type-fee{background:#dcfce7;color:#166534;}
        .type-urgent{background:#fee2e2;color:#991b1b;}
        .type-holiday{background:#dbeafe;color:#1e40af;}
        .type-event{background:#f3e8ff;color:#6b21a8;}
        .fee-invoice{border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;margin-bottom:12px;}
        .fee-invoice-head{background:#f8fafc;padding:12px 16px;display:flex;justify-content:space-between;align-items:center;font-size:13px;}
        .fee-items-table{font-size:13px;}
        .transport-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;}
        .transport-header{background:linear-gradient(135deg,#0f172a,#1e293b);color:#fff;padding:20px 24px;}
        .info-chip{display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;border-radius:8px;padding:8px 14px;font-size:13px;}
        @media(max-width:768px){.sidebar{display:none;}.main{margin-left:0;}}
    </style>
</head>
<body>

{{-- Top Bar --}}
<div class="topbar" style="position:fixed;top:0;left:0;right:0;z-index:100;">
    <div class="brand"><i class="bi bi-mortarboard-fill me-2"></i>Student Portal</div>
    <div class="d-flex align-items-center gap-3">
        <div class="student-name">
            <i class="bi bi-person-circle me-1"></i>{{ $student->name }}
            <span class="ms-2 opacity-75" style="font-size:11px;">{{ $student->student_uid }}</span>
        </div>
        <form method="POST" action="{{ route('student.logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm text-white" style="background:#ffffff20;border:1px solid #ffffff40;font-size:12px;border-radius:6px;">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </button>
        </form>
    </div>
</div>

{{-- Sidebar --}}
<div class="sidebar">
    <a href="{{ route('student.dashboard') }}" class="active"><i class="bi bi-grid-1x2"></i> Dashboard</a>
    <a href="#profile" data-tab="profile"><i class="bi bi-person-lines-fill"></i> My Profile</a>
    <a href="#academic" data-tab="academic"><i class="bi bi-book"></i> Academic</a>
    <a href="#fee" data-tab="fee"><i class="bi bi-receipt"></i> Fee Details</a>
    <a href="#notices" data-tab="notices"><i class="bi bi-megaphone"></i> Notices
        @php $unread = collect($notices ?? [])->filter(fn($n) => !in_array($n->id, $readNoticeIds ?? []))->count(); @endphp
        @if($unread > 0)<span class="badge bg-danger ms-1" style="font-size:10px;">{{ $unread }}</span>@endif
    </a>
    <a href="#transport" data-tab="transport"><i class="bi bi-bus-front"></i> Transport</a>
    <a href="{{ route('student.change-password') }}"><i class="bi bi-key"></i> Change Password</a>
</div>

{{-- Main Content --}}
<div class="main">

    @if(session('success'))
    <div class="alert alert-success alert-dismissible border-0 rounded-3 mb-4">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('info'))
    <div class="alert alert-info alert-dismissible border-0 rounded-3 mb-4">
        <i class="bi bi-info-circle me-2"></i>{{ session('info') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Stats row --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="stat-box">
                <div class="num">{{ $student->current_semester ?? '—' }}</div>
                <div class="lbl">Semester</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-box">
                <div class="num">{{ $student->subjects->count() }}</div>
                <div class="lbl">Subjects</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box">
                <div class="num" style="font-size:15px;">{{ $student->stream?->course?->name ?? '—' }}</div>
                <div class="lbl">Course</div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="stat-box" style="cursor:pointer;" onclick="switchTab('fee')">
                <div class="num {{ $totalDue > 0 ? 'text-danger' : 'text-success' }}" style="font-size:18px;">₹{{ number_format($totalDue, 0) }}</div>
                <div class="lbl">Fee Due</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-box" style="cursor:pointer;" onclick="switchTab('notices')">
                <div class="num" style="font-size:18px;">
                    {{ $notices->count() }}
                    @if($unread > 0)<span class="text-danger" style="font-size:13px;"> ({{ $unread }} new)</span>@endif
                </div>
                <div class="lbl">Notices</div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs tab-nav mb-0 flex-wrap" id="dashTabs">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#profile">
                <i class="bi bi-person me-1"></i>Profile
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#academic">
                <i class="bi bi-book me-1"></i>Academic
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#fee">
                <i class="bi bi-receipt me-1"></i>Fee Details
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#notices">
                <i class="bi bi-megaphone me-1"></i>Notices
                @if($unread > 0)<span class="badge bg-danger ms-1" style="font-size:10px;">{{ $unread }}</span>@endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#transport">
                <i class="bi bi-bus-front me-1"></i>Transport
            </a>
        </li>
    </ul>

    <div class="tab-content profile-card" style="border-radius:0 14px 14px 14px;">
        {{-- ── TAB 1: Profile ──────────────────────────────────────── --}}
        <div class="tab-pane fade show active p-0" id="profile">

            {{-- Header with photo --}}
            <div class="profile-header">
                <div class="profile-avatar">
                    @if($student->photo)
                        <img src="{{ asset('storage/' . $student->photo) }}"
                             style="width:80px;height:80px;border-radius:50%;object-fit:cover;" alt="Photo">
                    @else
                        <i class="bi bi-person-fill"></i>
                    @endif
                </div>
                <div>
                    <h5 class="mb-1 fw-bold">{{ $student->name }}</h5>
                    <div style="font-size:13px;opacity:.85;">
                        Student ID: <strong>{{ $student->student_uid }}</strong>
                    </div>
                    <div class="mt-2">
                        <span class="badge-status">{{ ucfirst($student->status ?? 'pending') }}</span>
                        @if($student->enrollment_no)
                            <span class="badge-status ms-2">Enrollment: {{ $student->enrollment_no }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-4">
                {{-- Personal Info --}}
                <div class="section-title">Personal Information</div>
                <div class="info-row"><span class="info-label">Full Name</span><span class="info-value">{{ $student->name }}</span></div>
                <div class="info-row"><span class="info-label">Date of Birth</span><span class="info-value">{{ $student->dob?->format('d M Y') ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Gender</span><span class="info-value">{{ ucfirst($student->gender ?? '—') }}</span></div>
                <div class="info-row"><span class="info-label">Mobile</span><span class="info-value">{{ $student->mobile ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Email</span><span class="info-value">{{ $student->email ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Aadhaar No.</span><span class="info-value">{{ $student->aadhar_no ? '****' . substr($student->aadhar_no, -4) : '—' }}</span></div>
                <div class="info-row"><span class="info-label">Category</span><span class="info-value">{{ strtoupper($student->category ?? '—') }}</span></div>
                <div class="info-row"><span class="info-label">Nationality</span><span class="info-value">{{ $student->nationality ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Religion</span><span class="info-value">{{ ucfirst($student->religion ?? '—') }}</span></div>

                {{-- Family Info --}}
                <div class="section-title">Family Information</div>
                <div class="info-row"><span class="info-label">Father's Name</span><span class="info-value">{{ $student->father_name ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Father's Mobile</span><span class="info-value">{{ $student->father_mobile ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Father's Occupation</span><span class="info-value">{{ $student->father_occupation ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Mother's Name</span><span class="info-value">{{ $student->mother_name ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Mother's Mobile</span><span class="info-value">{{ $student->mother_mobile ?? '—' }}</span></div>

                {{-- Address --}}
                <div class="section-title">Permanent Address</div>
                <div class="info-row">
                    <span class="info-label">Address</span>
                    <span class="info-value">
                        {{ collect([$student->perm_address, $student->perm_village, $student->perm_post, $student->perm_district, $student->perm_state, $student->perm_pincode])->filter()->implode(', ') ?: '—' }}
                    </span>
                </div>

                @if($student->comm_same_as_perm)
                <div class="info-row"><span class="info-label">Communication Address</span><span class="info-value text-muted fst-italic">Same as permanent address</span></div>
                @else
                <div class="section-title">Communication Address</div>
                <div class="info-row">
                    <span class="info-label">Address</span>
                    <span class="info-value">
                        {{ collect([$student->comm_address, $student->comm_city, $student->comm_district, $student->comm_state, $student->comm_pincode])->filter()->implode(', ') ?: '—' }}
                    </span>
                </div>
                @endif

                <div class="alert alert-light border mt-4 mb-0 rounded-3" style="font-size:13px;">
                    <i class="bi bi-info-circle text-primary me-2"></i>
                    Profile information can only be updated by the college administration. For any changes, contact your college office.
                </div>
            </div>
        </div>

        {{-- ── TAB 2: Academic Details ─────────────────────────────── --}}
        <div class="tab-pane fade" id="academic">
            <div class="p-4">

                <div class="section-title">Admission & Course Details</div>
                <div class="info-row"><span class="info-label">Institute</span><span class="info-value">{{ $student->institute?->name ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Academic Session</span><span class="info-value">{{ $student->session?->name ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Course</span><span class="info-value">{{ $student->stream?->course?->name ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Stream / Branch</span><span class="info-value">{{ $student->stream?->name ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Course Part / Year</span><span class="info-value">{{ $student->coursePart?->name ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Current Semester</span><span class="info-value">Semester {{ $student->current_semester }}</span></div>
                <div class="info-row"><span class="info-label">Admission Date</span><span class="info-value">{{ $student->admission_date?->format('d M Y') ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Admission Type</span><span class="info-value">{{ ucfirst($student->admission_type ?? '—') }}</span></div>
                <div class="info-row"><span class="info-label">Student Type</span><span class="info-value">{{ ucfirst($student->student_type ?? '—') }}</span></div>

                {{-- ID Numbers --}}
                <div class="section-title">Academic Identifiers</div>
                <div class="info-row"><span class="info-label">Student UID</span><span class="info-value">{{ $student->student_uid }}</span></div>
                @if($student->enrollment_no)
                <div class="info-row"><span class="info-label">Enrollment No.</span><span class="info-value">{{ $student->enrollment_no }}</span></div>
                @endif
                @if($student->roll_no)
                <div class="info-row"><span class="info-label">Roll No.</span><span class="info-value">{{ $student->roll_no }}</span></div>
                @endif
                @if($student->sr_no)
                <div class="info-row"><span class="info-label">SR No.</span><span class="info-value">{{ $student->sr_no }}</span></div>
                @endif
                @if($student->exam_form_no)
                <div class="info-row"><span class="info-label">Exam Form No.</span><span class="info-value">{{ $student->exam_form_no }}</span></div>
                @endif

                {{-- Subjects --}}
                <div class="section-title">Enrolled Subjects ({{ $student->subjects->count() }})</div>
                @if($student->subjects->count())
                    <div class="mb-3">
                        @foreach($student->subjects as $subject)
                            <span class="subject-chip">
                                @if($subject->code)<span style="opacity:.7;font-size:10px;">{{ $subject->code }} — </span>@endif
                                {{ $subject->name }}
                                @if($subject->pivot->subject_role && $subject->pivot->subject_role !== 'regular')
                                    <span style="font-size:10px;opacity:.7;"> ({{ ucfirst($subject->pivot->subject_role) }})</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted small">No subjects assigned yet.</p>
                @endif

                {{-- Education History --}}
                @if($student->educationDetails->count())
                <div class="section-title">Previous Education</div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" style="font-size:13px;">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th>Exam</th>
                                <th>Institute / Board</th>
                                <th>Year</th>
                                <th>Marks</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($student->educationDetails as $edu)
                            <tr>
                                <td>{{ $edu->exam_name }}</td>
                                <td>{{ $edu->institute_name ?? '—' }}<br><small class="text-muted">{{ $edu->board_university ?? '' }}</small></td>
                                <td>{{ $edu->passing_year ?? '—' }}</td>
                                <td>{{ $edu->obtained_marks ?? '—' }} / {{ $edu->max_marks ?? '—' }}</td>
                                <td>{{ $edu->percentage ? number_format($edu->percentage, 1) . '%' : '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- Scholarship --}}
                @if($student->has_scholarship)
                <div class="section-title">Scholarship</div>
                <div class="info-row"><span class="info-label">Scholarship Name</span><span class="info-value">{{ $student->scholarship_name ?? '—' }}</span></div>
                <div class="info-row"><span class="info-label">Type</span><span class="info-value">{{ ucfirst($student->scholarship_type ?? '—') }}</span></div>
                <div class="info-row"><span class="info-label">Amount</span><span class="info-value">₹{{ number_format($student->scholarship_amount ?? 0, 2) }}</span></div>
                @endif

            </div>
        </div>

        {{-- ── TAB 3: Fee Details ──────────────────────────────────── --}}
        <div class="tab-pane fade" id="fee">
            <div class="p-4">

                {{-- Summary bar --}}
                <div class="row g-3 mb-4">
                    <div class="col-4">
                        <div class="text-center p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                            <div style="font-size:20px;font-weight:700;color:#1e293b;">₹{{ number_format($totalFee, 2) }}</div>
                            <div style="font-size:12px;color:#64748b;">Total Fee</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-3 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                            <div style="font-size:20px;font-weight:700;color:#16a34a;">₹{{ number_format($totalPaid, 2) }}</div>
                            <div style="font-size:12px;color:#64748b;">Paid</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-3 rounded-3" style="background:{{ $totalDue > 0 ? '#fef2f2' : '#f0fdf4' }};border:1px solid {{ $totalDue > 0 ? '#fecaca' : '#bbf7d0' }};">
                            <div style="font-size:20px;font-weight:700;color:{{ $totalDue > 0 ? '#dc2626' : '#16a34a' }};">₹{{ number_format(abs($totalDue), 2) }}</div>
                            <div style="font-size:12px;color:#64748b;">{{ $totalDue > 0 ? 'Due' : 'Advance' }}</div>
                        </div>
                    </div>
                </div>

                @if($invoices->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-receipt" style="font-size:40px;opacity:.3;"></i>
                        <p class="mt-3">No fee records found for this session.</p>
                    </div>
                @else
                    @foreach($invoices as $invoice)
                    <div class="fee-invoice">
                        <div class="fee-invoice-head">
                            <div>
                                <span class="fw-semibold">#{{ $invoice->invoice_no }}</span>
                                <span class="text-muted ms-2" style="font-size:12px;">{{ $invoice->payment_date?->format('d M Y') ?? '—' }}</span>
                                @if($invoice->payment_mode)
                                    <span class="badge ms-2" style="background:#e0e7ff;color:#3730a3;font-size:10px;">{{ ucfirst($invoice->payment_mode) }}</span>
                                @endif
                            </div>
                            <div class="text-end">
                                <span class="fw-bold" style="color:#16a34a;">₹{{ number_format($invoice->paid_amount, 2) }}</span>
                                @if($invoice->total_amount != $invoice->paid_amount)
                                    <span class="text-muted" style="font-size:12px;"> / ₹{{ number_format($invoice->total_amount, 2) }}</span>
                                @endif
                            </div>
                        </div>
                        @if($invoice->items->count())
                        <div class="px-3 pb-3 pt-2">
                            <table class="table table-sm fee-items-table mb-0">
                                <thead>
                                    <tr style="font-size:11px;color:#94a3b8;">
                                        <th class="border-0 ps-0">Fee Head</th>
                                        <th class="border-0 text-end">Amount</th>
                                        @if($invoice->items->sum('discount') > 0)<th class="border-0 text-end">Discount</th>@endif
                                        <th class="border-0 text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->items as $item)
                                    <tr>
                                        <td class="ps-0 border-0">{{ $item->fee_name }}</td>
                                        <td class="text-end border-0">₹{{ number_format($item->amount, 2) }}</td>
                                        @if($invoice->items->sum('discount') > 0)<td class="text-end border-0 text-success">{{ $item->discount > 0 ? '-₹'.number_format($item->discount,2) : '—' }}</td>@endif
                                        <td class="text-end border-0 fw-semibold">₹{{ number_format($item->total_fee, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                        @if($invoice->remarks)
                        <div class="px-3 pb-2" style="font-size:12px;color:#64748b;"><i class="bi bi-chat-left-text me-1"></i>{{ $invoice->remarks }}</div>
                        @endif
                    </div>
                    @endforeach
                @endif

            </div>
        </div>

        {{-- ── TAB 4: Notices ──────────────────────────────────────── --}}
        <div class="tab-pane fade" id="notices">
            <div class="p-4">

                @if($notices->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-megaphone" style="font-size:40px;opacity:.3;"></i>
                        <p class="mt-3">No notices available.</p>
                    </div>
                @else
                    @foreach($notices as $notice)
                    @php
                        $isRead   = in_array($notice->id, $readNoticeIds);
                        $typeMap  = ['general'=>'type-general','exam'=>'type-exam','fee'=>'type-fee','urgent'=>'type-urgent','holiday'=>'type-holiday','event'=>'type-event'];
                        $typeCls  = $typeMap[$notice->notice_type] ?? 'type-general';
                    @endphp
                    <div class="notice-card {{ $notice->is_pinned ? 'pinned' : '' }} {{ !$isRead ? 'unread' : '' }}"
                         data-notice-id="{{ $notice->id }}"
                         onclick="markRead({{ $notice->id }}, this)">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                @if($notice->is_pinned)
                                    <i class="bi bi-pin-angle-fill text-warning" title="Pinned"></i>
                                @endif
                                <span class="notice-type {{ $typeCls }}">{{ ucfirst($notice->notice_type) }}</span>
                                @if(!$isRead)
                                    <span class="badge" style="background:#dbeafe;color:#1d4ed8;font-size:10px;">New</span>
                                @endif
                            </div>
                            <span style="font-size:12px;color:#94a3b8;white-space:nowrap;">{{ $notice->notice_date?->format('d M Y') }}</span>
                        </div>
                        <div class="fw-semibold mb-1" style="font-size:14px;">{{ $notice->title }}</div>
                        @if($notice->body)
                            <div style="font-size:13px;color:#475569;line-height:1.5;">{{ Str::limit($notice->body, 200) }}</div>
                        @endif
                        @if($notice->attachment)
                            <div class="mt-2">
                                <a href="{{ asset('storage/' . $notice->attachment) }}" target="_blank"
                                   class="text-decoration-none" style="font-size:12px;color:#2563EB;"
                                   onclick="event.stopPropagation()">
                                    <i class="bi bi-paperclip me-1"></i>View Attachment
                                </a>
                            </div>
                        @endif
                    </div>
                    @endforeach
                @endif

            </div>
        </div>

        {{-- ── TAB 5: Transport ────────────────────────────────────── --}}
        <div class="tab-pane fade" id="transport">
            <div class="p-4">

                @if(!$transport)
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-bus-front" style="font-size:40px;opacity:.3;"></i>
                        <p class="mt-3">No transport allocation found for this session.</p>
                    </div>
                @else
                <div class="transport-card mb-4">
                    <div class="transport-header">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:48px;height:48px;border-radius:50%;background:#ffffff20;display:flex;align-items:center;justify-content:center;font-size:22px;">
                                <i class="bi bi-bus-front-fill"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $transport->route?->name ?? 'Route' }}</h6>
                                <div style="font-size:12px;opacity:.7;">{{ $transport->route?->start_point }} → {{ $transport->route?->end_point }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-4">
                                <div class="text-center p-3 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                                    <div style="font-size:18px;font-weight:700;">₹{{ number_format($transport->charged_amount ?: $transport->fee_amount, 2) }}</div>
                                    <div style="font-size:12px;color:#64748b;">Total Fee</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center p-3 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                                    <div style="font-size:18px;font-weight:700;color:#16a34a;">₹{{ number_format($transport->paid_amount, 2) }}</div>
                                    <div style="font-size:12px;color:#64748b;">Paid</div>
                                </div>
                            </div>
                            <div class="col-4">
                                @php $tBal = $transport->balance; @endphp
                                <div class="text-center p-3 rounded-3" style="background:{{ $tBal > 0 ? '#fef2f2' : '#f0fdf4' }};border:1px solid {{ $tBal > 0 ? '#fecaca' : '#bbf7d0' }};">
                                    <div style="font-size:18px;font-weight:700;color:{{ $tBal > 0 ? '#dc2626' : '#16a34a' }};">₹{{ number_format(abs($tBal), 2) }}</div>
                                    <div style="font-size:12px;color:#64748b;">{{ $tBal > 0 ? 'Due' : 'Advance' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="section-title">Stop Details</div>
                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <i class="bi bi-geo-alt-fill text-danger"></i>
                                    <div>
                                        <div style="font-size:11px;color:#64748b;">Your Stop</div>
                                        <div class="fw-semibold" style="font-size:13px;">{{ $transport->stop?->stop_name ?? '—' }}</div>
                                    </div>
                                </div>
                            </div>
                            @if($transport->stop?->landmark)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <i class="bi bi-building text-muted"></i>
                                    <div>
                                        <div style="font-size:11px;color:#64748b;">Landmark</div>
                                        <div class="fw-semibold" style="font-size:13px;">{{ $transport->stop->landmark }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($transport->stop?->pickup_time)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <i class="bi bi-sunrise text-warning"></i>
                                    <div>
                                        <div style="font-size:11px;color:#64748b;">Morning Pickup</div>
                                        <div class="fw-semibold" style="font-size:13px;">{{ \Carbon\Carbon::parse($transport->stop->pickup_time)->format('h:i A') }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($transport->stop?->drop_time)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <i class="bi bi-sunset text-primary"></i>
                                    <div>
                                        <div style="font-size:11px;color:#64748b;">Evening Drop</div>
                                        <div class="fw-semibold" style="font-size:13px;">{{ \Carbon\Carbon::parse($transport->stop->drop_time)->format('h:i A') }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>

                        @if($transport->vehicle || $transport->driver)
                        <div class="section-title">Vehicle & Driver</div>
                        <div class="row g-2">
                            @if($transport->vehicle)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <i class="bi bi-truck-front text-primary"></i>
                                    <div>
                                        <div style="font-size:11px;color:#64748b;">Vehicle No.</div>
                                        <div class="fw-semibold" style="font-size:13px;">{{ $transport->vehicle->vehicle_no }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($transport->driver)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <i class="bi bi-person-badge text-success"></i>
                                    <div>
                                        <div style="font-size:11px;color:#64748b;">Driver</div>
                                        <div class="fw-semibold" style="font-size:13px;">{{ $transport->driver->name }}</div>
                                    </div>
                                </div>
                            </div>
                            @if($transport->driver->mobile)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <i class="bi bi-telephone-fill text-success"></i>
                                    <div>
                                        <div style="font-size:11px;color:#64748b;">Driver Mobile</div>
                                        <a href="tel:{{ $transport->driver->mobile }}" class="fw-semibold text-decoration-none" style="font-size:13px;">{{ $transport->driver->mobile }}</a>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @if($transport->driver->helper_name)
                            <div class="col-sm-6">
                                <div class="info-chip">
                                    <i class="bi bi-person text-muted"></i>
                                    <div>
                                        <div style="font-size:11px;color:#64748b;">Helper</div>
                                        <div class="fw-semibold" style="font-size:13px;">{{ $transport->driver->helper_name }}
                                            @if($transport->driver->helper_mobile)
                                                <a href="tel:{{ $transport->driver->helper_mobile }}" class="ms-1 text-success text-decoration-none" style="font-size:11px;">({{ $transport->driver->helper_mobile }})</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @endif
                        </div>
                        @endif

                        @if($transport->start_date || $transport->end_date)
                        <div class="section-title">Validity</div>
                        <div class="info-row">
                            <span class="info-label">Start Date</span>
                            <span class="info-value">{{ $transport->start_date?->format('d M Y') ?? '—' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">End Date</span>
                            <span class="info-value">{{ $transport->end_date?->format('d M Y') ?? 'Ongoing' }}</span>
                        </div>
                        @endif

                    </div>
                </div>
                @endif

            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const MARK_READ_URL = '{{ route("student.notices.read", ["id" => "__ID__"]) }}';
const CSRF_TOKEN    = '{{ csrf_token() }}';

function switchTab(id) {
    const el = document.querySelector(`a[href="#${id}"]`);
    if (el) new bootstrap.Tab(el).show();
}

function markRead(noticeId, card) {
    if (card.classList.contains('unread')) {
        card.classList.remove('unread');
        const badge = card.querySelector('.badge');
        if (badge) badge.remove();
        fetch(MARK_READ_URL.replace('__ID__', noticeId), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' }
        });
    }
}

// Sidebar tab links
document.querySelectorAll('.sidebar a[data-tab]').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        switchTab(link.dataset.tab);
    });
});

// Restore active tab from hash
const hash = window.location.hash;
if (hash) {
    const tab = document.querySelector(`#dashTabs a[href="${hash}"]`);
    if (tab) new bootstrap.Tab(tab).show();
}
// Update hash on tab change
document.querySelectorAll('#dashTabs a[data-bs-toggle="tab"]').forEach(el => {
    el.addEventListener('shown.bs.tab', e => {
        history.replaceState(null, '', e.target.getAttribute('href'));
    });
});
</script>
</body>
</html>
