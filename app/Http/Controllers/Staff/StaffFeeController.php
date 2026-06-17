<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Institute\Fee\FeeCollectionController as InstituteFeeController;
use App\Http\Controllers\Institute\Fee\WalletController as InstituteWalletController;
use App\Models\FeeInvoice;
use App\Models\AcademicSession;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffFeeController extends Controller
{
    private function staff()
    {
        return Auth::guard('staff')->user();
    }

    private function ensureFeeCollectionPermission(): void
    {
        if (!$this->staff()->canCollectFee()) {
            abort(403, 'Fee collection permission required.');
        }
    }

    private function ensureFeeViewPermission(): void
    {
        if (!$this->staff()->canViewFeeHistory()) {
            abort(403, 'Fee view permission required.');
        }
    }

    private function ensureFeeWalletPermission(): void
    {
        if (!$this->staff()->canViewFeeWallet()) {
            abort(403, 'Fee wallet permission required.');
        }
    }

    private function ensureFeeCancelPermission(): void
    {
        if (!$this->staff()->canCancelFee()) {
            abort(403, 'Fee cancel permission required.');
        }
    }

    public function create(Request $request)
    {
        $this->ensureFeeCollectionPermission();
        // Delegate to institute fee controller
        return app(InstituteFeeController::class)->create($request);
    }

    public function store(Request $request)
    {
        $this->ensureFeeCollectionPermission();
        return app(InstituteFeeController::class)->store($request);
    }

    public function searchStudent(Request $request)
    {
        $this->ensureFeeCollectionPermission();

        return app(InstituteFeeController::class)->searchStudent($request);
    }

    public function receipt(Student $student, FeeInvoice $invoice)
    {
        $this->ensureFeeViewPermission();

        return app(InstituteFeeController::class)->receipt($student, $invoice);
    }

    public function index(Request $request)
    {
        $this->ensureFeeViewPermission();
        return app(InstituteFeeController::class)->index($request);
    }

    public function studentHistory(Student $student)
    {
        $this->ensureFeeViewPermission();
        return app(InstituteFeeController::class)->studentHistory($student);
    }

    public function studentWallet(Student $student, Request $request)
    {
        $this->ensureFeeWalletPermission();
        return app(InstituteWalletController::class)->studentWallet($student, $request);
    }

    public function export(Request $request)
    {
        $this->ensureFeeViewPermission();
        return app(InstituteFeeController::class)->export($request);
    }

    public function cancel(Request $request, Student $student, FeeInvoice $invoice)
    {
        $this->ensureFeeCancelPermission();
        return app(InstituteFeeController::class)->cancel($request, $student, $invoice);
    }
}
