<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Mail\CenterCredentialsMail;
use App\Models\AcademicSession;
use App\Models\Center;
use App\Models\CenterFeeDiscountPermission;
use App\Models\CenterFeeCollectionPermission;
use App\Models\Course;
use App\Models\CourseType;
use App\Models\FeeType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class CenterController extends Controller
{
    private const PAY_MODES = ['cash', 'upi', 'online', 'cheque', 'dd', 'neft', 'rtgs'];

    private function instituteId(): int
    {
        return auth()->user()->institute_id;
    }

    private function formData(): array
    {
        $id = $this->instituteId();
        return [
            'courseTypes' => CourseType::forInstitute($id)->active()->orderBy('sort_order')->orderBy('name')->get(),
            'courses'   => Course::where('institute_id', $id)->where('status', true)
                              ->orderBy('name')->get(['id', 'name', 'course_type_id']),
            'sessions'  => AcademicSession::where('institute_id', $id)
                              ->orderByDesc('is_active')->orderByDesc('id')->get(['id', 'name', 'is_active']),
            'payModes'  => self::PAY_MODES,
            'feeTypes'  => FeeType::where('institute_id', $id)->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function index()
    {
        $centers = Center::where('institute_id', $this->instituteId())->orderBy('name')->get();
        return view('institute.master.centers.index', compact('centers'));
    }

    public function create()
    {
        return view('institute.master.centers.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:100',
            'code'                => 'nullable|string|max:20',
            'mobile'              => 'nullable|digits:10',
            'email'               => 'required|email|unique:centers,email',
            'city'                => 'nullable|string|max:50',
            'address'             => 'nullable|string|max:255',
            'state'               => 'nullable|string|max:50',
            'admission_form_type' => 'required|in:full,quick,both',
            'allowed_courses'     => 'nullable|array',
            'allowed_courses.*'   => 'integer|exists:courses,id',
            'student_scope'       => 'required|in:own,all',
            'fee_scope'           => 'required|in:own,all',
            'max_discount_pct'    => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            $plainPassword = Str::random(8);

            $center = Center::create([
                'institute_id'         => $this->instituteId(),
                'name'                 => strtoupper($request->name),
                'code'                 => $request->code ? strtoupper($request->code) : null,
                'mobile'               => $request->mobile,
                'email'                => $request->email,
                'password'             => Hash::make($plainPassword),
                'address'              => $request->address,
                'city'                 => $request->city,
                'state'                => $request->state,
                'status'               => true,
                // Feature flags
                'can_add_admission'    => $request->boolean('can_add_admission', true),
                'can_view_students'    => $request->boolean('can_view_students', true),
                'can_collect_fee'      => $request->boolean('can_collect_fee'),
                // Admission controls
                'admission_form_type'  => $request->admission_form_type,
                'allowed_courses'      => $request->filled('allowed_courses') ? $request->allowed_courses : null,
                'allowed_sessions'     => $this->parseSessionPerms($request),
                // Student & fee scope
                'student_scope'        => $request->student_scope,
                'fee_scope'            => $request->fee_scope,
                // Discount & fee restrictions
                'can_give_discount'             => $request->boolean('can_give_discount'),
                'max_discount_pct'              => $request->boolean('can_give_discount') ? ($request->max_discount_pct ?? 0) : 0,
                'can_waive_fee'                 => $request->boolean('can_waive_fee'),
                'restrict_fee_collection_types' => $request->boolean('restrict_fee_collection_types'),
                // Reports
                'can_download_reports' => $request->boolean('can_download_reports'),
            ]);

            $this->syncCenterFeeDiscountPermissions($center, $request);
            $this->syncCenterFeeCollectionPermissions($center, $request);

            $credentialsSent = $this->sendCredentialsEmail($center, $plainPassword);

            $message = "Center '{$request->name}' created successfully!";
            if ($credentialsSent) {
                $message .= " Login credentials sent to {$request->email}.";
            } else {
                $message .= " Email could not be sent. Share manually — Email: {$request->email} | Password: {$plainPassword}";
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => $message,
                    'redirect' => route('master.centers.index'),
                ]);
            }

            return redirect()->route('master.centers.index')->with('success', $message);
        } catch (Throwable $e) {
            report($e);
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function edit(Center $center)
    {
        abort_if($center->institute_id !== $this->instituteId(), 403);
        $center->load('feeDiscountPermissions', 'feeCollectionPermissions');
        return view('institute.master.centers.edit', array_merge(
            compact('center'),
            $this->formData()
        ));
    }

    public function update(Request $request, Center $center)
    {
        abort_if($center->institute_id !== $this->instituteId(), 403);

        $request->validate([
            'name'                => 'required|string|max:100',
            'mobile'              => 'nullable|digits:10',
            'admission_form_type' => 'required|in:full,quick,both',
            'allowed_courses'     => 'nullable|array',
            'allowed_courses.*'   => 'integer|exists:courses,id',
            'student_scope'       => 'required|in:own,all',
            'fee_scope'           => 'required|in:own,all',
            'max_discount_pct'    => 'nullable|numeric|min:0|max:100',
        ]);

        $data = [
            'name'                 => strtoupper($request->name),
            'code'                 => $request->code ? strtoupper($request->code) : null,
            'mobile'               => $request->mobile,
            'address'              => $request->address,
            'city'                 => $request->city,
            'state'                => $request->state,
            // Feature flags
            'can_add_admission'    => $request->boolean('can_add_admission'),
            'can_view_students'    => $request->boolean('can_view_students'),
            'can_collect_fee'      => $request->boolean('can_collect_fee'),
            // Admission controls
            'admission_form_type'  => $request->admission_form_type,
            'allowed_courses'      => $request->filled('allowed_courses') ? $request->allowed_courses : null,
            'allowed_sessions'     => $this->parseSessionPerms($request),
            // Student & fee scope
            'student_scope'        => $request->student_scope,
            'fee_scope'            => $request->fee_scope,
            // Discount & fee restrictions
            'can_give_discount'             => $request->boolean('can_give_discount'),
            'max_discount_pct'              => $request->boolean('can_give_discount') ? ($request->max_discount_pct ?? 0) : 0,
            'can_waive_fee'                 => $request->boolean('can_waive_fee'),
            'restrict_fee_collection_types' => $request->boolean('restrict_fee_collection_types'),
            // Reports
            'can_download_reports' => $request->boolean('can_download_reports'),
        ];

        $newPassword = null;
        if ($request->boolean('reset_password')) {
            $newPassword    = Str::random(8);
            $data['password'] = Hash::make($newPassword);
        }

        $center->update($data);

        $this->syncCenterFeeDiscountPermissions($center, $request);
        $this->syncCenterFeeCollectionPermissions($center, $request);

        $msg = 'Center updated successfully!';
        if ($newPassword) {
            $sent = $this->sendCredentialsEmail($center->fresh(), $newPassword);
            $msg .= $sent
                ? ' New password sent to the registered email.'
                : " New Password: {$newPassword}";
        }

        return redirect()->route('master.centers.index')->with('success', $msg);
    }

    public function destroy(Center $center)
    {
        abort_if($center->institute_id !== $this->instituteId(), 403);

        if ($center->students()->exists()) {
            $msg = "Cannot delete \"{$center->name}\" — students are linked to this center.";
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['delete' => $msg]);
        }

        try {
            $center->delete();
            if (request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => "Center \"{$center->name}\" deleted."]);
            }
            return redirect()->route('master.centers.index')->with('success', 'Center deleted!');
        } catch (Throwable $e) {
            $msg = 'Cannot delete this center — it may have linked data.';
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }
    }

    public function toggle(Center $center)
    {
        abort_if($center->institute_id !== $this->instituteId(), 403);
        $center->update(['status' => !$center->status]);
        return back()->with('success', 'Status updated!');
    }

    private function parseSessionPerms(Request $request): ?array
    {
        $input = $request->input('session_perms', []);
        if (empty($input)) return null;

        $result = [];
        foreach ($input as $sessionId => $perms) {
            if (!empty($perms['enabled'])) {
                $result[] = [
                    'id'            => (int) $sessionId,
                    'admission'     => !empty($perms['admission']),
                    'fee'           => !empty($perms['fee']),
                    'view'          => !empty($perms['view']),
                    'student_scope' => in_array($perms['student_scope'] ?? '', ['all']) ? 'all' : 'own',
                    'fee_scope'     => in_array($perms['fee_scope'] ?? '', ['all']) ? 'all' : 'own',
                ];
            }
        }

        return empty($result) ? null : $result;
    }

    private function syncCenterFeeDiscountPermissions(Center $center, Request $request): void
    {
        if (!$request->boolean('can_give_discount')) {
            $center->feeDiscountPermissions()->delete();
            return;
        }

        $submitted = $request->input('fee_discount_allowed', []);
        $allowedIds = collect($submitted)
            ->filter(fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN))
            ->keys()
            ->map('intval')
            ->filter(fn ($id) => $id > 0)
            ->all();

        $center->feeDiscountPermissions()->delete();
        foreach ($allowedIds as $feeTypeId) {
            CenterFeeDiscountPermission::create(['center_id' => $center->id, 'fee_type_id' => $feeTypeId]);
        }
    }

    private function syncCenterFeeCollectionPermissions(Center $center, Request $request): void
    {
        if (!$request->boolean('restrict_fee_collection_types')) {
            $center->feeCollectionPermissions()->delete();
            return;
        }

        $submitted = $request->input('fee_collection_allowed', []);
        $allowedIds = collect($submitted)
            ->filter(fn ($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN))
            ->keys()
            ->map('intval')
            ->filter(fn ($id) => $id > 0)
            ->all();

        $center->feeCollectionPermissions()->delete();
        foreach ($allowedIds as $feeTypeId) {
            CenterFeeCollectionPermission::create(['center_id' => $center->id, 'fee_type_id' => $feeTypeId]);
        }
    }

    private function sendCredentialsEmail(Center $center, string $plainPassword): bool
    {
        if (!$center->email) {
            return false;
        }

        try {
            $center->loadMissing('institute');
            Mail::to($center->email)->send(new CenterCredentialsMail($center, $plainPassword));
            return true;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }
}
