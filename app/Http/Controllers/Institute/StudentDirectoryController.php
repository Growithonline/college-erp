<?php

namespace App\Http\Controllers\Institute;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Center;
use App\Models\ChannelPartner;
use App\Models\Course;
use App\Models\CourseStream;
use App\Models\CourseType;
use App\Models\Institute;
use App\Models\Student;
use App\Traits\ExportsTabularData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StudentDirectoryController extends Controller
{
    use ExportsTabularData;
    private function instituteId(): int
    {
        return (int) Auth::user()->institute_id;
    }

    public function index(Request $request)
    {
        return $this->listStudents($request, false);
    }

    public function quickAdmissions(Request $request)
    {
        return $this->listStudents($request, true);
    }

    public function export(Request $request)
    {
        $type = $request->input('export', 'pdf');
        if (!in_array($type, ['pdf', 'csv', 'excel'])) {
            abort(400, 'Invalid export type.');
        }

        $instituteId   = $this->instituteId();
        $institute     = Institute::findOrFail($instituteId);
        $activeSession = AcademicSession::viewSession($instituteId);
        $sessionId     = $request->filled('session_id') ? (int) $request->session_id : $activeSession?->id;
        $sessionObj    = $sessionId ? AcademicSession::find($sessionId) : null;

        $query = Student::where('institute_id', $instituteId)
            ->with(['stream.course', 'session', 'coursePart', 'admittedBy'])
            ->orderByDesc('id');

        if ($request->boolean('quick_only')) {
            $query->where('is_quick_admission', true);
        }
        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($b) use ($s) {
                $b->where('name', 'like', "%{$s}%")
                  ->orWhere('mobile', 'like', "%{$s}%")
                  ->orWhere('father_name', 'like', "%{$s}%")
                  ->orWhere('mother_name', 'like', "%{$s}%")
                  ->orWhere('student_uid', 'like', "%{$s}%");
            });
        }
        if ($request->filled('course_type_id')) {
            $query->where('course_type_id', (int) $request->course_type_id);
        }
        if ($request->filled('course_id')) {
            $query->whereHas('stream', fn($q) => $q->where('course_id', (int) $request->course_id));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('admission_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('admission_date', '<=', $request->to_date);
        }

        $students = $query->get();

        $centerIds   = $students->where('admission_source', 'center')->pluck('admission_source_id')->filter()->unique();
        $partnerIds  = $students->whereIn('admission_source', ['partner', 'channel_partner'])->pluck('admission_source_id')->filter()->unique();
        $centersMap  = $centerIds->isNotEmpty()  ? Center::whereIn('id', $centerIds)->pluck('name', 'id')  : collect();
        $partnersMap = $partnerIds->isNotEmpty() ? ChannelPartner::whereIn('id', $partnerIds)->pluck('name', 'id') : collect();

        if ($type === 'pdf') {
            return view('institute.students.export-pdf', compact(
                'students', 'institute', 'sessionObj', 'centersMap', 'partnersMap'
            ));
        }

        $expHeaders = ['#', 'Session', 'Student ID', 'Student Name', 'Father Name', 'Mother Name',
                       'Roll No', 'Enroll No', 'UIN No', 'Course', 'Stream', 'Year/Sem',
                       'Admitted By', 'Source', 'Adm. Date', 'Status'];

        $expRows = $students->map(function ($student, $i) use ($centersMap, $partnersMap) {
            $source = match($student->admission_source) {
                'center'  => ($centersMap[$student->admission_source_id] ?? null)
                                ? 'Center: ' . $centersMap[$student->admission_source_id] : 'Center',
                'partner', 'channel_partner' => ($partnersMap[$student->admission_source_id] ?? null)
                                ? 'Partner: ' . $partnersMap[$student->admission_source_id] : 'Partner',
                default   => 'Direct',
            };
            return [
                $i + 1,
                $student->session?->name ?? '—',
                $student->student_uid ?? '—',
                $student->name,
                $student->father_name ?: '—',
                $student->mother_name ?: '—',
                $student->roll_no ?: '—',
                $student->enrollment_no ?: '—',
                $student->uin_no ?: '—',
                $student->stream?->course?->name ?? '—',
                $student->stream?->name ?? '—',
                ($student->coursePart?->year_label ?? '—') . ($student->current_semester ? ' S' . $student->current_semester : ''),
                $student->admittedBy?->name ?? match($student->admission_source) {
                    'center'                     => ($centersMap[$student->admission_source_id] ?? null) ? 'Center: ' . $centersMap[$student->admission_source_id] : 'Center',
                    'partner', 'channel_partner' => ($partnersMap[$student->admission_source_id] ?? null) ? 'Partner: ' . $partnersMap[$student->admission_source_id] : 'Partner',
                    default                      => 'Admin / Direct',
                },
                $source,
                $student->admission_date?->format('d/m/Y') ?? '—',
                ucfirst($student->status ?? 'pending'),
            ];
        })->toArray();

        $sessName = $sessionObj?->name ?? 'All Sessions';
        $title    = $institute->name . ' — Student List';
        $subtitle = 'Session: ' . $sessName . '   |   Total: ' . count($expRows) . '   |   Generated: ' . now()->setTimezone('Asia/Kolkata')->format('d M Y h:i A');
        $filename = 'students-' . now()->format('Ymd-His');

        if ($type === 'excel') {
            return $this->exportSimpleExcel($expHeaders, $expRows, $filename . '.xlsx', $title, $subtitle);
        }

        return $this->exportCsv($expHeaders, $expRows, $filename . '.csv');
    }

    private function listStudents(Request $request, bool $quickOnly)
    {
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->first();

        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('id')
            ->get();

        $courseTypes = CourseType::where('institute_id', $instituteId)
            ->orderBy('name')->get();

        $courses = Course::where('institute_id', $instituteId)
            ->orderBy('name')->get();

        $streams = CourseStream::whereHas('course', fn($q) => $q->where('institute_id', $instituteId))
            ->with('course')
            ->orderBy('name')
            ->get();

        $sessionId = $request->filled('session_id') ? (int) $request->session_id : $activeSession?->id;

        $query = Student::where('institute_id', $instituteId)
            ->with(['stream.course', 'session', 'coursePart', 'admittedBy'])
            ->orderByDesc('id');

        if ($quickOnly) {
            $query->where('is_quick_admission', true);
        }

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($b) use ($s) {
                $b->where('name', 'like', "%{$s}%")
                  ->orWhere('mobile', 'like', "%{$s}%")
                  ->orWhere('father_name', 'like', "%{$s}%")
                  ->orWhere('mother_name', 'like', "%{$s}%")
                  ->orWhere('student_uid', 'like', "%{$s}%");
            });
        }

        if ($request->filled('course_type_id')) {
            $query->where('course_type_id', (int) $request->course_type_id);
        }

        if ($request->filled('course_id')) {
            $query->whereHas('stream', fn($q) => $q->where('course_id', (int) $request->course_id));
        }

        if ($request->filled('course_stream_id')) {
            $query->where('course_stream_id', (int) $request->course_stream_id);
        }

        if ($request->filled('current_semester')) {
            $query->where('current_semester', (int) $request->current_semester);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('admission_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('admission_date', '<=', $request->to_date);
        }

        $students = $query->paginate(50)->withQueryString();

        // Eager-load guide (center/partner) names in one pass to avoid N+1
        $centerIds  = $students->where('admission_source', 'center')->pluck('admission_source_id')->filter()->unique();
        $partnerIds = $students->whereIn('admission_source', ['partner', 'channel_partner'])->pluck('admission_source_id')->filter()->unique();
        $centersMap  = $centerIds->isNotEmpty()  ? Center::whereIn('id', $centerIds)->pluck('name', 'id')  : collect();
        $partnersMap = $partnerIds->isNotEmpty() ? ChannelPartner::whereIn('id', $partnerIds)->pluck('name', 'id') : collect();

        return view('institute.students.index', compact(
            'students', 'activeSession', 'sessions', 'courseTypes', 'courses', 'streams',
            'sessionId', 'quickOnly', 'centersMap', 'partnersMap'
        ));
    }

    public function search(Request $request)
    {
        $instituteId = $this->instituteId();
        $sessions = AcademicSession::where('institute_id', $instituteId)
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        $filters = $this->globalSearchFilters($request);
        $sessionId = $request->filled('session_id') ? (int) $request->session_id : null;

        $isInitialLoad = false;
        $students      = null;

        if ($this->hasGlobalSearchFilters($filters)) {
            $students = $this->buildGlobalStudentSearchQuery($instituteId, $filters, $sessionId)
                ->paginate(15)
                ->withQueryString();
        } else {
            $isInitialLoad = true;
            $students = Student::where('institute_id', $instituteId)
                ->with(['stream.course', 'session', 'coursePart'])
                ->when($sessionId, fn($q) => $q->where('academic_session_id', $sessionId))
                ->orderByDesc('id')
                ->limit(50)
                ->get();
        }

        $viewData = array_merge(
            compact('sessions', 'students', 'filters', 'sessionId', 'isInitialLoad'),
            [
                'layout'               => 'institute.layout',
                'indexRoute'           => 'students.index',
                'searchRoute'          => 'students.search',
                'profileRoute'         => 'admissions.show',
                'walletRoute'          => 'fee.wallet.student',
                'historyRoute'         => 'fee.student-history',
                'collectFeeRoute'      => 'fee.create',
                'showWalletAction'     => true,
                'showHistoryAction'    => true,
                'showCollectFeeAction' => true,
                'listLabel'            => 'All Students',
            ]
        );

        // AJAX live-search: return only the results partial
        if ($request->ajax() || $request->boolean('_ajax')) {
            return view('institute.students._global-search-results', $viewData);
        }

        return view('institute.students.global-search', $viewData);
    }

    public function wallet()
    {
        return view('institute.students.search', [
            'mode'  => 'wallet',
            'title' => 'Student Wallet',
            'icon'  => 'bi-wallet2',
            'desc'  => 'Search a student to open their wallet',
        ]);
    }

    public function feeHistory()
    {
        return view('institute.students.search', [
            'mode'  => 'history',
            'title' => 'Fee History',
            'icon'  => 'bi-receipt',
            'desc'  => 'Search a student to view their fee history',
        ]);
    }

    public function ajaxSearch(Request $request)
    {
        $instituteId   = $this->instituteId();
        $activeSession = AcademicSession::where('institute_id', $instituteId)
            ->where('is_active', true)
            ->first();

        $q = trim((string) $request->q);

        $students = Student::where('institute_id', $instituteId)
            ->when($activeSession, fn($b) => $b->where('academic_session_id', $activeSession->id))
            ->when($q !== '', fn($b) => $this->applyStudentSearch($b, $q))
            ->with('stream.course')
            ->limit(10)
            ->get();

        return response()->json($students->map(fn($s) => [
            'id'          => $s->id,
            'name'        => $s->name,
            'student_uid' => $s->student_uid,
            'mobile'      => $s->mobile,
            'father_name' => $s->father_name,
            'mother_name' => $s->mother_name,
            'course'      => $s->stream->course->name ?? '',
            'stream'      => $s->stream->name ?? '',
        ]));
    }

    private function buildGlobalStudentSearchQuery(int $instituteId, array $filters, ?int $sessionId = null)
    {
        $query = Student::where('institute_id', $instituteId)
            ->with(['stream.course', 'session', 'coursePart'])
            ->orderBy('name');

        if ($sessionId) {
            $query->where('academic_session_id', $sessionId);
        }

        $this->applyStudentSearchFilters($query, $filters);

        return $query;
    }

    private function applyStudentSearch($query, string $searchText): void
    {
        $query->where(function ($builder) use ($searchText) {
            $builder->where('name', 'like', "%{$searchText}%")
                ->orWhere('father_name', 'like', "%{$searchText}%")
                ->orWhere('mother_name', 'like', "%{$searchText}%")
                ->orWhere('mobile', 'like', "%{$searchText}%")
                ->orWhere('email', 'like', "%{$searchText}%")
                ->orWhere('student_uid', 'like', "%{$searchText}%")
                ->orWhere('roll_no', 'like', "%{$searchText}%")
                ->orWhere('enrollment_no', 'like', "%{$searchText}%")
                ->orWhereHas('academicIdentities', function ($identityQuery) use ($searchText) {
                    $identityQuery->where(function ($identityBuilder) use ($searchText) {
                        $identityBuilder->where('roll_no', 'like', "%{$searchText}%")
                            ->orWhere('form_no', 'like', "%{$searchText}%")
                            ->orWhere('roll_no_snapshot', 'like', "%{$searchText}%")
                            ->orWhere('enrollment_no_snapshot', 'like', "%{$searchText}%");
                    });
                });
        });
    }

    private function globalSearchFilters(Request $request): array
    {
        return [
            'student_name' => trim((string) $request->input('student_name', '')),
            'father_name' => trim((string) $request->input('father_name', '')),
            'mother_name' => trim((string) $request->input('mother_name', '')),
            'mobile' => trim((string) $request->input('mobile', '')),
            'email' => trim((string) $request->input('email', '')),
            'student_id' => trim((string) $request->input('student_id', '')),
            'roll_no' => trim((string) $request->input('roll_no', '')),
            'enrollment_no' => trim((string) $request->input('enrollment_no', '')),
            'uin_no' => trim((string) $request->input('uin_no', '')),
        ];
    }

    private function hasGlobalSearchFilters(array $filters): bool
    {
        foreach ($filters as $value) {
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    private function applyStudentSearchFilters($query, array $filters): void
    {
        // Multi-word search: each word must appear somewhere in the name (any order)
        if ($filters['student_name'] !== '') {
            $words = array_filter(preg_split('/\s+/', trim($filters['student_name'])));
            foreach ($words as $word) {
                $query->where('name', 'like', '%' . $word . '%');
            }
        }

        if ($filters['father_name'] !== '') {
            $words = array_filter(preg_split('/\s+/', trim($filters['father_name'])));
            foreach ($words as $word) {
                $query->where('father_name', 'like', '%' . $word . '%');
            }
        }

        if ($filters['mother_name'] !== '') {
            $words = array_filter(preg_split('/\s+/', trim($filters['mother_name'])));
            foreach ($words as $word) {
                $query->where('mother_name', 'like', '%' . $word . '%');
            }
        }

        if ($filters['mobile'] !== '') {
            $query->where('mobile', 'like', '%' . $filters['mobile'] . '%');
        }

        if ($filters['email'] !== '') {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }

        if ($filters['student_id'] !== '') {
            $query->where('student_uid', 'like', '%' . $filters['student_id'] . '%');
        }

        if ($filters['roll_no'] !== '') {
            $searchText = $filters['roll_no'];
            $query->where(function ($builder) use ($searchText) {
                $builder->where('roll_no', 'like', "%{$searchText}%")
                    ->orWhereHas('academicIdentities', function ($identityQuery) use ($searchText) {
                        $identityQuery->where(function ($identityBuilder) use ($searchText) {
                            $identityBuilder->where('roll_no', 'like', "%{$searchText}%")
                                ->orWhere('roll_no_snapshot', 'like', "%{$searchText}%");
                        });
                    });
            });
        }

        if ($filters['enrollment_no'] !== '') {
            $searchText = $filters['enrollment_no'];
            $query->where(function ($builder) use ($searchText) {
                $builder->where('enrollment_no', 'like', "%{$searchText}%")
                    ->orWhereHas('academicIdentities', function ($identityQuery) use ($searchText) {
                        $identityQuery->where('enrollment_no_snapshot', 'like', "%{$searchText}%");
                    });
            });
        }

        if (isset($filters['uin_no']) && $filters['uin_no'] !== '') {
            $searchText = $filters['uin_no'];
            $query->where(function ($builder) use ($searchText) {
                $builder->where('uin_no', 'like', "%{$searchText}%")
                    ->orWhereHas('academicIdentities', function ($identityQuery) use ($searchText) {
                        $identityQuery->where('uin_no_snapshot', 'like', "%{$searchText}%");
                    });
            });
        }
    }
}
