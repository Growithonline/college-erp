<?php

namespace App\Http\Controllers\Institute\Master;

use App\Http\Controllers\Controller;
use App\Models\CourseStreamSubject;
use App\Models\Subject;
use App\Models\SubjectComponent;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::with('components')
            ->where('institute_id', auth()->user()->institute_id)
            ->orderBy('name')->get();
        return view('institute.master.subjects.index', compact('subjects'));
    }

    public function create()
    {
        return view('institute.master.subjects.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'code'          => 'nullable|string|max:20',
            'credit'        => 'nullable|integer|min:0',
            'has_practical' => 'boolean',
            'theory_max_marks'    => 'required|integer|min:1',
            'theory_pass_marks'   => 'required|integer|min:1',
            'practical_max_marks' => 'required_if:has_practical,1|nullable|integer|min:1',
            'practical_pass_marks'=> 'required_if:has_practical,1|nullable|integer|min:1',
        ]);

        $instituteId = auth()->user()->institute_id;

        $name = strtoupper($request->name);
        if (Subject::where('institute_id', $instituteId)->where('name', $name)->exists()) {
            return back()->withInput()->withErrors(['name' => 'Subject already exists.']);
        }

        $subject = Subject::create([
            'institute_id'  => $instituteId,
            'name'          => $name,
            'code'          => $request->code ? strtoupper($request->code) : null,
            'credit'        => $request->credit,
            'has_practical' => $request->boolean('has_practical'),
            'status'        => true,
        ]);

        // Auto-create components
        SubjectComponent::create([
            'subject_id'     => $subject->id,
            'component_type' => 'theory',
            'max_marks'      => $request->theory_max_marks,
            'pass_marks'     => $request->theory_pass_marks,
        ]);

        if ($request->boolean('has_practical')) {
            SubjectComponent::create([
                'subject_id'     => $subject->id,
                'component_type' => 'practical',
                'max_marks'      => $request->practical_max_marks,
                'pass_marks'     => $request->practical_pass_marks,
            ]);
        }

        return redirect()->route('master.subjects.index')
            ->with('success', "Subject '{$subject->name}' created successfully!");
    }

    public function edit(Subject $subject)
    {
        $this->authorizeSubject($subject);
        $subject->load('components');
        return view('institute.master.subjects.edit', compact('subject'));
    }

    public function update(Request $request, Subject $subject)
    {
        $this->authorizeSubject($subject);

        $request->validate([
            'name'          => 'required|string|max:100',
            'code'          => 'nullable|string|max:20',
            'credit'        => 'nullable|integer|min:0',
            'theory_max_marks'    => 'required|integer|min:1',
            'theory_pass_marks'   => 'required|integer|min:1',
            'practical_max_marks' => 'required_if:has_practical,1|nullable|integer|min:1',
            'practical_pass_marks'=> 'required_if:has_practical,1|nullable|integer|min:1',
        ]);

        $hasPractical = $request->boolean('has_practical');

        $subject->update([
            'name'          => strtoupper($request->name),
            'code'          => $request->code ? strtoupper($request->code) : null,
            'credit'        => $request->credit,
            'has_practical' => $hasPractical,
        ]);

        // Update theory
        SubjectComponent::updateOrCreate(
            ['subject_id' => $subject->id, 'component_type' => 'theory'],
            ['max_marks' => $request->theory_max_marks, 'pass_marks' => $request->theory_pass_marks]
        );

        // Update or create practical
        if ($hasPractical) {
            SubjectComponent::updateOrCreate(
                ['subject_id' => $subject->id, 'component_type' => 'practical'],
                ['max_marks' => $request->practical_max_marks, 'pass_marks' => $request->practical_pass_marks]
            );
        } else {
            SubjectComponent::where('subject_id', $subject->id)
                ->where('component_type', 'practical')->delete();
        }

        return redirect()->route('master.subjects.index')
            ->with('success', 'Subject updated successfully!');
    }

    public function destroy(Subject $subject)
    {
        $this->authorizeSubject($subject);

        if (CourseStreamSubject::where('subject_id', $subject->id)->exists()) {
            return back()->withErrors(['delete' => "Cannot delete \"{$subject->name}\" — it is mapped to one or more course streams."]);
        }

        $subject->components()->delete();
        $subject->delete();
        return redirect()->route('master.subjects.index')
            ->with('success', 'Subject deleted!');
    }

    public function toggleStatus(Subject $subject)
    {
        $this->authorizeSubject($subject);
        $subject->update(['status' => !$subject->status]);
        return back()->with('success', 'Status updated!');
    }

    private function authorizeSubject(Subject $subject): void
    {
        abort_if($subject->institute_id !== auth()->user()->institute_id, 403);
    }
}
