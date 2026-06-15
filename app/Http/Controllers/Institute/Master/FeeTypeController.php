<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\FeeType;
use Illuminate\Http\Request;

class FeeTypeController extends Controller
{
    private const CATEGORIES = [
        'registration'      => 'Registration Fee',
        'course'            => 'Course Fee',
        'subject_theory'    => 'Subject Theory Fee',
        'subject_practical' => 'Subject Practical Fee',
        'exam'              => 'Exam Fee',
        'practical_exam'    => 'Practical Exam Fee',
        'transport'         => 'Transport Fee',
        'library'           => 'Library Fee',
        'maintenance'       => 'Maintenance Fee',
        'computer'          => 'Computer Fee',
        'fine'              => 'Fine',
        'discount'          => 'Discount',
        'certification'     => 'Certification Fee',
        'other'             => 'Other',
    ];

    public function index()
    {
        $feeTypes = FeeType::where('institute_id', auth()->user()->institute_id)
            ->orderBy('sort_order')->orderBy('name')->get();
        $categories = self::CATEGORIES;
        return view('institute.master.fee.types.index', compact('feeTypes', 'categories'));
    }

    public function create()
    {
        $categories = self::CATEGORIES;
        return view('institute.master.fee.types.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'category'    => 'required|in:' . implode(',', array_keys(self::CATEGORIES)),
            'description' => 'nullable|string|max:255',
        ]);

        FeeType::create([
            'institute_id' => auth()->user()->institute_id,
            'name'         => strtoupper($request->name),
            'category'     => $request->category,
            'description'  => $request->description,
            'is_active'    => true,
            'sort_order'   => FeeType::where('institute_id', auth()->user()->institute_id)->max('sort_order') + 1,
        ]);

        return redirect()->route('master.fee-types.index')
            ->with('success', 'Fee type created!');
    }

    public function edit(FeeType $feeType)
    {
        abort_if($feeType->institute_id !== auth()->user()->institute_id, 403);
        $categories = self::CATEGORIES;
        return view('institute.master.fee.types.edit', compact('feeType', 'categories'));
    }

    public function update(Request $request, FeeType $feeType)
    {
        abort_if($feeType->institute_id !== auth()->user()->institute_id, 403);
        $request->validate([
            'name'        => 'required|string|max:100',
            'category'    => 'required|in:' . implode(',', array_keys(self::CATEGORIES)),
            'description' => 'nullable|string|max:255',
        ]);
        $feeType->update([
            'name'        => strtoupper($request->name),
            'category'    => $request->category,
            'description' => $request->description,
        ]);
        return redirect()->route('master.fee-types.index')->with('success', 'Fee type updated!');
    }

    public function destroy(FeeType $feeType)
    {
        abort_if($feeType->institute_id !== auth()->user()->institute_id, 403);
        abort_if($feeType->is_system, 403, 'System fee types cannot be deleted.');
        $feeType->delete();
        return redirect()->route('master.fee-types.index')->with('success', 'Deleted!');
    }

    public function toggle(FeeType $feeType)
    {
        abort_if($feeType->institute_id !== auth()->user()->institute_id, 403);
        $feeType->update(['is_active' => !$feeType->is_active]);
        return back()->with('success', 'Status updated!');
    }
}
