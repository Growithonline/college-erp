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
use App\Services\StudentIdService;
use App\Services\WalletService;
use Illuminate\Http\Request;
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

    // ── Show upload page ──────────────────────────────────────────────
    public function index()
    {
        $instituteId    = $this->instituteId();
        $sessions       = AcademicSession::where('institute_id', $instituteId)->orderByDesc('id')->get();
        $activeSession  = $sessions->firstWhere('is_active', true);
        return view('institute.admission.bulk-import', compact('sessions', 'activeSession'));
    }

    // ── Download Excel Template ───────────────────────────────────────
    public function downloadTemplate()
    {
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
            ['Mobile *',                    'Required. 10-digit mobile number. Must be unique.'],
            ['Course Name *',               'Required. Must match EXACTLY from "Courses_Streams" sheet (case-insensitive).'],
            ['Stream Name *',               'Required. Must match EXACTLY from "Courses_Streams" sheet for that course.'],
            ['Semester *',                  'Required. Number from 1 to 8.'],
            ['Admission Type',              'New / Lateral / Transfer / Readmission  (default: New)'],
            ['Admission Source',            'Direct / Center / Channel  (default: Direct)'],
            ['Source Name',                 'If source is Center or Channel, enter name from "Sources" sheet.'],
            ['Admission Date',              'Format: DD/MM/YYYY  (default: today)'],
            ['Gap Year',                    'Yes / No  (default: No)'],
            ['DOB',                         'Format: DD/MM/YYYY'],
            ['Gender',                      'Male / Female / Other'],
            ['Category',                    'General / OBC / SC / ST'],
            ['Marital Status',              'Single / Married'],
            ['Comm Same as Perm',           'Yes / No — If Yes, communication address auto-copied from permanent.'],
            ['Has Scholarship',             'Yes / No'],
            ['Scholarship Amount',          'Numeric value only. Example: 5000'],
            ['Scholarship Applied Date',    'Format: DD/MM/YYYY'],
            ['',                            ''],
            ['IMPORTANT RULES',             ''],
            ['',                            '1. Columns marked with * are REQUIRED.'],
            ['',                            '2. DO NOT change column header names or order.'],
            ['',                            '3. DELETE the example rows (row 2–3) before uploading.'],
            ['',                            '4. Maximum ' . self::MAX_ROWS . ' data rows per file.'],
            ['',                            '5. File size must be under ' . self::MAX_FILE_MB . ' MB.'],
            ['',                            '6. Accepted formats: .xlsx, .xls only.'],
            ['',                            '7. Duplicate mobile numbers will be flagged as errors.'],
            ['',                            '8. If Student UID is left blank, system generates one automatically.'],
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

        $validRows   = [];
        $invalidRows = [];
        $seenUids        = [];
        $seenRolls       = [];
        $seenEnrolls     = [];
        $seenUins        = [];
        $seenExamForms   = [];

        foreach ($dataRows as $rowIdx => $rawCols) {
            $rowNum = $rowIdx + 2; // Excel row number (header=1, data starts at 2)

            // Pad to 60 columns, convert all to trimmed strings
            $c = array_map(
                fn($v) => trim(strip_tags((string)($v ?? ''))),
                array_pad(array_values($rawCols), 60, '')
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
             $scholarAmt, $scholarRef, $scholarDate] = array_pad($c, 60, '');

            $errors = [];

            // Clean mobile
            $mobile = preg_replace('/[^0-9]/', '', $mobile);

            // ── Required fields ───────────────────────────────────────
            if ($name === '') $errors[] = 'Name is required';
            if ($mobile === '') {
                $errors[] = 'Mobile is required';
            } elseif (strlen($mobile) !== 10) {
                $errors[] = 'Mobile must be exactly 10 digits';
            }

            // Course
            $courseObj = null;
            $courseId  = null;
            if ($courseName === '') {
                $errors[] = 'Course Name is required';
            } else {
                $courseObj = $coursesLower[strtolower($courseName)] ?? null;
                if (!$courseObj) {
                    $errors[] = "Course \"{$courseName}\" not found — check spelling or Courses_Streams sheet";
                } else {
                    $courseId = $courseObj->id;
                }
            }

            // Stream
            $streamObj = null;
            $streamId  = null;
            if ($streamName === '') {
                $errors[] = 'Stream Name is required';
            } elseif ($courseId) {
                $streamObj = $streamsLower[strtolower($streamName) . '|' . $courseId] ?? null;
                if (!$streamObj) {
                    $errors[] = "Stream \"{$streamName}\" not found in course \"{$courseName}\" — check Courses_Streams sheet";
                } else {
                    $streamId = $streamObj->id;
                }
            }

            // Semester
            $sem = (int) $semester;
            if ($sem < 1 || $sem > 8) $errors[] = 'Semester must be a number from 1 to 8';

            // ── Duplicate checks (only truly unique fields) ───────────
            $cleanUid = $uid ?: null;
            if ($cleanUid !== null) {
                if (isset($existingUids[$cleanUid])) {
                    $errors[] = "Student UID \"{$cleanUid}\" already exists in the system";
                } elseif (isset($seenUids[$cleanUid])) {
                    $errors[] = "Student UID \"{$cleanUid}\" appears more than once in this file";
                } else {
                    $seenUids[$cleanUid] = true;
                }
            }

            $cleanRoll = $rollNo ?: null;
            if ($cleanRoll !== null) {
                if (isset($existingRolls[$cleanRoll])) {
                    $errors[] = "Roll No \"{$cleanRoll}\" already exists in the system";
                } elseif (isset($seenRolls[$cleanRoll])) {
                    $errors[] = "Roll No \"{$cleanRoll}\" appears more than once in this file";
                } else {
                    $seenRolls[$cleanRoll] = true;
                }
            }

            $cleanEnroll = $enrollNo ?: null;
            if ($cleanEnroll !== null) {
                if (isset($existingEnrolls[$cleanEnroll])) {
                    $errors[] = "Enrollment No \"{$cleanEnroll}\" already exists in the system";
                } elseif (isset($seenEnrolls[$cleanEnroll])) {
                    $errors[] = "Enrollment No \"{$cleanEnroll}\" appears more than once in this file";
                } else {
                    $seenEnrolls[$cleanEnroll] = true;
                }
            }

            $cleanUin = $uinNo ?: null;
            if ($cleanUin !== null) {
                if (isset($existingUins[$cleanUin])) {
                    $errors[] = "UIN No \"{$cleanUin}\" already exists in the system";
                } elseif (isset($seenUins[$cleanUin])) {
                    $errors[] = "UIN No \"{$cleanUin}\" appears more than once in this file";
                } else {
                    $seenUins[$cleanUin] = true;
                }
            }

            $cleanExamForm = $examFormNo ?: null;
            if ($cleanExamForm !== null) {
                if (isset($existingExamForms[$cleanExamForm])) {
                    $errors[] = "Exam Form No \"{$cleanExamForm}\" already exists in the system";
                } elseif (isset($seenExamForms[$cleanExamForm])) {
                    $errors[] = "Exam Form No \"{$cleanExamForm}\" appears more than once in this file";
                } else {
                    $seenExamForms[$cleanExamForm] = true;
                }
            }

            // ── Admission source ──────────────────────────────────────
            $admSourceNorm = 'direct';
            $admSourceId   = null;
            $srcLower = strtolower($admSource);
            if (in_array($srcLower, ['center', 'centre'])) {
                $admSourceNorm = 'center';
                if ($sourceName !== '') {
                    $admSourceId = $centersLower[strtolower($sourceName)] ?? null;
                    if (!$admSourceId) $errors[] = "Center \"{$sourceName}\" not found — check Sources sheet";
                }
            } elseif ($srcLower === 'channel') {
                $admSourceNorm = 'channel';
                if ($sourceName !== '') {
                    $admSourceId = $partnersLower[strtolower($sourceName)] ?? null;
                    if (!$admSourceId) $errors[] = "Channel Partner \"{$sourceName}\" not found — check Sources sheet";
                }
            }

            // ── Date parsing ──────────────────────────────────────────
            $parsedAdmDate     = $this->parseDate($admDate)     ?? now()->toDateString();
            $parsedDob         = $this->parseDate($dob);
            $parsedScholarDate = $this->parseDate($scholarDate);

            if ($dob !== '' && !$parsedDob)         $errors[] = "DOB \"{$dob}\" invalid — use DD/MM/YYYY";
            if ($admDate !== '' && !$this->parseDate($admDate)) $errors[] = "Admission Date \"{$admDate}\" invalid — use DD/MM/YYYY";

            // ── Enum normalisation ────────────────────────────────────
            $genderNorm     = match(strtolower($gender))        { 'male' => 'male', 'female' => 'female', 'other' => 'other', default => null };
            $categoryNorm   = match(strtolower($category))      { 'general' => 'general', 'obc' => 'obc', 'sc' => 'sc', 'st' => 'st', default => null };
            $maritalNorm    = match(strtolower($maritalStatus)) { 'single' => 'single', 'married' => 'married', default => 'single' };
            $admTypeNorm    = in_array(strtolower($admType), ['new','lateral','transfer','readmission'])
                                ? strtolower($admType) : 'new';

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
                'guardian_relation'        => $guardianRel ?: null,
                'email'                    => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
                'dob'                      => $parsedDob,
                'gender'                   => $genderNorm,
                'category'                 => $categoryNorm,
                'special_category'         => $specialCat ?: 'none',
                'religion'                 => $religion ?: null,
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
                'scholarship_type'         => $scholarType ?: null,
                'scholarship_authority'    => $scholarAuth ?: null,
                'scholarship_amount'       => is_numeric($scholarAmt) ? (float) $scholarAmt : null,
                'scholarship_ref_no'       => $scholarRef ?: null,
                'scholarship_applied_date' => $parsedScholarDate,
                'errors'                   => $errors,
            ];

            if (empty($errors)) {
                $validRows[] = $rowData;
            } else {
                $invalidRows[] = $rowData;
            }
        }

        if (empty($validRows) && empty($invalidRows)) {
            return back()->withErrors(['file' => 'No readable data found in the file.']);
        }

        // Store valid rows in session with expiring token
        $token = Str::random(48);
        session([
            'bulk_import_token'      => $token,
            'bulk_import_session_id' => $session->id,
            'bulk_import_rows'       => $validRows,
            'bulk_import_expires_at' => now()->addMinutes(self::SESSION_TTL)->timestamp,
        ]);

        return view('institute.admission.bulk-import-preview', compact(
            'validRows', 'invalidRows', 'token', 'session'
        ));
    }

    // ── Import: consume session and create students ───────────────────
    public function import(Request $request)
    {
        $request->validate(['token' => ['required', 'string', 'size:48']]);

        $instituteId = $this->instituteId();

        // Token & expiry check
        if (session('bulk_import_token') !== $request->token) {
            return redirect()->route('admissions.bulk-import.index')
                ->withErrors(['token' => 'Import session expired or invalid. Please upload the file again.']);
        }
        if ((int) session('bulk_import_expires_at', 0) < now()->timestamp) {
            session()->forget(['bulk_import_token', 'bulk_import_rows', 'bulk_import_session_id', 'bulk_import_expires_at']);
            return redirect()->route('admissions.bulk-import.index')
                ->withErrors(['token' => 'Import session expired (30 min limit). Please upload again.']);
        }

        $sessionId       = (int) session('bulk_import_session_id');
        $rows            = session('bulk_import_rows', []);

        // Verify session still belongs to institute
        $academicSession = AcademicSession::where('id', $sessionId)
            ->where('institute_id', $instituteId)
            ->firstOrFail();

        if (empty($rows)) {
            return redirect()->route('admissions.bulk-import.index')
                ->withErrors(['token' => 'No valid rows to import.']);
        }

        $year      = StudentIdService::getYearFromSession($academicSession->name);
        $imported  = 0;
        $failed    = 0;
        $lastError = null;

        // Load course parts for year-number lookup
        $coursePartsByYear = CoursePart::whereIn('course_id', array_unique(array_column($rows, 'course_id')))
            ->get()
            ->groupBy(fn($p) => $p->course_id . '|' . $p->year_number);

        DB::transaction(function () use (
            $rows, $instituteId, $sessionId, $year, $coursePartsByYear, &$imported, &$failed, &$lastError
        ) {
            foreach ($rows as $rowData) {
                try {
                    // Generate or use provided UID
                    $uid = $rowData['student_uid'] ?: null;
                    if (!$uid) {
                        $uid = StudentIdService::generateStudentId($instituteId, $year);
                    } elseif (Student::where('institute_id', $instituteId)->where('student_uid', $uid)->exists()) {
                        // Provided UID already taken — auto-generate instead
                        $uid = StudentIdService::generateStudentId($instituteId, $year);
                    }

                    // Resolve course part from semester
                    $yearNumber  = (int) ceil(($rowData['current_semester'] ?? 1) / 2);
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
                        'status'                   => 'active',
                        'is_quick_admission'       => false,
                    ]);

                    WalletService::onAdmission($student);

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
                            'subjects_json'             => [],
                            'form_no'                   => last(explode('/', $student->student_uid)),
                            'sr_no_snapshot'            => $student->sr_no,
                            'enrollment_no_snapshot'    => $student->enrollment_no,
                            'roll_no_snapshot'          => $student->roll_no,
                            'admission_source_snapshot' => $student->admission_source,
                            'source'                    => 'admission',
                            'admission_type'            => $student->admission_type ?? 'new',
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

        session()->forget(['bulk_import_token', 'bulk_import_rows', 'bulk_import_session_id', 'bulk_import_expires_at']);

        if ($imported === 0) {
            $errMsg = $lastError
                ? "Import failed. No students were saved. Error: {$lastError}"
                : "Import failed — no students were saved. Please try again or contact support.";
            return redirect()->route('admissions.bulk-import.index')->withErrors(['import' => $errMsg]);
        }

        $msg = "{$imported} student(s) imported successfully.";
        if ($failed > 0) $msg .= " {$failed} row(s) failed to save — check Laravel logs.";

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
