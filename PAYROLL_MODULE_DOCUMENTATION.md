# Staff Attendance & Payroll Module

## Overview
This module implements a complete staff attendance tracking and payroll management system with 4 phases:
- **Phase 1**: Staff category setup + Daily attendance marking + Monthly summaries
- **Phase 2**: Salary draft generation from attendance
- **Phase 3**: Finance journal posting + Payment processing
- **Phase 4**: Advanced features (leave balance, shifts, overtime, biometric integration)

---

## Database Structure

### Tables Created

#### 1. **staff_members** (Enhanced)
New fields added to existing table:
```
- staff_category: enum('Teaching', 'Office', 'Non-Teaching', 'Guest')
- payroll_type: enum('monthly', 'daily') 
- monthly_salary: decimal(12,2)
- daily_wage: decimal(12,2)
- salary_expense_head_id: foreign key to accounts
- leave_policy_group: string
- bank_account_number: string
- bank_account_holder: string
- bank_name: string
- bank_ifsc: string
```

#### 2. **staff_attendance** (New)
```
- id: primary key
- institute_id: foreign key
- staff_member_id: foreign key to staff_members
- attendance_date: date
- staff_category_snapshot: enum (stored at mark time)
- status: enum('Present', 'Absent', 'Half Day', 'Paid Leave', 'Unpaid Leave', 'Holiday', 'Week Off')
- in_time: time (optional)
- out_time: time (optional)
- late_minutes: integer
- overtime_hours: decimal(5,2)
- remarks: text
- marked_by: foreign key to staff_members
- created_at, updated_at
```

Unique constraint: `(institute_id, staff_member_id, attendance_date)`

#### 3. **attendance_lock_records** (New)
Prevents attendance editing after month close or salary generation:
```
- id: primary key
- institute_id: foreign key
- lock_year: year
- lock_month: month (1-12)
- lock_reason: enum('month_closed', 'salary_generated', 'manual')
- locked_by: foreign key to staff_members
- lock_remarks: text
- created_at, updated_at
```

---

## Models

### StaffAttendance
```php
// Relationships
$attendance->institute()
$attendance->staff()
$attendance->markedBy()

// Scopes
$attendance->forDate($date)
$attendance->forMonth($year, $month)
$attendance->forStaff($staffId)
$attendance->forCategory($category)
$attendance->present()
$attendance->absent()
$attendance->leave()
$attendance->paidLeave()
$attendance->unpaidLeave()

// Helpers
$attendance->isWorkingDay()
$attendance->isPaidDay()
$attendance->isLocked()
```

### AttendanceLockRecord
```php
// Methods
AttendanceLockRecord::isMonthLocked($instituteId, $year, $month)
AttendanceLockRecord::lockMonth($instituteId, $year, $month, $reason, $lockedBy, $remarks)
```

---

## Services

### AttendanceService
Main service for attendance operations:

```php
// Mark attendance for one staff
AttendanceService::markAttendance(
    $staffId, $date, $status,
    $inTime, $outTime, $lateMinutes, $overtimeHours, $remarks, $markedBy
);

// Mark attendance for multiple staff (bulk)
AttendanceService::bulkMarkAttendance($instituteId, $date, $staffIds, $status, $markedBy);

// Get attendance for a specific date
AttendanceService::getAttendanceForDate($instituteId, $date, $category);

// Get monthly summary for one staff
$summary = AttendanceService::getMonthlyAttendanceSummary($instituteId, $staffId, $year, $month);

// Get monthly summary for all staff in category
$summaries = AttendanceService::getCategoryMonthlyAttendance($instituteId, $category, $year, $month);

// Lock/unlock month
AttendanceService::lockMonth($instituteId, $year, $month, $reason, $lockedBy, $remarks);
AttendanceService::unlockMonth($instituteId, $year, $month);

// Get active staff for marking
$staff = AttendanceService::getActiveStaff($instituteId, $category);
```

### PayrollService
Main service for salary calculations and generation:

```php
// Generate salary draft from attendance
$records = PayrollService::generateSalaryDraft($instituteId, $year, $month, $category);

// Calculate salary for a staff member
$salaryData = PayrollService::calculateSalary($staff, $attendance, $year, $month);

// Get draft records for review
$drafts = PayrollService::getSalaryDraft($instituteId, $year, $month, $category);

// Approve salary
PayrollService::approveSalary($salaryRecord);

// Mark as paid and post journal
PayrollService::markSalaryPaid($salaryRecord, $paymentDate, $paymentMode, $remarks);

// Reverse payment
PayrollService::reverseSalary($salaryRecord, $reason);

// Get payroll summary
$summary = PayrollService::getPayrollSummary($instituteId, $year, $month, $category);
```

---

## Controllers

### AttendanceController
Handles daily and monthly attendance operations:

```
Routes:
GET  /payroll/attendance/daily         -> view daily register
POST /payroll/attendance/mark          -> mark attendance
POST /payroll/attendance/bulk-mark     -> bulk mark
GET  /payroll/attendance/monthly       -> view monthly summary
POST /payroll/attendance/lock-month    -> lock month
POST /payroll/attendance/unlock-month  -> unlock month
```

### PayrollController
Handles salary draft, approval, and payment:

```
Routes:
POST /payroll/generate-draft           -> generate salary draft
GET  /payroll/draft                    -> view draft
POST /payroll/approve/{id}             -> approve salary
POST /payroll/mark-paid/{id}           -> mark as paid
POST /payroll/reverse/{id}             -> reverse payment
GET  /payroll/summary                  -> payroll summary report
```

---

## Views

### Daily Attendance (`attendance/daily.blade.php`)
- Date selection
- Category filter (Teaching/Office/Non-Teaching/Guest)
- Register-style table with all staff
- Bulk mark present/absent buttons
- Individual attendance entry modal
- Fields: Status, In/Out time, Late minutes, Overtime, Remarks

### Monthly Summary (`attendance/monthly.blade.php`)
- Month/Year/Category filter
- Per-staff totals: Present, Absent, Half Day, Paid Leave, Unpaid Leave, Holiday, Week Off
- Payable days calculation
- Lock month button
- Detail view link for each staff

### Salary Draft (`payroll/draft.blade.php`)
- Year/Month/Category filter
- Summary cards: Total records, Gross, Deductions, Net Payable
- Generate draft button
- Detailed salary table with approval actions
- Status badges: Draft, Approved, Paid

### Payroll Summary (`payroll/summary.blade.php`)
- Complete payroll report for a period
- Financial breakdown: Basic, Allowances, Deductions, Net
- Status summary: Draft, Approved, Paid counts
- Detailed records table with payment info
- Print functionality

---

## Usage Workflow

### Phase 1: Daily Attendance
1. Go to **Payroll → Attendance → Daily**
2. Select date and category
3. View all active staff for that category
4. Either:
   - **Bulk mark**: Check staff → Select status → Mark All
   - **Individual mark**: Click edit → Enter details → Save
5. System prevents marking attendance before joining date
6. Each staff can have only 1 record per day (updates replace)

### Phase 2: Monthly Summary & Review
1. Go to **Payroll → Attendance → Monthly**
2. Select Month/Year/Category
3. View totals for all staff in category
4. Review calculated payable days
5. **Lock the month** when finalized (prevents further edits)

### Phase 3: Generate Salary Draft
1. After attendance is finalized, lock the month
2. Go to **Payroll → Generate Draft**
3. Select Year/Month/Category
4. System calculates salary for each staff based on:
   - Attendance records
   - Payroll type (Monthly/Daily)
   - Salary/Wage
   - Deductions only for Unpaid Leave
   - Overtime calculations
5. Creates draft SalaryRecords (status = 'draft')

### Phase 4: Review & Approve Salary
1. Go to **Payroll → Draft**
2. Review salary calculations per staff
3. Approve each record (status = 'approved')
4. Or make corrections to attendance and regenerate

### Phase 5: Process Payment
1. Go to **Payroll → Draft** or **Payroll → Summary**
2. Select approved salaries
3. Click **Mark Paid**
4. Enter payment date and mode (Cash/Bank/Check)
5. System automatically creates journal entry
6. Posts to finance GL accounts

### Phase 6: Reversals (if needed)
1. Go to **Payroll → Summary**
2. Find paid salary record
3. Click **Reverse**
4. Enter reason
5. System reverses journal entry
6. Payment marked as reversed

---

## Salary Calculation Logic

### Monthly Payroll Type
```
Basic Salary = staff.monthly_salary

Deductions:
  - Unpaid Leave Days × (Basic / 30)
  - Half Days × 0.5 × (Basic / 30)

Allowances:
  - Overtime Hours × Hourly Rate (Basic / 240)

Net Payable = Basic + Allowances - Deductions
```

### Daily/Wage Payroll Type
```
Basic Salary = Paid Days × staff.daily_wage

Where Paid Days = Present + (Half Days × 0.5) + Paid Leave Days
(Unpaid Leave and Absent days not counted)

Net Payable = Basic (no deductions for daily wage workers)
```

---

## Important Rules

### Validation Rules
- ✅ Prevent duplicate attendance per staff per date
- ✅ Prevent attendance before joining date
- ✅ Prevent attendance marking when month is locked
- ✅ Inactive staff hidden from marking
- ✅ Prevent salary mark-paid without approval
- ✅ Auto-lock month after salary draft generation

### Salary Rules
- ✅ Only Unpaid Leave reduces salary (Paid Leave does not)
- ✅ Paid Leave treated as working day
- ✅ Half Day = 0.5 days deduction
- ✅ Holiday/Week Off = 0 salary impact
- ✅ Absence only reduces salary if not Paid Leave

---

## Staff Master Setup

### Required Staff Fields
Before using this module, ensure staff_members table has:

1. **basic info**: name, email, mobile, photo
2. **joining_date**: when staff joined
3. **status**: active (true/false) or inactive
4. **payroll info**: 
   - staff_category (required)
   - payroll_type (required)
   - monthly_salary OR daily_wage
5. **accounting**: 
   - salary_expense_head_id (link to GL account)
6. **bank details** (optional, for bank payments):
   - bank_account_number
   - bank_account_holder
   - bank_name
   - bank_ifsc

### Account Heads Required
Ensure these GL accounts exist (created via AccountingSetupService):
- 3001: Teaching Staff Salary (Expense)
- 3002: Non-Teaching Staff Salary (Expense)
- 1001: Cash Account (Asset)
- 1002: Bank Account (Asset)

---

## API Endpoints

### Attendance
```
POST /payroll/attendance/mark
Body: {staff_id, date, status, in_time?, out_time?, late_minutes?, overtime_hours?, remarks?}
Response: {success, data: attendance}

POST /payroll/attendance/bulk-mark
Body: {date, staff_ids: [], status}
Response: {success, count}

POST /payroll/attendance/lock-month
Body: {year, month, reason?, remarks?}
Response: {success}
```

### Payroll
```
POST /payroll/generate-draft
Body: {year, month, category?}
Response: {success, count}

POST /payroll/approve/{id}
Response: {success}

POST /payroll/mark-paid/{id}
Body: {payment_date, payment_mode, remarks?}
Response: {success, journal_entry}

POST /payroll/reverse/{id}
Body: {reason}
Response: {success}
```

---

## Database Migrations

Run migrations to create new tables:
```bash
php artisan migrate
```

This will:
1. Add new columns to staff_members table
2. Create staff_attendance table
3. Create attendance_lock_records table

---

## Future Enhancements (Phase 4)

- Leave balance tracking
- Shift timings
- Biometric attendance import
- Overtime calculation policies
- Deduction rules (TDS, EPF, etc.)
- Bank reconciliation
- Salary slip generation
- Attendance reports and analytics

---

## Troubleshooting

### Issue: Can't mark attendance
- ✅ Check if staff joining_date is before attendance date
- ✅ Check if month is locked
- ✅ Check if staff is active

### Issue: Salary calculation seems wrong
- ✅ Verify attendance records for the month
- ✅ Check payroll_type and salary/daily_wage values
- ✅ Verify unpaid leave vs paid leave status
- ✅ Check half-day counts

### Issue: Journal entry not created on payment
- ✅ Verify salary_expense_head_id is set on staff
- ✅ Check if payment_account_id is set on SalaryRecord
- ✅ Review journal service logs
