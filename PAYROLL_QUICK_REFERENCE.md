# PAYROLL MODULE - QUICK REFERENCE

## 🔗 URL Endpoints

### ATTENDANCE ROUTES

#### Daily Attendance
```
GET  /payroll/attendance/daily
     View daily attendance register
     Query params: ?date=YYYY-MM-DD&category=Teaching
     
POST /payroll/attendance/mark
     Mark attendance for one staff
     Body: {staff_id, date, status, in_time?, out_time?, late_minutes?, overtime_hours?, remarks?}
     
POST /payroll/attendance/bulk-mark
     Mark attendance for multiple staff at once
     Body: {date, staff_ids: [], status}
```

#### Monthly Summary
```
GET  /payroll/attendance/monthly
     View monthly attendance summary
     Query params: ?year=2026&month=5&category=Teaching&staff_id=15
     
POST /payroll/attendance/lock-month
     Lock month (prevents editing)
     Body: {year, month, reason?, remarks?}
     
POST /payroll/attendance/unlock-month
     Unlock month (allows editing again)
     Body: {year, month}
```

---

### PAYROLL ROUTES

#### Salary Draft
```
POST /payroll/generate-draft
     Generate salary draft from attendance
     Body: {year, month, category?}
     Response: {success, count}
     
GET  /payroll/draft
     View salary draft for review
     Query params: ?year=2026&month=5&category=Teaching
```

#### Salary Management
```
POST /payroll/approve/{salaryRecordId}
     Approve salary record
     
POST /payroll/mark-paid/{salaryRecordId}
     Mark salary as paid (creates journal)
     Body: {payment_date, payment_mode, remarks?}
     
POST /payroll/reverse/{salaryRecordId}
     Reverse payment
     Body: {reason}
     
GET  /payroll/summary
     View payroll summary report
     Query params: ?year=2026&month=5&category=Teaching
```

---

## 📊 API Response Examples

### Mark Attendance - Success
```json
{
  "success": true,
  "message": "Attendance marked successfully",
  "data": {
    "id": 142,
    "institute_id": 1,
    "staff_member_id": 15,
    "attendance_date": "2026-05-03",
    "status": "Present",
    "in_time": "09:00:00",
    "out_time": "17:30:00",
    "late_minutes": 5,
    "overtime_hours": 1.5,
    "marked_by": 8,
    "created_at": "2026-05-03T10:30:00Z"
  }
}
```

### Bulk Mark - Success
```json
{
  "success": true,
  "message": "Attendance marked for 15 staff members",
  "count": 15
}
```

### Generate Draft - Success
```json
{
  "success": true,
  "message": "Salary draft generated for 45 staff members",
  "count": 45
}
```

### Mark Paid - Success
```json
{
  "success": true,
  "message": "Salary marked as paid",
  "journal_entry": 2847
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description here"
}
```

---

## 🔐 Request Headers

All POST requests require:
```
Content-Type: application/json
X-CSRF-TOKEN: {csrf_token}
Accept: application/json
```

Get CSRF token from:
```html
<meta name="csrf-token" content="{token}">
```

---

## 📋 Attendance Status Values

```
"Present"           - Normal working day
"Absent"            - Not present (salary impact: no pay)
"Half Day"          - Half day (counts as 0.5 day)
"Paid Leave"        - Leave with pay (no salary impact)
"Unpaid Leave"      - Leave without pay (salary deducted)
"Holiday"           - Public holiday (paid)
"Week Off"          - Scheduled off day (paid)
```

---

## 💰 Payment Modes

```
"cash"              - Cash payment
"bank"              - Bank transfer
"check"             - Cheque payment
```

---

## 📊 Salary Record Status

```
"draft"             - Initial state, not yet approved
"approved"          - Reviewed and approved by authority
"paid"              - Payment processed and recorded
"reversed"          - Payment reversed/cancelled
```

---

## 🎯 Query Parameter Examples

### Daily Attendance
```
/payroll/attendance/daily?date=2026-05-03&category=Teaching
/payroll/attendance/daily  (uses today's date)
```

### Monthly Summary
```
/payroll/attendance/monthly?year=2026&month=5
/payroll/attendance/monthly?year=2026&month=5&category=Teaching
/payroll/attendance/monthly?year=2026&month=5&staff_id=15  (detail view)
```

### Payroll Draft
```
/payroll/draft?year=2026&month=5
/payroll/draft?year=2026&month=5&category=Teaching
```

### Summary Report
```
/payroll/summary?year=2026&month=5
/payroll/summary?year=2026&month=5&category=Office
```

---

## 🔢 Staff Categories

```
"Teaching"          - Teaching staff (faculty)
"Office"            - Office staff (administrative)
"Non-Teaching"      - Support staff (non-teaching)
"Guest"             - Guest/visiting staff (future)
```

---

## 💼 Payroll Types

```
"monthly"           - Fixed monthly salary
"daily"             - Hourly/daily wage worker
```

---

## 📌 Common Request Body Examples

### Mark Single Attendance
```json
{
  "staff_id": 15,
  "date": "2026-05-03",
  "status": "Present",
  "in_time": "09:00",
  "out_time": "17:30",
  "late_minutes": 5,
  "overtime_hours": 1.5,
  "remarks": "Normal working day"
}
```

### Bulk Mark Attendance
```json
{
  "date": "2026-05-03",
  "staff_ids": [15, 16, 17, 18, 19],
  "status": "Present"
}
```

### Generate Salary Draft
```json
{
  "year": 2026,
  "month": 5,
  "category": "Teaching"
}
```

### Mark Paid
```json
{
  "payment_date": "2026-05-05",
  "payment_mode": "bank",
  "remarks": "Monthly salary via bank transfer"
}
```

### Lock Month
```json
{
  "year": 2026,
  "month": 5,
  "reason": "month_closed",
  "remarks": "Month finalized for payroll processing"
}
```

### Reverse Payment
```json
{
  "reason": "Incorrect salary amount, need recalculation"
}
```

---

## 🎨 View Routes (Browser)

```
/payroll/attendance/daily           Daily attendance register
/payroll/attendance/monthly         Monthly attendance summary
/payroll/draft                      Salary draft review
/payroll/summary                    Payroll summary report
```

---

## ⚙️ Service Methods Quick Reference

### AttendanceService

```php
AttendanceService::markAttendance(
  $staffId, $date, $status,
  $inTime?, $outTime?, $lateMinutes?, 
  $overtimeHours?, $remarks?, $markedBy?
)

AttendanceService::bulkMarkAttendance(
  $instituteId, $date, $staffIds, $status, $markedBy?
)

AttendanceService::getAttendanceForDate(
  $instituteId, $date, $category?
)

AttendanceService::getMonthlyAttendanceSummary(
  $instituteId, $staffId, $year, $month
)

AttendanceService::getCategoryMonthlyAttendance(
  $instituteId, $category, $year, $month
)

AttendanceService::lockMonth(
  $instituteId, $year, $month, $reason?, $lockedBy?, $remarks?
)

AttendanceService::unlockMonth($instituteId, $year, $month)

AttendanceService::isMonthLocked($instituteId, $year, $month)

AttendanceService::getActiveStaff($instituteId, $category?)
```

### PayrollService

```php
PayrollService::generateSalaryDraft(
  $instituteId, $year, $month, $category?
)

PayrollService::calculateSalary(
  $staff, $attendance, $year, $month
)

PayrollService::getSalaryDraft(
  $instituteId, $year, $month, $category?
)

PayrollService::approveSalary($salaryRecord)

PayrollService::markSalaryPaid(
  $salaryRecord, $paymentDate?, $paymentMode?, $remarks?
)

PayrollService::reverseSalary($salaryRecord, $reason?)

PayrollService::getPayrollSummary(
  $instituteId, $year, $month, $category?
)
```

---

## 🔄 Data Flow Sequence

```
1. ATTENDANCE MARKING
   Input:  Staff ID, Date, Status
   Output: StaffAttendance record
   
2. ATTENDANCE LOCKING
   Input:  Year, Month
   Output: AttendanceLockRecord
   
3. SALARY CALCULATION
   Input:  Monthly attendance summary
   Calc:   Basic - Unpaid + Overtime
   Output: SalaryRecord (draft)
   
4. APPROVAL
   Input:  Confirm salary correct
   Output: SalaryRecord (approved)
   
5. PAYMENT
   Input:  Payment date, mode
   Calc:   Journal entry auto-created
   Output: SalaryRecord (paid) + JournalEntry
   
6. REVERSAL (if needed)
   Input:  Reason for reversal
   Calc:   Reverse journal entry
   Output: SalaryRecord (reversed) + Reverse JournalEntry
```

---

## 🗂️ File Structure Quick Reference

```
Controllers:
  AttendanceController.php    - Daily/monthly attendance
  PayrollController.php       - Salary & payment processing

Services:
  AttendanceService.php       - Attendance business logic
  PayrollService.php          - Salary calculations

Models:
  StaffAttendance             - Daily attendance records
  AttendanceLockRecord        - Month locks
  SalaryRecord                - Existing, used for salary
  StaffMember                 - Enhanced with payroll fields

Views:
  attendance/daily.blade.php        - Daily register
  attendance/monthly.blade.php      - Monthly summary
  attendance/monthly-detail.blade.php - Staff detail
  payroll/draft.blade.php          - Draft review
  payroll/summary.blade.php        - Report

Routes:
  routes/web.php              - All endpoints defined

Migrations:
  2026_05_03_000001_*.php     - Staff table enhancements
  2026_05_03_000002_*.php     - Staff attendance table
  2026_05_03_000003_*.php     - Lock records table
```

---

## ✅ Common Tasks

### Task: Check if month is locked
```php
$isLocked = AttendanceService::isMonthLocked($instituteId, 2026, 5);
```

### Task: Get this month's attendance summary for a staff
```php
$summary = AttendanceService::getMonthlyAttendanceSummary(
  $instituteId, $staffId, now()->year, now()->month
);
```

### Task: Generate draft for entire institute
```php
$records = PayrollService::generateSalaryDraft(
  $instituteId, 2026, 5  // No category filter
);
```

### Task: Get all approved but unpaid salaries
```php
$records = SalaryRecord::where('status', 'approved')->get();
```

---

## 🚨 Important Notes

⚠️ Month must be locked before generating salary draft  
⚠️ Cannot mark attendance before staff joining_date  
⚠️ Payment posting auto-creates journal entry  
⚠️ Reversal reverses the journal entry too  
⚠️ Unpaid Leave reduces salary; Paid Leave does not  
⚠️ All timestamps in UTC (PHP auto-converts to app timezone)  

---

**Saved:** May 3, 2026  
**Version:** 1.0  
**Status:** Production Ready ✅
