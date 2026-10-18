{{--
    Shared student navigation buttons: Profile / Wallet / Fee History / Print Balance / Print Statement / Back.
    Used on Collect Fee, Student Wallet, Fee History and Student Profile so the same buttons appear everywhere.
    Props: $student, $current ('collect'|'profile'|'wallet'|'history'), $backUrl, $canViewWallet, canViewHistory (default true), smartBack (true = Back returns to the previous page; $backUrl is the fallback)
--}}
@php
    $_navStaff   = auth()->guard('staff')->check();
    $_navCenter  = auth()->guard('center')->check();
    $_navPartner = auth()->guard('partner')->check();
    $_navProfile = $_navStaff ? 'staff.admissions.show'     : ($_navCenter ? 'center.students.show'       : ($_navPartner ? 'partner.students.show'       : 'admissions.show'));
    $_navWallet  = $_navStaff ? 'staff.fee.wallet.student'  : ($_navCenter ? 'center.fee.wallet.student'  : ($_navPartner ? 'partner.fee.wallet.student'  : 'fee.wallet.student'));
    $_navHistory = $_navStaff ? 'staff.fee.student-history' : ($_navCenter ? 'center.fee.student-history' : ($_navPartner ? 'partner.fee.student-history' : 'fee.student-history'));
    $_navStmt    = $_navStaff ? 'staff.statement'           : (($_navCenter || $_navPartner) ? null : 'statement');
    $_navCurrent = $current ?? 'collect';
    $_navCanWallet = $canViewWallet ?? true;
    $_navCanHistory = $canViewHistory ?? true;
    $_navSid     = $student->id;
@endphp
<a href="{{ route($_navProfile, $_navSid) }}" class="btn btn-sm {{ $_navCurrent === 'profile' ? 'btn-primary' : 'btn-outline-primary' }}">
    <i class="bi bi-person me-1"></i> Profile
</a>
@if($_navCanWallet)
<a href="{{ route($_navWallet, $_navSid) }}" class="btn btn-sm {{ $_navCurrent === 'wallet' ? 'btn-info text-white' : 'btn-outline-info' }}">
    <i class="bi bi-wallet2 me-1"></i> Wallet
</a>
@endif
@if($_navCanHistory)
<a href="{{ route($_navHistory, $_navSid) }}" class="btn btn-sm {{ $_navCurrent === 'history' ? 'btn-secondary' : 'btn-outline-secondary' }}">
    <i class="bi bi-receipt me-1"></i> Fee History
</a>
@endif
@if($_navStmt)
<a href="{{ route($_navStmt . '.balance', ['student_id' => $_navSid, 'print' => 'thermal']) }}" target="_blank" class="btn btn-outline-success btn-sm">
    <i class="bi bi-printer me-1"></i> Print Balance
</a>
<a href="{{ route($_navStmt . '.fee-record', ['student_id' => $_navSid, 'print' => 'thermal']) }}" target="_blank" class="btn btn-outline-warning btn-sm">
    <i class="bi bi-printer me-1"></i> Print Statement
</a>
@endif
<a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm"
   @if($smartBack ?? false) onclick="try { if (document.referrer && new URL(document.referrer).origin === location.origin && history.length > 1) { history.back(); return false; } } catch (e) {}" @endif>
    <i class="bi bi-arrow-left me-1"></i> Back
</a>
