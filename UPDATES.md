# College ERP — Updates & Change Log

> **Purpose:** Client-facing record of all features, fixes, and improvements.
> **Format:** Latest changes appear at the top. Each entry lists what changed and why.
> **Last Updated:** 2026-05-08

---

## [2026-05-08] — Admission Form Fixes & Discount Limit Enforcement

### Full Admission Form — Validation Errors Now Visible
- Fixed: submitting the full admission form with missing required fields caused a **silent page reload** with no error shown
- The form would silently fail and redisplay with all data intact but no indication of what was wrong
- Fix: a red error summary box now appears at the top of the form listing all validation errors clearly

### Full Admission Form — Photo Preserved After Failed Submit
- Fixed: the student photo uploaded in the admission form **disappeared** when the form failed validation and was redisplayed
- Root cause: the photo was being saved to temp storage only after validation — if validation failed, the file was never saved and was lost
- Fix: photo is now saved to temp storage **before** validation runs, so `old('photo_temp')` is populated and the preview reappears correctly after a redirect

### Full Admission Form — Admission Source Dropdown Preserved After Failed Submit
- Fixed: after a failed submit, the **admission source selection** (Center / Channel Partner / Direct) was reverting to its default state
- The dropdown visibility was only being restored inside a block that required `old('course_id')` to be set — if not, the dropdown reset to hidden
- Fix: the restore logic now always runs regardless of whether a course was previously selected

### Full Admission Form — "Main Center" Selection Not Lost After Submit
- Fixed: selecting a specific center (e.g., "Main Center") in the admission form and then submitting caused the center dropdown to revert to **"Select Center"** after redirect
- Root cause: two `<select name="admission_source_id">` elements existed in the DOM (one for Center, one for Partner) — the empty partner dropdown was overwriting the center value in `old()`
- Fix: the inactive dropdown is now marked `disabled` in HTML so only the active dropdown submits its value

### Staff Discount Limit — Fee Payment Page
- Fixed: the per-staff discount limits configured in Staff Permissions were **not being enforced** on the full admission fee-payment page
- Staff members could enter any discount amount, bypassing their configured maximum
- Fix: the fee-payment page now reads the staff member's discount permissions and:
  - Disables the discount field entirely for fee types the staff member is not allowed to discount
  - Caps the discount amount at the staff member's configured maximum percentage

### Staff Discount Limit — Quick Registration Form
- Fixed: the per-staff discount limits configured in Staff Permissions were **not being enforced** on the Quick Registration fee rows
- Fix: Quick Registration now applies the same discount enforcement:
  - Discount input is disabled for fee types not permitted for the staff member
  - Entering a discount above the maximum automatically clamps it to the allowed limit
  - Enforcement is consistent across all three fee row interactions (typing, changing, row toggle)

---

## [2026-05-08] — Staff Global Search Crash Fix

### Staff Panel — Global Search Internal Server Error Fixed
- Fixed: opening `staff/students/search` caused a **500 Internal Server Error** (`TypeError: method_exists(): Argument #1 must be of type object|string, null given`)
- Root cause: Staff controller was passing `$students = null` when no filters were active; PHP 8.3 disallows passing `null` to `method_exists()`
- Fix 1: view now null-checks `$students` before calling `method_exists()` (safe for all PHP 8.x versions)
- Fix 2: Staff global search now shows **50 most recently admitted students** on initial page load (same as Institute panel — previously showed nothing or crashed)
- Fix 3: Staff search pagination changed from 12 → 15 results per page to match Institute panel

---

## [2026-05-08] — Global Search, Staff Fixes, Performance

### Global Student Search — Complete Redesign
- Replaced the old single-search-box form with a **DataTable-style per-column filter table**
- Each column (Student ID, Name, Father Name, Mother Name, Mobile, Email, Enrollment No, Roll No) now has its own search box directly in the table header
- Search results update **live as you type** (300ms debounce — no button press needed)
- Page loads with the **50 most recently admitted students** by default instead of showing an empty screen
- Session filter dropdown added to quickly narrow results by academic year
- Multi-word search supported: searching "Ram Kumar" in Name column finds students whose name contains both "Ram" and "Kumar" in any order (same for Father Name and Mother Name)
- Cursor position is preserved after each live-search refresh (no loss of focus mid-typing)
- Previous in-flight search request is cancelled when a new keystroke arrives (no stale results)

### Performance — Database Indexes Added
- Added 9 database indexes to speed up student search queries:
  - `students` table: name, father_name, mother_name, mobile, email, enrollment_no, roll_no (all scoped to institute)
  - `student_academic_identity` table: roll_no, form_no
- Expected improvement: search queries 5–10× faster on large student databases (10,000+ records)
- **Action required:** Run `php artisan migrate` once when deploying to apply indexes

### Staff Panel — Dashboard Route Fix
- Fixed a crash: `Route [dashboard] not defined` error that appeared when opening the **Print All** page from the Staff panel
- Root cause: code was using a single generic route name that only worked for the Institute panel
- Fix: each panel (Institute, Staff, Center, Partner) now resolves its own correct dashboard route

### Admission Form — Education Section Fix (Quick Registration)
- Fixed: Education details section was not appearing in the **Quick Registration form** (Staff panel)
- Root cause: education section was disabled by default in the quick-form configuration
- Fix: Education section (10th, 12th) is now enabled by default in quick registration
- This also fixed blank rows in the **Print Form** education table — data now saves and prints correctly

### Print Form — Education Table Columns Added
- **Stream** and **District** columns were missing from the education table in the printed admission form
- These fields are collected in the form but were not displaying in the printout
- Fix: both columns now appear in the correct order in the print layout
- Print-All page: Stream column added to the education summary table; blank row cells corrected to 10 columns

### Staff Panel — Payment Mode & Bank Account Filtering
- Fixed: Staff members with restricted payment permissions (e.g., Cash + UPI + RTGS only) were seeing **all 7 payment modes** in the fee collection form
- Fixed: Bank accounts not linked to the staff member were appearing in the dropdown
- Fix applied to three locations:
  1. Quick Registration form (Staff panel)
  2. Full Admission fee-payment form
  3. Fee Collection form (`fee/create`)
- If a staff member has no payment permission record configured, only **Cash** is shown as a safe fallback
- Bank accounts are now filtered to show only those the staff member is permitted to use

### Datetime Fix — Non-Cash Payment in Quick Registration
- Fixed: the "Payment Date & Time" field in Quick Registration was defaulting to **UTC time** (showing 5:30 hours behind for IST users)
- Fix: field now defaults to the **device's local time** (correct for all time zones)

---

## [2026-05-07] — Multi-Word Search & Permission Fixes

### Global Search — Multi-Word Filter Support
- Enhanced global search filters to match multi-word queries
- Example: typing "Mohan Lal" in Father Name now requires both words to be present (more precise results)
- Applied to: Student Name, Father Name, Mother Name columns

### Practical Fee Token — Permission Fix
- Fixed permission checks in `PracticalFeeTokenController` and `StaffMember`
- Staff members now correctly see/hide the Practical Fee Token feature based on their assigned permissions

### Admission Permissions — Clarity Fix
- Replaced internal permission key `admission_add` with `admission_edit` where appropriate
- Ensures staff with edit-only access can perform the correct operations

---

## [2026-05-06] — Education Stream & Print Improvements

### Education Details — Stream Field Added
- Added **Stream** field to the student education details table (e.g., Science, Arts, Commerce for 12th standard)
- Field appears in: Admission form, Quick Registration form, Print Form, Print-All page
- Migration added: `2026_05_03_160000_add_stream_to_student_education_details_table`

### Print View — Label Fixes
- Corrected labels: "Registration Number" → "Student UID", "Form Number" → "Form No" for clarity in print views

---

## [2026-05-05] — Staff Discount Permissions

### Discount Control — Per Fee Item Permissions
- Institute admins can now set discount permissions **per fee item** for each staff member
- Options: Allow/Deny discount on each specific fee item (replaces the old single max-percentage limit)
- Staff members see only the fee items they are permitted to discount
- Max discount percentage control removed — replaced by item-level allow/deny

### Discount UI — Default & Sync Improvements
- Discount percentage field now shows a sensible default when enabled
- Input fields stay in sync when switching between percentage and flat amount modes

---

## [2026-04-30 to 2026-05-04] — Payroll, Certificates, Reports

### Staff Payroll — Attendance & Salary Management
- Full attendance marking system for staff (monthly, lockable)
- Salary draft generation with attendance completeness check and warnings
- Payroll navigation menu added to Staff portal
- Attendance lock mechanism: once locked, attendance records cannot be edited

### Certificate Generation Module
- Certificate issuance system for students (Phase 1–6 complete)
- 3 PDF themes available
- Certificate number auto-generation (5-digit padded)
- Routes under `certificate.*`

### Custom Student Report
- Dynamic column selection: pick which student fields to include
- Export formats: Excel (XLSX), CSV, PDF, ZIP
- Deep column filters: filter results within the report by any column
- Fee summary columns available: total fee, collected, balance, payment mode breakdown

### Fee Collection Report
- Improved layout and print functionality
- Invoice display improvements
- Fine details added to report

---

## [2026-04-20 to 2026-04-29] — Practical Fees, Library, Student Types

### Practical Fee Tokens
- Full practical fee token management: create, list, view
- Fine and discount fields on practical fee entries
- Already-paid tracking: shows previously collected practical fees
- Staff permission checks for practical fee access

### Library Management
- Library member management (sync from students)
- Book reservation, circulation, and return workflows
- Vendor and subject masters
- Reservation feature flag — UI elements disabled if feature not enabled

### Student Types & Course Types
- Dynamic student type management (replaces hardcoded enum)
- Course type management with CRUD
- Student type and course type selection integrated into admission forms and fee structures

### Bulk Student Correction Upload
- Upload Excel file to bulk-correct student records
- Column mapping interface with accordion sections
- Search and filter on mapping page

---

## [2026-04-10 to 2026-04-19] — Admissions, Fee, Printing

### Admission Slip & Receipt
- Thermal receipt layout improvements
- Admission slip layout redesigned for clarity
- Print-All page: headers, padding, and font size improvements
- One-time payment locking: fee fields lock after first successful collection

### Quick Admission Form
- Guardian details fields added
- Communication address auto-sync from permanent address
- Admission source tracking (referral, walk-in, etc.)
- Non-cash payments: mandatory date & time field
- Transaction reference field for RTGS/NEFT/Cheque/DD

### Fee Calculation
- Fine inclusion in collectable amount
- Pending fines reflected in balance summary
- Wallet service integrated for fee due list summary

### Student Profile
- Additional fields in profile edit: guardian, address details, education
- Photo optimization on upload (auto-resize/compress)
- Submitted date field added to admission records

---

## How to Apply Pending Migrations

Run the following after deployment to apply all pending database changes:

```bash
php artisan migrate
```

Key pending migrations (as of 2026-05-08):
- `2026_05_08_000002_add_search_indexes_to_students_table` — 9 performance indexes for student search

---

*This file is maintained by the development team. Update it whenever a feature, fix, or improvement is deployed.*
