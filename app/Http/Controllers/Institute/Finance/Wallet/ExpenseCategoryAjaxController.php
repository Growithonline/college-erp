<?php

namespace App\Http\Controllers\Institute\Finance\Wallet;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategoryL2;
use App\Models\ExpenseVendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseCategoryAjaxController extends Controller
{
    private function instituteId(): int
    {
        if (Auth::guard('staff')->check()) {
            return (int) Auth::guard('staff')->user()->institute_id;
        }
        return (int) auth()->user()->institute_id;
    }

    public function subCategories(Request $request): JsonResponse
    {
        $l1Id = (int) $request->input('l1_id');

        $subs = ExpenseCategoryL2::where('institute_id', $this->instituteId())
            ->where('l1_id', $l1Id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($subs);
    }

    public function vendors(Request $request): JsonResponse
    {
        $l2Id = (int) $request->input('l2_id');

        $vendors = ExpenseVendor::where('institute_id', $this->instituteId())
            ->where('l2_id', $l2Id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'contact_phone']);

        return response()->json($vendors);
    }
}
