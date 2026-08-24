<?php

namespace App\Http\Controllers\Institute\Admission;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Center;
use App\Models\ChannelPartner;
use App\Models\Course;
use App\Models\CoursePart;
use App\Models\CourseStream;
use App\Models\Student;
use App\Models\StudentAcademicIdentity;
use App\Services\AuditLogService;
use App\Services\StudentAcademicChangeService;
use App\Services\StudentIdService;
use App\Services\WalletService;
use App\Support\StudentSnapshotBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentBulkImportController extends Controller
{
    private const MAX_ROWS       = 500;
    private const MAX_FILE_MB    = 5;
    private const SESSION_TTL    = 30; // minutes
    private const ALLOWED_MIMES  = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel',
        'application/octet-stream', // some browsers send this for xlsx
    ];

    private const RELIGION_OPTIONS = ['hindu', 'muslim', 'sikh', 'christian', 'jain', 'parsi', 'buddhist', 'others'];
    private const GUARDIAN_RELATION_OPTIONS = ['father', 'mother', 'uncle', 'aunt', 'brother', 'sister', 'grandfather', 'grandmother', 'others'];
    private const SCHOLARSHIP_TYPE_OPTIONS = ['govt_central', 'govt_state', 'university', 'institute', 'private', 'other'];

    // ── Resolve institute_id for any guard ────────────────────────────
    private function instituteId(): int
    {
        foreach (['web', 'staff'] as $guard) {
            if (auth()->guard($guard)->check()) {
                $id = auth()->guard($guard)->user()?->institute_id;
                if ($id) return (int) $id;
            }
        }
        abort(403, 'Institute context missing.');
    }

    // ── Permission gate — no-op for the institute-owner (web) guard,
    // mirrors PromotionController::ensurePromotionAccess() for consistency
    // if these routes are ever extended to the staff guard.
    private function ensureBulkImportAccess(): void
    {
        $staff = Auth::guard('staff')->user();
        if (!$staff) return;
        abort_if(!$staff->hasPermission('admission_add'), 403, 'You are not allowed to bulk-import students.');
    }

    // Strip leading formula-trigger characters so a value like
    // =HYPERLINK(...) can't execute if this data is later opened in Excel.
    private function sanitizeCell(string $value): string
    {
        $value = trim(strip_tags($value));
        return preg_replace('/^[=+\-@]+/', '', $value);
    }

    // ── Show upload page ──────────────────────────────────────────────
    public function index()
    {
        $this->ensureBulkImportAccess();
        $instituteId    = $this->instituteId();
        $sessions       = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $activeSession  = $sessions->firstWhere('is_active', true);
        return view('institute.admission.bulk-import', compact('sessions', 'activeSession'));
    }

    // ── Download Excel Template ───────────────────────────────────────
    public function downloadTemplate()
    {
        $this->ensureBulkImportAccess();
        $instituteId = $this->instituteId();

        $courses = Course::where('institute_id', $instituteId)
            ->where('status', true)
            ->with(['streams' => fn($q) => $q->where('status', true)->orderBy('name')])
            ->orderBy('name')->get();

        $centers  = Center::where('institute_id', $instituteId)->where('status', true)->orderBy('name')->get();
        $partners = ChannelPartner::where('institute_id', $instituteId)->orderBy('name')->get();

        $spreadsheet = new Spreadsheet();

        // ── Sheet 1: Import Template ──────────────────────────────────
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Import Template');

        $headers = [
            'A'  => 'Student UID',
            'B'  => 'Name *',
            'C'  => 'Mobile *',
            'D'  => 'Course Name *',
            'E'  => 'Stream Name *',
            'F'  => 'Semester *',
            'G'  => 'Enrollment No',
            'H'  => 'Roll No',
            'I'  => 'UIN No',
            'J'  => 'Exam Form No',
            'K'  => 'Institute Form No',
            'L'  => 'SR No',
            'M'  => 'Reference No',
            'N'  => 'Admission Type',
            'O'  => 'Admission Source',
            'P'  => 'Source Name',
            'Q'  => 'Admission Date (DD/MM/YYYY)',
            'R'  => 'Gap Year (Yes/No)',
            'S'  => 'Father Name',
            'T'  => 'Father Mobile',
            'U'  => 'Father Occupation',
            'V'  => 'Mother Name',
            'W'  => 'Mother Mobile',
            'X'  => 'Mother Occupation',
            'Y'  => 'Guardian Name',
            'Z'  => 'Guardian Mobile',
            'AA' => 'Guardian Relation',
            'AB' => 'Email',
            'AC' => 'DOB (DD/MM/YYYY)',
            'AD' => 'Gender',
            'AE' => 'Category',
            'AF' => 'Special Category',
            'AG' => 'Religion',
            'AH' => 'Nationality',
            'AI' => 'Marital Status',
            'AJ' => 'Aadhar No',
            'AK' => 'APAAR No',
            'AL' => 'Student Type',
            'AM' => 'Perm Address',
            'AN' => 'Perm Village',
            'AO' => 'Perm Post',
            'AP' => 'Perm Thana',
            'AQ' => 'Perm District',
            'AR' => 'Perm State',
            'AS' => 'Perm Pincode',
            'AT' => 'Comm Same as Perm (Yes/No)',
            'AU' => 'Comm Address',
            'AV' => 'Comm City',
            'AW' => 'Comm Post',
            'AX' => 'Comm Thana',
            'AY' => 'Comm District',
            'AZ' => 'Comm State',
            'BA' => 'Comm Pincode',
            'BB' => 'Has Scholarship (Yes/No)',
            'BC' => 'Scholarship Name',
            'BD' => 'Scholarship Type',
            'BE' => 'Scholarship Authority',
            'BF' => 'Scholarship Amount',
            'BG' => 'Scholarship Ref No',
            'BH' => 'Scholarship Applied Date',
            'BI' => 'Student Status',
            'BJ' => 'Due Semesters (comma-separated)',
            'BK' => 'Due Amounts (comma-separated)',
            'BL' => 'Major Subject',
            'BM' => 'Minor Subjects (comma-separated)',
        ];

        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . '1', $label);
        }

        $lastCol = array_key_last($headers);

        // Dark header for all columns
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);

        // Purple highlight for required columns A–F
        $sheet->getStyle('A1:F1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF7C3AED']],
        ]);

        // Example data row
        $example = [
            'A' => '',  // leave blank = auto-generate
            'B' => 'Rahul Sharma',
            'C' => '9876543210',
            'D' => $courses->first()?->name ?? 'B.Sc.',
            'E' => $courses->first()?->streams->first()?->name ?? 'B.Sc. Physics',
            'F' => '1',
            'G' => 'EN2024001',
            'H' => '2024001',
            'I' => 'UIN001',
            'J' => '',
            'K' => '',
            'L' => '',
            'M' => '',
            'N' => 'New',
            'O' => 'Direct',
            'P' => '',
            'Q' => '01/07/2024',
            'R' => 'No',
            'S' => 'Ramesh Sharma',
            'T' => '9123456789',
            'U' => 'Farmer',
            'V' => 'Sunita Sharma',
            'W' => '',
            'X' => '',
            'Y' => '',
            'Z' => '',
            'AA' => '',
            'AB' => 'rahul@gmail.com',
            'AC' => '15/08/2002',
            'AD' => 'Male',
            'AE' => 'General',
            'AF' => '',
            'AG' => 'Hindu',
            'AH' => 'Indian',
            'AI' => 'Single',
            'AJ' => '1234-5678-9012',
            'AK' => '',
            'AL' => 'Regular',
            'AM' => 'Sector 5, Near Temple',
            'AN' => 'VillageName',
            'AO' => '',
            'AP' => '',
            'AQ' => 'Raipur',
            'AR' => 'Chhattisgarh',
            'AS' => '492001',
            'AT' => 'No',
            'AU' => '',
            'AV' => '',
            'AW' => '',
            'AX' => '',
            'AY' => '',
            'AZ' => '',
            'BA' => '',
            'BB' => 'No',
            'BC' => '',
            'BD' => '',
            'BE' => '',
            'BF' => '',
            'BG' => '',
            'BH' => '',
            'BI' => 'Active',
            'BJ' => '',
            'BK' => '',
            'BL' => '',
            'BM' => '',
        ];

        foreach ($example as $col => $val) {
            $sheet->setCellValue($col . '2', $val);
        }

        $sheet->getStyle('A2:' . $lastCol . '2')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FDF4']],
            'font' => ['italic' => true, 'color' => ['argb' => 'FF64748B']],
        ]);

        // Warning notice row
        $sheet->mergeCells('A3:' . $lastCol . '3');
        $sheet->setCellValue('A3', '⚠ This is an EXAMPLE row. Delete rows 2 and 3 before uploading. Fill actual student data from row 2.');
        $sheet->getStyle('A3')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']],
            'font' => ['bold' => true, 'color' => ['argb' => 'FF92400E'], 'size' => 10],
        ]);

        // Freeze header row, auto-size columns, set row height
        $sheet->freezePane('A4');
        $sheet->getRowDimension(1)->setRowHeight(32);
        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ── Sheet 2: Courses & Streams Reference ─────────────────────
        $refSheet = $spreadsheet->createSheet()->setTitle('Courses_Streams');
        $refSheet->setCellValue('A1', 'Course Name');
        $refSheet->setCellValue('B1', 'Stream Name');
        $refSheet->getStyle('A1:B1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        ]);
        $rRow = 2;
        foreach ($courses as $course) {
            foreach ($course->streams as $stream) {
                $refSheet->setCellValue('A' . $rRow, $course->name);
                $refSheet->setCellValue('B' . $rRow, $stream->name);
                $rRow++;
            }
        }
        $refSheet->getColumnDimension('A')->setAutoSize(true);
        $refSheet->getColumnDimension('B')->setAutoSize(true);

        // Centers & Partners sub-sheet
        if ($centers->isNotEmpty() || $partners->isNotEmpty()) {
            $srcSheet = $spreadsheet->createSheet()->setTitle('Sources');
            $srcSheet->setCellValue('A1', 'Type');
            $srcSheet->setCellValue('B1', 'Name (use in Source Name column)');
            $srcSheet->getStyle('A1:B1')->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            ]);
            $sRow = 2;
            foreach ($centers as $c) {
                $srcSheet->setCellValue('A' . $sRow, 'Center');
                $srcSheet->setCellValue('B' . $sRow, $c->name);
                $sRow++;
            }
            foreach ($partners as $p) {
                $srcSheet->setCellValue('A' . $sRow, 'Channel');
                $srcSheet->setCellValue('B' . $sRow, $p->name);
                $sRow++;
            }
            $srcSheet->getColumnDimension('A')->setAutoSize(true);
            $srcSheet->getColumnDimension('B')->setAutoSize(true);
        }

        // ── Sheet 3: Instructions ────────────────────────────────────
        $instrSheet = $spreadsheet->createSheet()->setTitle('Instructions');
        $rows = [
            ['Field',                       'Allowed Values / Notes'],
            ['Student UID',                 'Leave blank to auto-generate. If given, must be unique across the system.'],
            ['Name *',                      'Required. Full student name.'],
            ['Mobile *',                    'Required. 10-digit mobile number. Can repeat across students (e.g. siblings sharing a number).'],
            ['Course Name *',               'Required. Must match EXACTLY from "Courses_Streams" sheet (case-insensitive).'],
            ['Stream Name *',               'Required. Must match EXACTLY from "Courses_Streams" sheet for that course.'],
            ['Semester *',                  'Required. Absolute semester/term number for that course (e.g. a 3-year, 2-semester/year course allows 1-6; a 4-year trimester course allows 1-12) — NOT a fixed 1-8 range.'],
            ['Admission Type',              'New / Lateral / Transfer / Readmission  (default: New)'],
            ['Admission Source',            'Direct / Center / Channel  (default: Direct)'],
            ['Source Name',                 'If source is Center or Channel, enter name from "Sources" sheet.'],
            ['Admission Date',              'Format: DD/MM/YYYY  (default: today)'],
            ['Gap Year',                    'Yes / No  (default: No)'],
            ['DOB',                         'Format: DD/MM/YYYY'],
            ['Gender',                      'Male / Female / Other'],
            ['Category',                    'General / OBC / SC / ST / EWS / Others'],
            ['Special Category',            'Scholarship Quota / Sports Quota / Others / None (default: None)'],
            ['Marital Status',              'Single / Married / Divorced / Widowed (default: Single)'],
            ['Comm Same as Perm',           'Yes / No — If Yes, communication address auto-copied from permanent.'],
            ['Has Scholarship',             'Yes / No'],
            ['Scholarship Type',            'Govt Central / Govt State / University / Institute / Private / Other'],
            ['Scholarship Amount',          'Numeric value only. Example: 5000'],
            ['Scholarship Applied Date',    'Format: DD/MM/YYYY'],
            ['Student Status',              'Active / Passed Out / Detained / Transferred / Cancelled (default: Active). Passed Out/Detained/Transferred/Cancelled students do NOT get a fresh current-semester fee charge — use this for migrating existing/previous-year or already-graduated students.'],
            ['Semester * (for non-Active)', 'For Passed Out/Detained/Transferred/Cancelled students, enter their LAST/final semester here — not a currently-ongoing one.'],
            ['Due Semesters',               'Optional. Comma-separated list of semester numbers that still have a pending balance from before joining this system. Example: 2,3'],
            ['Due Amounts',                 'Optional. Comma-separated amounts matching Due Semesters 1-to-1, same order. Example: 4000,2500 means Semester 2 owes 4000 and Semester 3 owes 2500. For Active students, only semesters BEFORE the Semester * value can carry a due (the current semester\'s fee is auto-calculated by the system). For Passed Out/Detained/etc., dues up to and including the Semester * value are allowed. Shows up as "Previous Due (Semester N)" on the student\'s fee ledger.'],
            ['Major Subject',               'Optional. Must match a subject name available for that Course/Stream/Semester. Unmatched names are skipped, not blocked.'],
            ['Minor Subjects',              'Optional. Comma-separated. Same matching rule as Major Subject. Compulsory subjects for that stream are auto-enrolled regardless of this column.'],
            ['',                            ''],
            ['IMPORTANT RULES',             ''],
            ['',                            '1. Columns marked with * are REQUIRED — a row missing these cannot be imported.'],
            ['',                            '2. DO NOT change column header names or order.'],
            ['',                            '3. DELETE the example rows (row 2–3) before uploading.'],
            ['',                            '4. Maximum ' . self::MAX_ROWS . ' data rows per file.'],
            ['',                            '5. File size must be under ' . self::MAX_FILE_MB . ' MB.'],
            ['',                            '6. Accepted formats: .xlsx, .xls only.'],
            ['',                            '7. Duplicate Student UID / Roll No / Enrollment No / UIN / Exam Form No are flagged as minor issues — you choose at preview time whether to still import that row.'],
            ['',                            '8. If Student UID is left blank (or is a duplicate), the system generates one automatically.'],
            ['',                            '9. Re-uploading a file that still has already-imported rows in it: if the same Name + Mobile already exists, it is flagged as a "possible duplicate" minor issue — remove those rows or leave "Import Anyway" unchecked to skip them.'],
        ];
        foreach ($rows as $i => $row) {
            $instrSheet->setCellValue('A' . ($i + 1), $row[0]);
            $instrSheet->setCellValue('B' . ($i + 1), $row[1]);
        }
        $instrSheet->getStyle('A1:B1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']],
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
        ]);
        $instrSheet->getColumnDimension('A')->setWidth(28);
        $instrSheet->getColumnDimension('B')->setWidth(75);

        // Activate first sheet
        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'student_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ── Preview: parse, validate, store in session ────────────────────
    public function preview(Request $request)
    {
        $this->ensureBulkImportAccess();
        $request->validate([
            'file'       => ['required', 'file', 'mimes:xlsx,xls', 'max:' . (self::MAX_FILE_MB * 1024)],
            'session_id' => ['required', 'integer', 'min:1'],
        ]);

        $instituteId = $this->instituteId();

        // Double MIME check
        $mime = $request->file('file')->getMimeType();
        if (!in_array($mime, self::ALLOWED_MIMES)) {
            return back()->withErrors(['file' => 'Invalid file type. Only .xlsx or .xls files are accepted.']);
        }

        // Verify session belongs to this institute
        $session = AcademicSession::where('id', $request->session_id)
            ->where('institute_id', $instituteId)
            ->firstOrFail();

        // ── Parse Excel ───────────────────────────────────────────────
        $realPath = $request->file('file')->getRealPath();
        try {
            $reader      = IOFactory::createReaderForFile($realPath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($realPath);
            $rawRows     = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Could not read file. Make sure it is a valid Excel file.']);
        }

        if (empty($rawRows)) {
            return back()->withErrors(['file' => 'File is empty.']);
        }

        // Remove header row
        array_shift($rawRows);

        // Filter out completely empty rows
        $dataRows = array_values(array_filter(
            $rawRows,
            fn($r) => !empty(array_filter(array_map('strval', $r), fn($v) => trim($v) !== ''))
        ));

        if (empty($dataRows)) {
            return back()->withErrors(['file' => 'No data rows found. Please fill in student data and remove example rows.']);
        }

        if (count($dataRows) > self::MAX_ROWS) {
            return back()->withErrors(['file' => 'File has ' . count($dataRows) . ' rows. Maximum allowed is ' . self::MAX_ROWS . '. Please split into smaller files.']);
        }

        // ── Load lookup maps ──────────────────────────────────────────
        $coursesAll = Course::where('institute_id', $instituteId)->where('status', true)->get();
        $coursesLower = $coursesAll->mapWithKeys(fn($c) => [strtolower(trim($c->name)) => $c]);

        $streamsAll = CourseStream::whereIn('course_id', $coursesAll->pluck('id'))
            ->where('status', true)->get();
        // Key: "stream_lower|course_id" => stream model
        $streamsLower = $streamsAll->mapWithKeys(
            fn($s) => [strtolower(trim($s->name)) . '|' . $s->course_id => $s]
        );

        $centersLower  = Center::where('institute_id', $instituteId)->where('status', true)->get()
            ->mapWithKeys(fn($c) => [strtolower(trim($c->name)) => $c->id])->toArray();
        $partnersLower = ChannelPartner::where('institute_id', $instituteId)->get()
            ->mapWithKeys(fn($p) => [strtolower(trim($p->name)) => $p->id])->toArray();

        // Existing records for duplicate detection (only truly unique fields)
        $existingUids     = Student::where('institute_id', $instituteId)->pluck('student_uid')->flip()->toArray();
        $existingRolls    = Student::where('institute_id', $instituteId)->whereNotNull('roll_no')->pluck('roll_no')->flip()->toArray();
        $existingEnrolls  = Student::where('institute_id', $instituteId)->whereNotNull('enrollment_no')->pluck('enrollment_no')->flip()->toArray();
        $existingUins     = Student::where('institute_id', $instituteId)->whereNotNull('uin_no')->pluck('uin_no')->flip()->toArray();
        $existingExamForms = Student::where('institute_id', $instituteId)->whereNotNull('exam_form_no')->pluck('exam_form_no')->flip()->toArray();

        // Name+Mobile combo lookup — catches the common "re-uploaded the same
        // file after fixing a few rows" case, where Student UID/Roll/etc. were
        // left blank the first time (so those checks above never trigger) but
        // the student was already created. Soft only: Mobile alone can repeat
        // legitimately (e.g. siblings), so this only flags when BOTH match.
        $existingByNameMobile = Student::where('institute_id', $instituteId)
            ->get(['student_uid', 'name', 'mobile'])
            ->keyBy(fn($s) => strtolower(trim($s->name)) . '|' . $s->mobile);
        $seenNameMobile = [];

        $validRows   = [];
        $softRows    = [];
        $invalidRows = [];
        $subjectLookupCache = []; // "streamId|year" => ['available' => [...], 'compulsory' => [...]]
        $seenUids        = [];
        $seenRolls       = [];
        $seenEnrolls     = [];
        $seenUins        = [];
        $seenExamForms   = [];

        foreach ($dataRows as $rowIdx => $rawCols) {
            $rowNum = $rowIdx + 2; // Excel row number (header=1, data starts at 2)

            // Pad to 65 columns, convert all to trimmed, formula-defanged strings
            $c = array_map(
                fn($v) => $this->sanitizeCell((string)($v ?? '')),
                array_pad(array_values($rawCols), 65, '')
            );

            // Map columns by index (matches template header order)
            [$uid, $name, $mobile, $courseName, $streamName, $semester,
             $enrollNo, $rollNo, $uinNo, $examFormNo, $instFormNo, $srNo, $refNo,
             $admType, $admSource, $sourceName, $admDate, $gapYear,
             $fatherName, $fatherMobile, $fatherOcc,
             $motherName, $motherMobile, $motherOcc,
             $guardianName, $guardianMobile, $guardianRel,
             $email, $dob, $gender, $category, $specialCat,
             $religion, $nationality, $maritalStatus, $aadhar, $apaar, $studentType,
             $permAddr, $permVillage, $permPost, $permThana, $permDist, $permState, $permPin,
             $commSame, $commAddr, $commCity, $commPost, $commThana, $commDist, $commState, $commPin,
             $hasScholar, $scholarName, $scholarType, $scholarAuth,
             $scholarAmt, $scholarRef, $scholarDate,
             $studentStatusRaw, $dueSemestersRaw, $dueAmountsRaw,
             $majorSubjectsRaw, $minorSubjectsRaw]
                = array_pad($c, 65, '');

            // Hard errors block the row entirely (mandatory fields: Name, Mobile,
            // Course, Stream, Semester). Soft errors are optional-field problems —
            // the row still previews as importable, but the user is asked to
            // confirm before those specific fields get left blank.
            $hardErrors = [];
            $softErrors = [];

            // Clean mobile
            $mobile = preg_replace('/[^0-9]/', '', $mobile);

            // ── Required fields ───────────────────────────────────────
            if ($name === '') $hardErrors[] = 'Name is required';
            if ($mobile === '') {
                $hardErrors[] = 'Mobile is required';
            } elseif (strlen($mobile) !== 10) {
                $hardErrors[] = 'Mobile must be exactly 10 digits';
            }

            // ── Possible duplicate (same Name + Mobile already imported) ──
            if ($name !== '' && $mobile !== '') {
                $comboKey = strtolower(trim($name)) . '|' . $mobile;
                if ($existingMatch = $existingByNameMobile->get($comboKey)) {
                    $softErrors[] = "Possible duplicate — \"{$name}\" with this mobile already exists (UID \"{$existingMatch->student_uid}\"). Check before importing again.";
                } elseif (isset($seenNameMobile[$comboKey])) {
                    $softErrors[] = "Possible duplicate — \"{$name}\" with this mobile appears more than once in this file.";
                } else {
                    $seenNameMobile[$comboKey] = true;
                }
            }

            // Course
            $courseObj = null;
            $courseId  = null;
            if ($courseName === '') {
                $hardErrors[] = 'Course Name is required';
            } else {
                $courseObj = $coursesLower[strtolower($courseName)] ?? null;
                if (!$courseObj) {
                    $hardErrors[] = "Course \"{$courseName}\" not found — check spelling or Courses_Streams sheet";
                } else {
                    $courseId = $courseObj->id;
                }
            }

            // Stream
            $streamObj = null;
            $streamId  = null;
            if ($streamName === '') {
                $hardErrors[] = 'Stream Name is required';
            } elseif ($courseId) {
                $streamObj = $streamsLower[strtolower($streamName) . '|' . $courseId] ?? null;
                if (!$streamObj) {
                    $hardErrors[] = "Stream \"{$streamName}\" not found in course \"{$courseName}\" — check Courses_Streams sheet";
                } else {
                    $streamId = $streamObj->id;
                }
            }

            // Semester — max valid value depends on the course's own length
            // (duration in years × its semesters/trimesters per year), not a fixed
            // number: a 3-year semester course tops out at 6, a 5-year course at 10,
            // a 4-year trimester course at 12, etc.
            $sem = (int) $semester;
            $semestersPerYear = $courseObj ? max(1, $courseObj->effectiveSemestersPerYear()) : 1;
            $courseMaxPart = $courseObj
                ? max(1, (int) ($courseObj->duration ?? 1)) * $semestersPerYear
                : 8;
            if ($sem < 1 || $sem > $courseMaxPart) {
                $hardErrors[] = $courseObj
                    ? "Semester must be a number from 1 to {$courseMaxPart} for course \"{$courseName}\""
                    : 'Semester must be a positive number';
            }
            // Year/Part this semester falls in — uses the course's actual
            // semesters-per-year instead of a hardcoded ÷2, so a trimester or
            // yearly course maps to the right CoursePart and subject list.
            $yearNumber = max(1, (int) ceil($sem / $semestersPerYear));

            // ── Student Status ────────────────────────────────────────
            $statusNorm = match(strtolower(trim($studentStatusRaw))) {
                '', 'active'                           => 'active',
                'passed out', 'passed_out', 'passout'  => 'passed_out',
                'detained'                              => 'detained',
                'transferred'                           => 'transferred',
                'cancelled', 'canceled'                 => 'cancelled',
                default                                 => null,
            };
            if ($statusNorm === null) {
                $softErrors[] = "Student Status \"{$studentStatusRaw}\" not recognized — treated as Active";
                $statusNorm = 'active';
            }
            $isTerminalStatus = $statusNorm !== 'active';

            // ── Semester-wise Previous Due ────────────────────────────
            // Two comma-separated columns instead of one fixed column per semester —
            // works the same for a 2-semester course or a 15-term one, without the
            // template needing to guess the longest course up front.
            // Active students get their current semester auto-charged fresh by
            // WalletService::onAdmission(), so a due here can only be for a semester
            // strictly BEFORE that one. Terminal-status students (passed out/detained/
            // transferred/cancelled) never get that auto-charge, so their own last
            // semester's due is also collectible — up to and including Semester *.
            $maxDueSemester = $isTerminalStatus ? $sem : $sem - 1;
            $semesterDues = [];
            $dueSemList = array_values(array_filter(array_map('trim', explode(',', $dueSemestersRaw)), fn($v) => $v !== ''));
            $dueAmtList = array_values(array_filter(array_map('trim', explode(',', $dueAmountsRaw)), fn($v) => $v !== ''));

            if (count($dueSemList) !== count($dueAmtList)) {
                $softErrors[] = 'Due Semesters and Due Amounts have a different number of values — dues for this row skipped';
            } else {
                foreach ($dueSemList as $i => $rawSemNum) {
                    $rawAmt = $dueAmtList[$i];
                    if (!ctype_digit($rawSemNum)) {
                        $softErrors[] = "Due Semesters value \"{$rawSemNum}\" is not a valid semester number — skipped";
                        continue;
                    }
                    if (!is_numeric($rawAmt)) {
                        $softErrors[] = "Due Amounts value \"{$rawAmt}\" is not a valid number — skipped";
                        continue;
                    }
                    $semNum = (int) $rawSemNum;
                    $dueAmount = (float) $rawAmt;
                    if ($dueAmount < 0) {
                        $softErrors[] = "Due amount for Semester {$semNum} cannot be negative — skipped";
                        continue;
                    }
                    if ($semNum < 1 || $semNum > $maxDueSemester) {
                        $softErrors[] = "Due for Semester {$semNum} not allowed — current Semester is {$sem}"
                            . ($isTerminalStatus ? '' : ' (only semesters before it can carry a due)') . ' — skipped';
                        continue;
                    }
                    if (isset($semesterDues[$semNum])) {
                        $softErrors[] = "Semester {$semNum} appears more than once in Due Semesters — later value skipped";
                        continue;
                    }
                    if ($dueAmount > 0) {
                        $semesterDues[$semNum] = $dueAmount;
                    }
                }
            }

            // ── Duplicate checks (only truly unique fields) — soft, since a
            // duplicate here just means that specific identifier gets dropped
            // (Student UID auto-regenerates; the rest are left blank) rather
            // than blocking the whole student from being imported.
            $cleanUid = $uid ?: null;
            if ($cleanUid !== null) {
                if (isset($existingUids[$cleanUid])) {
                    $softErrors[] = "Student UID \"{$cleanUid}\" already exists — a new one will be auto-generated";
                } elseif (isset($seenUids[$cleanUid])) {
                    $softErrors[] = "Student UID \"{$cleanUid}\" appears more than once in this file — a new one will be auto-generated";
                } else {
                    $seenUids[$cleanUid] = true;
                }
            }

            $cleanRoll = $rollNo ?: null;
            if ($cleanRoll !== null) {
                if (isset($existingRolls[$cleanRoll])) {
                    $softErrors[] = "Roll No \"{$cleanRoll}\" already exists in the system";
                } elseif (isset($seenRolls[$cleanRoll])) {
                    $softErrors[] = "Roll No \"{$cleanRoll}\" appears more than once in this file";
                } else {
                    $seenRolls[$cleanRoll] = true;
                }
            }

            $cleanEnroll = $enrollNo ?: null;
            if ($cleanEnroll !== null) {
                if (isset($existingEnrolls[$cleanEnroll])) {
                    $softErrors[] = "Enrollment No \"{$cleanEnroll}\" already exists in the system";
                } elseif (isset($seenEnrolls[$cleanEnroll])) {
                    $softErrors[] = "Enrollment No \"{$cleanEnroll}\" appears more than once in this file";
                } else {
                    $seenEnrolls[$cleanEnroll] = true;
                }
            }

            $cleanUin = $uinNo ?: null;
            if ($cleanUin !== null) {
                if (isset($existingUins[$cleanUin])) {
                    $softErrors[] = "UIN No \"{$cleanUin}\" already exists in the system";
                } elseif (isset($seenUins[$cleanUin])) {
                    $softErrors[] = "UIN No \"{$cleanUin}\" appears more than once in this file";
                } else {
                    $seenUins[$cleanUin] = true;
                }
            }

            $cleanExamForm = $examFormNo ?: null;
            if ($cleanExamForm !== null) {
                if (isset($existingExamForms[$cleanExamForm])) {
                    $softErrors[] = "Exam Form No \"{$cleanExamForm}\" already exists in the system";
                } elseif (isset($seenExamForms[$cleanExamForm])) {
                    $softErrors[] = "Exam Form No \"{$cleanExamForm}\" appears more than once in this file";
                } else {
                    $seenExamForms[$cleanExamForm] = true;
                }
            }

            // ── Admission source ──────────────────────────────────────
            // 'channel_partner' is the actual DB enum value — 'channel' (the
            // template's display word) is not, and used to fail silently at
            // insert time with a truncation error.
            $admSourceNorm = 'direct';
            $admSourceId   = null;
            $srcLower = strtolower($admSource);
            if (in_array($srcLower, ['center', 'centre'])) {
                $admSourceNorm = 'center';
                if ($sourceName !== '') {
                    $admSourceId = $centersLower[strtolower($sourceName)] ?? null;
                    if (!$admSourceId) $softErrors[] = "Center \"{$sourceName}\" not found — Source Name left unlinked";
                }
            } elseif (in_array($srcLower, ['channel', 'channel_partner', 'channel partner'])) {
                $admSourceNorm = 'channel_partner';
                if ($sourceName !== '') {
                    $admSourceId = $partnersLower[strtolower($sourceName)] ?? null;
                    if (!$admSourceId) $softErrors[] = "Channel Partner \"{$sourceName}\" not found — Source Name left unlinked";
                }
            } elseif ($srcLower === 'online') {
                $admSourceNorm = 'online';
            } elseif ($srcLower !== '' && $srcLower !== 'direct') {
                $softErrors[] = "Admission Source \"{$admSource}\" not recognized — treated as Direct";
            }

            // ── Date parsing ──────────────────────────────────────────
            $parsedAdmDate     = $this->parseDate($admDate)     ?? now()->toDateString();
            $parsedDob         = $this->parseDate($dob);
            $parsedScholarDate = $this->parseDate($scholarDate);

            if ($dob !== '' && !$parsedDob)         $softErrors[] = "DOB \"{$dob}\" invalid — left blank";
            if ($admDate !== '' && !$this->parseDate($admDate)) $softErrors[] = "Admission Date \"{$admDate}\" invalid — defaulted to today";
            if ($scholarDate !== '' && !$parsedScholarDate) $softErrors[] = "Scholarship Applied Date \"{$scholarDate}\" invalid — left blank";

            // ── Enum normalisation ────────────────────────────────────
            // Mapped to the exact enum values the `students` table accepts (see
            // create_students_table migration) — a value that merely LOOKS right
            // (e.g. 'general' instead of 'gen', 'readmission' instead of
            // 're_admission') fails at insert time with a MySQL "Data truncated"
            // error instead of a friendly validation message, since these are
            // native ENUM columns, not free-text.
            $genderNorm = match(strtolower(trim($gender))) { 'male' => 'male', 'female' => 'female', 'other' => 'other', '' => null, default => null };
            if ($gender !== '' && $genderNorm === null) {
                $softErrors[] = "Gender \"{$gender}\" not recognized — left blank";
            }
            $categoryNorm   = match(strtolower(trim($category))) {
                'general', 'gen' => 'gen',
                'obc'            => 'obc',
                'sc'             => 'sc',
                'st'             => 'st',
                'ews'            => 'ews',
                'others', 'other'=> 'others',
                default          => null,
            };
            if ($category !== '' && $categoryNorm === null) {
                $softErrors[] = "Category \"{$category}\" not recognized — left blank";
            }
            $maritalNorm    = match(strtolower(trim($maritalStatus))) {
                '', 'single'        => 'single',
                'married'           => 'married',
                'divorced'          => 'divorced',
                'widowed'           => 'widowed',
                default             => null,
            };
            if ($maritalNorm === null) {
                $softErrors[] = "Marital Status \"{$maritalStatus}\" not recognized — treated as Single";
                $maritalNorm = 'single';
            }
            $admTypeNorm    = match(strtolower(trim($admType))) {
                '', 'new'                        => 'new',
                'lateral'                        => 'lateral',
                'transfer'                       => 'transfer',
                'readmission', 're_admission', 're-admission' => 're_admission',
                default                          => null,
            };
            if ($admTypeNorm === null) {
                $softErrors[] = "Admission Type \"{$admType}\" not recognized — treated as New";
                $admTypeNorm = 'new';
            }
            // special_category is a free-text varchar column (not an ENUM), so
            // an unrecognized value is passed through as-is instead of rejected.
            $specialCatNorm = match(strtolower(trim($specialCat))) {
                '', 'none', 'na', 'n/a'                  => 'none',
                'scholarship quota', 'scholarship_quota' => 'scholarship_quota',
                'sports quota', 'sports_quota', 'sports' => 'sports_quota',
                'pwd'                                    => 'pwd',
                'ex_serviceman', 'ex-serviceman', 'ex serviceman' => 'ex_serviceman',
                'ncc'                                    => 'ncc',
                'others', 'other'                        => 'others',
                default                                  => mb_substr(trim($specialCat), 0, 50),
            };

            $religionNorm = null;
            if ($religion !== '') {
                $religionLower = strtolower(trim($religion));
                if (in_array($religionLower, self::RELIGION_OPTIONS, true)) {
                    $religionNorm = $religionLower;
                } else {
                    $softErrors[] = "Religion \"{$religion}\" not recognized — left blank";
                }
            }

            $guardianRelNorm = null;
            if ($guardianRel !== '') {
                $guardianRelLower = strtolower(trim($guardianRel));
                if (in_array($guardianRelLower, self::GUARDIAN_RELATION_OPTIONS, true)) {
                    $guardianRelNorm = $guardianRelLower;
                } else {
                    $softErrors[] = "Guardian Relation \"{$guardianRel}\" not recognized — left blank";
                }
            }

            $scholarTypeNorm = null;
            if ($scholarType !== '') {
                $scholarTypeLower = strtolower(str_replace([' ', '-'], '_', trim($scholarType)));
                $scholarTypeNorm = match ($scholarTypeLower) {
                    'govt_central', 'central_govt', 'central_government', 'government_central' => 'govt_central',
                    'govt_state', 'state_govt', 'state_government', 'government_state' => 'govt_state',
                    'university' => 'university',
                    'institute', 'college' => 'institute',
                    'private' => 'private',
                    'other', 'others' => 'other',
                    default => null,
                };
                if ($scholarTypeNorm === null) {
                    $softErrors[] = "Scholarship Type \"{$scholarType}\" not recognized — left blank. Use: Govt Central / Govt State / University / Institute / Private / Other";
                }
            }

            $emailNorm = null;
            if ($email !== '') {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emailNorm = $email;
                } else {
                    $softErrors[] = "Email \"{$email}\" is not valid — left blank";
                }
            }

            $scholarAmtNorm = null;
            if ($scholarAmt !== '') {
                if (is_numeric($scholarAmt)) {
                    $scholarAmtNorm = (float) $scholarAmt;
                } else {
                    $softErrors[] = "Scholarship Amount \"{$scholarAmt}\" is not numeric — left blank";
                }
            }

            // ── Subject enrollment (optional) ─────────────────────────
            // Course/Stream stay mandatory as before; individual subjects do
            // not. Unmatched subject names are skipped (soft error), not
            // blocking. Compulsory subjects for the stream/year are always
            // auto-included, same as the normal admission form.
            $subjectRoleRows = [];
            if ($streamObj) {
                $cacheKey = $streamObj->id . '|' . $yearNumber;
                if (!isset($subjectLookupCache[$cacheKey])) {
                    $subjectLookupCache[$cacheKey] = [
                        'available'   => $streamObj->subjectsForYear($yearNumber)->get()
                            ->mapWithKeys(fn($s) => [strtolower(trim($s->name)) => $s->id]),
                        'compulsory'  => $streamObj->compulsorySubjectsForYear($yearNumber)->get()
                            ->map(fn($s) => ['subject_id' => $s->id, 'subject_role' => $s->pivot->subject_role]),
                    ];
                }
                $subjectCacheEntry = $subjectLookupCache[$cacheKey];

                foreach (['major' => $majorSubjectsRaw, 'minor' => $minorSubjectsRaw] as $role => $rawList) {
                    foreach (array_filter(array_map('trim', explode(',', $rawList)), fn($v) => $v !== '') as $subjName) {
                        $subjId = $subjectCacheEntry['available'][strtolower($subjName)] ?? null;
                        if ($subjId) {
                            $subjectRoleRows[] = ['subject_id' => $subjId, 'subject_role' => $role, 'is_auto_included' => false];
                        } else {
                            $softErrors[] = ucfirst($role) . " Subject \"{$subjName}\" not found for \"{$streamName}\" Semester {$sem} — skipped";
                        }
                    }
                }

                foreach ($subjectCacheEntry['compulsory'] as $compulsory) {
                    $subjectRoleRows[] = $compulsory + ['is_auto_included' => true];
                }
            }

            $rowData = [
                'row_num'                  => $rowNum,
                'student_uid'              => $cleanUid,
                'name'                     => $name,
                'mobile'                   => $mobile,
                'course_name'              => $courseObj?->name ?? $courseName,
                'stream_name'              => $streamObj?->name ?? $streamName,
                'course_id'                => $courseId,
                'course_type_id'           => $courseObj?->course_type_id ?? null,
                'stream_id'                => $streamId,
                'current_semester'         => $sem,
                'year_number'              => $yearNumber,
                'subject_role_rows'        => $subjectRoleRows,
                'enrollment_no'            => $enrollNo ?: null,
                'roll_no'                  => $rollNo ?: null,
                'uin_no'                   => $uinNo ?: null,
                'exam_form_no'             => $examFormNo ?: null,
                'institute_form_no'        => $instFormNo ?: null,
                'sr_no'                    => $srNo ?: null,
                'reference_no'             => $refNo ?: null,
                'admission_type'           => $admTypeNorm,
                'admission_source'         => $admSourceNorm,
                'admission_source_id'      => $admSourceId,
                'admission_date'           => $parsedAdmDate,
                'gap_year'                 => strtolower($gapYear) === 'yes',
                'father_name'              => $fatherName ?: null,
                'father_mobile'            => preg_replace('/[^0-9]/', '', $fatherMobile) ?: null,
                'father_occupation'        => $fatherOcc ?: null,
                'mother_name'              => $motherName ?: null,
                'mother_mobile'            => preg_replace('/[^0-9]/', '', $motherMobile) ?: null,
                'mother_occupation'        => $motherOcc ?: null,
                'guardian_name'            => $guardianName ?: null,
                'guardian_mobile'          => preg_replace('/[^0-9]/', '', $guardianMobile) ?: null,
                'guardian_relation'        => $guardianRelNorm,
                'email'                    => $emailNorm,
                'dob'                      => $parsedDob,
                'gender'                   => $genderNorm,
                'category'                 => $categoryNorm,
                'special_category'         => $specialCatNorm,
                'religion'                 => $religionNorm,
                'nationality'              => match(strtolower(trim($nationality))) {
                    'indian'      => 'indian',
                    'nepali'      => 'nepali',
                    'bhutanese'   => 'bhutanese',
                    'sri_lankan', 'srilankan', 'sri lankan' => 'sri_lankan',
                    'others', 'other' => 'others',
                    default       => 'indian',
                },
                'marital_status'           => $maritalNorm,
                'aadhar_no'                => preg_replace('/[^0-9\-]/', '', $aadhar) ?: null,
                'apaar_no'                 => $apaar ?: null,
                'student_type'             => $studentType ?: 'regular',
                'perm_address'             => $permAddr ?: null,
                'perm_village'             => $permVillage ?: null,
                'perm_post'                => $permPost ?: null,
                'perm_thana'               => $permThana ?: null,
                'perm_district'            => $permDist ?: null,
                'perm_state'               => $permState ?: null,
                'perm_pincode'             => $permPin ?: null,
                'comm_same_as_perm'        => strtolower($commSame) === 'yes',
                'comm_address'             => $commAddr ?: null,
                'comm_city'                => $commCity ?: null,
                'comm_post'                => $commPost ?: null,
                'comm_thana'               => $commThana ?: null,
                'comm_district'            => $commDist ?: null,
                'comm_state'               => $commState ?: null,
                'comm_pincode'             => $commPin ?: null,
                'has_scholarship'          => strtolower($hasScholar) === 'yes',
                'scholarship_name'         => $scholarName ?: null,
                'scholarship_type'         => $scholarTypeNorm,
                'scholarship_authority'    => $scholarAuth ?: null,
                'scholarship_amount'       => $scholarAmtNorm,
                'scholarship_ref_no'       => $scholarRef ?: null,
                'scholarship_applied_date' => $parsedScholarDate,
                'student_status'           => $statusNorm,
                'semester_dues'            => $semesterDues,
                'soft_errors'              => $softErrors,
                'errors'                   => array_merge($hardErrors, $softErrors),
            ];

            if (!empty($hardErrors)) {
                $invalidRows[] = $rowData;
            } elseif (!empty($softErrors)) {
                $softRows[] = $rowData;
            } else {
                $validRows[] = $rowData;
            }
        }

        if (empty($validRows) && empty($softRows) && empty($invalidRows)) {
            return back()->withErrors(['file' => 'No readable data found in the file.']);
        }

        // Store valid + soft-issue rows in session with an expiring token —
        // import() decides at confirm time whether to include the soft ones.
        $token = Str::random(48);
        session([
            'bulk_import_token'      => $token,
            'bulk_import_session_id' => $session->id,
            'bulk_import_rows'       => $validRows,
            'bulk_import_soft_rows'  => $softRows,
            'bulk_import_expires_at' => now()->addMinutes(self::SESSION_TTL)->timestamp,
        ]);

        return view('institute.admission.bulk-import-preview', compact(
            'validRows', 'softRows', 'invalidRows', 'token', 'session'
        ));
    }

    // ── Import: consume session and create students ───────────────────
    public function import(Request $request)
    {
        $this->ensureBulkImportAccess();
        $request->validate(['token' => ['required', 'string', 'size:48']]);

        $instituteId = $this->instituteId();

        // Token & expiry check
        if (session('bulk_import_token') !== $request->token) {
            return redirect()->route('admissions.bulk-import.index')
                ->withErrors(['token' => 'Import session expired or invalid. Please upload the file again.']);
        }
        if ((int) session('bulk_import_expires_at', 0) < now()->timestamp) {
            session()->forget(['bulk_import_token', 'bulk_import_rows', 'bulk_import_soft_rows', 'bulk_import_session_id', 'bulk_import_expires_at']);
            return redirect()->route('admissions.bulk-import.index')
                ->withErrors(['token' => 'Import session expired (30 min limit). Please upload again.']);
        }

        $sessionId  = (int) session('bulk_import_session_id');
        $rows       = session('bulk_import_rows', []);
        $softRows   = session('bulk_import_soft_rows', []);
        $includeSoft = $request->boolean('include_soft_rows');
        $softIncludedCount = $includeSoft ? count($softRows) : 0;
        if ($includeSoft) {
            $rows = array_merge($rows, $softRows);
        }

        // Verify session still belongs to institute
        $academicSession = AcademicSession::where('id', $sessionId)
            ->where('institute_id', $instituteId)
            ->firstOrFail();

        if (empty($rows)) {
            return redirect()->route('admissions.bulk-import.index')
                ->withErrors(['token' => 'No valid rows to import.']);
        }

        // Re-verify course/stream still exist, are active, and belong to this
        // institute — the preview snapshot can be up to 30 minutes old.
        $validCourseIds = Course::where('institute_id', $instituteId)->where('status', true)->pluck('id')->flip();
        $validStreamIds = CourseStream::whereIn('course_id', $validCourseIds->keys())->where('status', true)->pluck('id')->flip();

        $year      = StudentIdService::getYearFromSession($academicSession->name);
        $imported  = 0;
        $failed    = 0;
        $lastError = null;

        // Load course parts for year-number lookup
        $coursePartsByYear = CoursePart::whereIn('course_id', array_unique(array_column($rows, 'course_id')))
            ->get()
            ->groupBy(fn($p) => $p->course_id . '|' . $p->year_number);

        DB::transaction(function () use (
            $rows, $instituteId, $sessionId, $year, $coursePartsByYear,
            $validCourseIds, $validStreamIds, &$imported, &$failed, &$lastError
        ) {
            foreach ($rows as $rowData) {
                try {
                    if (!isset($validCourseIds[$rowData['course_id'] ?? 0]) || !isset($validStreamIds[$rowData['stream_id'] ?? 0])) {
                        throw new \RuntimeException('Course or Stream is no longer active/available.');
                    }

                    // Generate or use provided UID
                    $uid = $rowData['student_uid'] ?: null;
                    if (!$uid) {
                        $uid = StudentIdService::generateStudentId($instituteId, $year);
                    } elseif (Student::where('institute_id', $instituteId)->where('student_uid', $uid)->exists()) {
                        // Provided UID already taken — auto-generate instead
                        $uid = StudentIdService::generateStudentId($instituteId, $year);
                    }

                    // Resolve course part from the year computed at preview time
                    // (course's own semesters-per-year, not a hardcoded ÷2).
                    $yearNumber  = $rowData['year_number'] ?? 1;
                    $partKey     = ($rowData['course_id'] ?? 0) . '|' . $yearNumber;
                    $coursePartId = $coursePartsByYear->get($partKey)?->first()?->id ?? null;

                    $student = Student::create([
                        'institute_id'             => $instituteId,
                        'academic_session_id'      => $sessionId,
                        'student_uid'              => $uid,
                        'name'                     => $rowData['name'],
                        'mobile'                   => $rowData['mobile'],
                        'email'                    => $rowData['email'],
                        'dob'                      => $rowData['dob'],
                        'gender'                   => $rowData['gender'],
                        'religion'                 => $rowData['religion'],
                        'category'                 => $rowData['category'],
                        'special_category'         => $rowData['special_category'],
                        'nationality'              => $rowData['nationality'],
                        'marital_status'           => $rowData['marital_status'],
                        'aadhar_no'                => $rowData['aadhar_no'],
                        'apaar_no'                 => $rowData['apaar_no'],
                        'student_type'             => $rowData['student_type'],
                        'father_name'              => $rowData['father_name'],
                        'father_mobile'            => $rowData['father_mobile'],
                        'father_occupation'        => $rowData['father_occupation'],
                        'mother_name'              => $rowData['mother_name'],
                        'mother_mobile'            => $rowData['mother_mobile'],
                        'mother_occupation'        => $rowData['mother_occupation'],
                        'guardian_name'            => $rowData['guardian_name'],
                        'guardian_mobile'          => $rowData['guardian_mobile'],
                        'guardian_relation'        => $rowData['guardian_relation'],
                        'enrollment_no'            => $rowData['enrollment_no'],
                        'roll_no'                  => $rowData['roll_no'],
                        'uin_no'                   => $rowData['uin_no'],
                        'exam_form_no'             => $rowData['exam_form_no'],
                        'institute_form_no'        => $rowData['institute_form_no'],
                        'sr_no'                    => $rowData['sr_no'],
                        'reference_no'             => $rowData['reference_no'],
                        'admission_type'           => $rowData['admission_type'],
                        'admission_source'         => $rowData['admission_source'],
                        'admission_source_id'      => $rowData['admission_source_id'],
                        'admission_date'           => $rowData['admission_date'],
                        'submitted_date'           => now()->toDateString(),
                        'gap_year'                 => $rowData['gap_year'],
                        'course_type_id'           => $rowData['course_type_id'],
                        'course_stream_id'         => $rowData['stream_id'],
                        'course_part_id'           => $coursePartId,
                        'current_semester'         => $rowData['current_semester'],
                        'perm_address'             => $rowData['perm_address'],
                        'perm_village'             => $rowData['perm_village'],
                        'perm_post'                => $rowData['perm_post'],
                        'perm_thana'               => $rowData['perm_thana'],
                        'perm_district'            => $rowData['perm_district'],
                        'perm_state'               => $rowData['perm_state'],
                        'perm_pincode'             => $rowData['perm_pincode'],
                        'comm_same_as_perm'        => $rowData['comm_same_as_perm'],
                        'comm_address'             => $rowData['comm_address'],
                        'comm_city'                => $rowData['comm_city'],
                        'comm_post'                => $rowData['comm_post'],
                        'comm_thana'               => $rowData['comm_thana'],
                        'comm_district'            => $rowData['comm_district'],
                        'comm_state'               => $rowData['comm_state'],
                        'comm_pincode'             => $rowData['comm_pincode'],
                        'has_scholarship'          => $rowData['has_scholarship'],
                        'scholarship_name'         => $rowData['scholarship_name'],
                        'scholarship_type'         => $rowData['scholarship_type'],
                        'scholarship_authority'    => $rowData['scholarship_authority'],
                        'scholarship_amount'       => $rowData['scholarship_amount'],
                        'scholarship_ref_no'       => $rowData['scholarship_ref_no'],
                        'scholarship_applied_date' => $rowData['scholarship_applied_date'],
                        'status'                   => $rowData['student_status'] ?? 'active',
                        'is_quick_admission'       => false,
                        'admitted_by_staff_id'     => Auth::guard('staff')->check() ? Auth::guard('staff')->id() : null,
                        'admitted_by_type'         => Auth::guard('staff')->check() ? 'staff' : 'admin',
                    ]);

                    // Only auto-charge the current-semester fee for students who are
                    // actually still studying — a Passed Out/Detained/Transferred/
                    // Cancelled row is a historical record, not a fresh enrollment.
                    if (($rowData['student_status'] ?? 'active') === 'active') {
                        WalletService::onAdmission($student);
                    }

                    foreach ($rowData['semester_dues'] ?? [] as $semNum => $dueAmount) {
                        WalletService::chargeBulkImportPreviousDue($student, $semNum, $dueAmount);
                    }

                    $subjectIds = [];
                    if (!empty($rowData['subject_role_rows'])) {
                        $subjectIds = StudentAcademicChangeService::syncSubjects(
                            $student, $sessionId, $yearNumber, $rowData['subject_role_rows']
                        );
                    }

                    $student->load('educationDetails');

                    StudentAcademicIdentity::firstOrCreate(
                        [
                            'student_id'          => $student->id,
                            'academic_session_id' => $student->academic_session_id,
                        ],
                        [
                            'institute_id'              => $student->institute_id,
                            'course_id'                 => $rowData['course_id'],
                            'course_stream_id'          => $student->course_stream_id,
                            'course_part_id'            => $student->course_part_id,
                            'semester_at_time'          => $student->current_semester,
                            'subjects_json'             => $subjectIds,
                            'form_no'                   => last(explode('/', $student->student_uid)),
                            'sr_no_snapshot'            => $student->sr_no,
                            'enrollment_no_snapshot'    => $student->enrollment_no,
                            'roll_no_snapshot'          => $student->roll_no,
                            'admission_source_snapshot' => $student->admission_source,
                            'source'                    => 'admission',
                            'admission_type'            => $student->admission_type ?? 'new',
                            'profile_snapshot'          => StudentSnapshotBuilder::build($student),
                        ]
                    );

                    $imported++;
                } catch (\Throwable $e) {
                    $failed++;
                    $lastError = $e->getMessage();
                    \Log::error('Bulk import row failed', [
                        'row'   => $rowData['row_num'] ?? '?',
                        'name'  => $rowData['name'] ?? '?',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        session()->forget(['bulk_import_token', 'bulk_import_rows', 'bulk_import_soft_rows', 'bulk_import_session_id', 'bulk_import_expires_at']);

        if ($imported === 0) {
            $errMsg = $lastError
                ? "Import failed. No students were saved. Error: {$lastError}"
                : "Import failed — no students were saved. Please try again or contact support.";
            return redirect()->route('admissions.bulk-import.index')->withErrors(['import' => $errMsg]);
        }

        $msg = "{$imported} student(s) imported successfully.";
        if ($softIncludedCount > 0) $msg .= " ({$softIncludedCount} of them had minor data issues you chose to import anyway.)";
        if ($failed > 0) $msg .= " {$failed} row(s) failed to save — check Laravel logs.";

        AuditLogService::log(
            $instituteId,
            'admission',
            'bulk_import_completed',
            $msg,
            null,
            ['session_id' => $sessionId, 'imported' => $imported, 'failed' => $failed, 'soft_issue_rows_included' => $softIncludedCount]
        );

        return redirect()->route('admissions.index')->with('success', $msg);
    }

    // ── Helper: parse date from common formats or Excel serial number ─
    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;

        // Excel stores dates as serial numbers (e.g. 36932 = 15/08/2002)
        // This happens when setReadDataOnly(true) skips cell formatting
        if (is_numeric($value) && (float)$value > 1000) {
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value);
                return $dt->format('Y-m-d');
            } catch (\Throwable) {}
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'd-m-y', 'Y/m/d'] as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $value);
            if ($dt && $dt->format($fmt) === $value) {
                return $dt->format('Y-m-d');
            }
        }
        return null;
    }
}
