# Payroll Module - Quick Implementation Guide

## 🚀 Installation Steps

### Step 1: Run Setup Command
```bash
php artisan payroll:setup
```

This automatically:
- Runs all migrations
- Creates GL accounts for salary expense heads
- Sets up necessary database tables

### Step 2: Update Staff Master Data

Go to Staff Member management and add:
```
✅ Staff Category (required):
   - Teaching
   - Office
   - Non-Teaching
   - Guest

✅ Payroll Type (required):
   - Monthly (for salary staff)
   - Daily (for wage workers)

✅ Salary/Wage (required):
   - Monthly Salary: for monthly payroll type
   - Daily Wage: for daily payroll type

✅ Bank Details (optional but recommended):
   - Bank Account Number
   - Account Holder Name
   - Bank Name
   - IFSC Code

✅ Salary Expense Head (required):
   - Select appropriate GL account for salary posting
```

### Step 3: Start Using the Module

#### Daily Attendance
```
URL: /payroll/attendance/daily

Steps:
1. Select date from calendar
2. Filter by staff category
3. Either:
   a) Bulk mark: Select staff → Choose status → Click "Mark All"
   b) Individual: Click edit per staff → Fill details → Save
4. View gets updated immediately
5. Can re-edit if not locked
```

#### Monthly Summary
```
URL: /payroll/attendance/monthly

Steps:
1. Select Year, Month, Category
2. View all staff totals:
   - Present, Absent, Half Day
   - Paid Leave, Unpaid Leave
   - Payable Days
3. Click on staff to see daily details
4. When finalized, click "Lock This Month"
```

#### Generate Salary
```
URL: /payroll/generate-draft

Steps:
1. Click "Generate Draft" button
2. Select Year, Month, Category
3. System calculates salary for each staff
4. Creates SalaryRecord with status = 'draft'
5. Automatically locks the month
```

#### Review Salary Draft
```
URL: /payroll/draft

Features:
- Summary cards with totals
- Per-staff breakdown
- Basic, Allowances, Deductions, Net
- Approve button per record
```

#### Process Payment
```
URL: /payroll/summary

Steps:
1. View complete payroll report
2. Click "Mark Paid" on approved records
3. Enter:
   - Payment Date
   - Payment Mode (Cash/Bank/Check)
   - Remarks
4. System creates journal entry automatically
5. Posts to GL accounts
```

---

## 📊 Data Flow

```
1. MARK ATTENDANCE (Daily)
   ↓
2. REVIEW MONTHLY SUMMARY
   ↓
3. LOCK MONTH (Prevents further edits)
   ↓
4. GENERATE SALARY DRAFT
   ↓
5. APPROVE SALARY
   ↓
6. MARK PAID (Creates finance entry)
   ↓
7. JOURNAL POSTED TO GL
   ↓
8. PAYMENT COMPLETE
```

---

## 💾 Database Changes Made

### New Tables:
- `staff_attendance` - Daily attendance records
- `attendance_lock_records` - Month lock status

### Modified Tables:
- `staff_members` - Added 11 new columns:
  - staff_category
  - payroll_type
  - monthly_salary
  - daily_wage
  - salary_expense_head_id
  - leave_policy_group
  - bank_account_number
  - bank_account_holder
  - bank_name
  - bank_ifsc

---

## 🔐 Important Validations

### Attendance Marking:
- ❌ Cannot mark before joining date
- ❌ Cannot mark if month is locked
- ❌ Cannot create duplicate for same staff + date (updates instead)
- ✅ Inactive staff auto-hidden

### Salary Generation:
- ❌ Cannot generate if attendance not finalized
- ✅ Month auto-locks after draft generation
- ✅ Only generates for active staff

### Payment Processing:
- ❌ Cannot mark paid if not approved
- ✅ Journal entry auto-created
- ✅ GL accounts auto-updated

---

## 📋 Attendance Statuses

| Status | Salary Impact | Notes |
|--------|---------------|-------|
| **Present** | ✅ Paid | Full day counted |
| **Absent** | ❌ Unpaid | Not counted as paid day |
| **Half Day** | ✅ 0.5 Day | Counted as 0.5 paid day |
| **Paid Leave** | ✅ Paid | Counted as paid day (no deduction) |
| **Unpaid Leave** | ❌ Unpaid | Deducted from salary |
| **Holiday** | ✅ Paid | Non-working, fully paid |
| **Week Off** | ✅ Paid | Scheduled off, fully paid |

---

## 💰 Salary Calculation Examples

### Example 1: Monthly Payroll
```
Staff: John (Teaching)
Payroll Type: Monthly
Basic Salary: ₹20,000

Attendance:
- Present: 22 days
- Absent: 0 days
- Half Day: 1 day (0.5 days counted)
- Paid Leave: 2 days
- Unpaid Leave: 1 day
- Week Off: 4 days

Calculation:
Payable Days = 22 + 0.5 + 2 = 24.5 days
Daily Rate = 20,000 / 30 = 666.67
Unpaid Leave Deduction = 1 × 666.67 = 666.67

Net Payable = 20,000 - 666.67 = ₹19,333.33
```

### Example 2: Daily Wage Payroll
```
Staff: Ram (Helper)
Payroll Type: Daily
Daily Wage: ₹500

Attendance:
- Present: 20 days
- Absent: 2 days
- Paid Leave: 1 day
- Unpaid Leave: 1 day
- Week Off: 5 days (+ 1 weekend)

Calculation:
Paid Days = 20 + 0.5 + 1 = 21.5 days
(Unpaid leave and absent not counted)

Basic Salary = 21.5 × 500 = ₹10,750

Net Payable = ₹10,750
```

---

## 🛠️ Common Tasks

### Task: Correct Attendance After Locking
```
1. Go to Payroll → Attendance → Monthly
2. Click "Unlock Month" button
3. Go to Daily attendance
4. Correct the record
5. Re-lock the month
6. Regenerate salary draft
```

### Task: Reverse a Payment
```
1. Go to Payroll → Summary
2. Find the paid record
3. Click "Reverse" button
4. Enter reason for reversal
5. System reverses journal entry
```

### Task: Pay via Bank Transfer
```
1. Go to Payroll → Summary
2. Filter by approved records
3. For each record:
   - Click "Mark Paid"
   - Select Payment Mode: "Bank"
   - Select Bank Account
   - Journal entry auto-posts
```

### Task: Generate Payroll Report
```
1. Go to Payroll → Summary
2. Select Period (Year/Month)
3. Select Category (optional)
4. View financial breakdown
5. Click "Print Report" to generate PDF
```

---

## ⚙️ Configuration

### To Change Calculation Logic:
Edit: `app/Services/PayrollService.php`
Method: `calculateSalary()`

### To Add Custom Deductions:
Edit: `app/Services/PayrollService.php`
Method: `calculateSalary()` → Add deduction rules

### To Change Overtime Rate:
Edit: `app/Services/PayrollService.php`
Line: `$overtimeRate = ($staff->monthly_salary ?? 0) / (30 * 8);`

### To Add New Categories:
Edit: Migrations
Update enum values in `staff_members.staff_category` column

---

## 🐛 Troubleshooting

### Q: Attendance not saving
**A:** Check if month is locked. Try unlocking it first.

### Q: Salary calculation wrong
**A:** Verify attendance records and payroll_type field on staff member.

### Q: Journal not created
**A:** Ensure staff has salary_expense_head_id set. Check GL account exists.

### Q: Can't generate draft
**A:** Month must be locked. Try clicking "Lock This Month" first.

### Q: Missing GL accounts
**A:** Run: `php artisan payroll:setup`

---

## 📞 Support

For issues or customizations, refer to:
- Main Documentation: `PAYROLL_MODULE_DOCUMENTATION.md`
- Models: `app/Models/StaffAttendance.php`
- Services: `app/Services/AttendanceService.php`, `PayrollService.php`
- Controllers: `app/Http/Controllers/Institute/Payroll/`
