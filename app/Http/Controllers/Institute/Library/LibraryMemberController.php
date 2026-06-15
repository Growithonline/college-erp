<?php

namespace App\Http\Controllers\Institute\Library;

use App\Models\Library\LibraryMember;
use App\Models\Library\LibraryRuleSet;
use App\Models\StaffMember;
use App\Models\Student;
use App\Services\LibraryManagementService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LibraryMemberController extends BaseLibraryController
{
    public function index(Request $request)
    {
        $this->ensureLibraryPermission('members');
        $instituteId = $this->instituteId();
        $search = trim((string) $request->input('search', ''));

        $members = LibraryMember::forInstitute($instituteId)
            ->with(['student.stream.course', 'staffMember.role', 'ruleSet', 'activeTransactions.copy.book', 'transactions'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('member_code', 'like', '%' . $search . '%')
                        ->orWhere('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $ruleSets = LibraryRuleSet::forInstitute($instituteId)->where('is_active', true)->orderBy('name')->get();

        $stats = [
            'student_members' => LibraryMember::forInstitute($instituteId)->where('member_type', 'student')->count(),
            'staff_members' => LibraryMember::forInstitute($instituteId)->whereIn('member_type', ['staff', 'faculty'])->count(),
            'blocked_members' => LibraryMember::forInstitute($instituteId)->where('status', 'blocked')->count(),
            'active_issues' => \App\Models\Library\LibraryTransaction::forInstitute($instituteId)->where('current_status', 'issued')->count(),
        ];

        return view('institute.library.members.index', compact('members', 'ruleSets', 'search', 'stats'));
    }

    public function syncStudents()
    {
        $this->ensureLibraryPermission('members');
        $instituteId = $this->instituteId();
        $ruleSetId = $this->defaultRuleSetId('student');
        $created = 0;
        $updated = 0;

        Student::where('institute_id', $instituteId)
            ->where('status', '!=', 'pending')
            ->chunkById(200, function ($students) use (&$created, &$updated, $ruleSetId) {
                foreach ($students as $student) {
                    $member = LibraryManagementService::syncStudentMember($student);
                    if ($member) {
                        if ($member->wasRecentlyCreated) {
                            $created++;
                        } else {
                            $updated++;
                        }
                        if (!$member->rule_set_id && $ruleSetId) {
                            $member->update(['rule_set_id' => $ruleSetId]);
                        }
                    }
                }
            });

        return back()->with('success', $created . ' new + ' . $updated . ' updated — student members synced successfully.');
    }

    public function syncStaff()
    {
        $this->ensureLibraryPermission('members');
        $instituteId = $this->instituteId();
        $created = 0;

        StaffMember::where('institute_id', $instituteId)
            ->with('role')
            ->chunkById(100, function ($staffMembers) use (&$created, $instituteId) {
                foreach ($staffMembers as $staffMember) {
                    LibraryManagementService::syncStaffMember($staffMember);
                    $created++;
                }
            });

        return back()->with('success', $created . ' staff members sync ho gaye.');
    }

    public function update(Request $request, LibraryMember $member)
    {
        $this->ensureLibraryPermission('members');
        abort_if($member->institute_id !== $this->instituteId(), 403);

        $instituteId = $this->instituteId();

        $data = $request->validate([
            'rule_set_id' => [
                'nullable',
                'integer',
                Rule::exists('library_rule_sets', 'id')->where('institute_id', $instituteId),
            ],
            'status' => 'required|in:active,blocked,inactive',
            'blocked_reason' => 'nullable|string|max:255',
        ]);

        if ($data['status'] !== 'blocked') {
            $data['blocked_reason'] = null;
        }

        $member->update($data);

        return back()->with('success', 'Member status update ho gaya.');
    }
}
