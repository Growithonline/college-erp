<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseFeeRule extends Model
{
    protected $fillable = [
        'institute_id', 'academic_session_id', 'course_id', 'fee_type_id',
        'course_part', 'semester',
        'student_type', 'admission_source', 'category', 'gender',
        'amount', 'is_active', 'remarks',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function course()   { return $this->belongsTo(Course::class); }
    public function feeType()  { return $this->belongsTo(FeeType::class); }
    public function session()  { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }
    public function institute(){ return $this->belongsTo(Institute::class); }

    // ── Labels ───────────────────────────────────────────────────────────
    public function getStudentTypeLabelAttribute(): string
    {
        if ($this->student_type === 'all') return 'All';

        // Master se naam lo; fallback: slug ko readable karo
        $name = \App\Models\StudentType::where('institute_id', $this->institute_id)
            ->where('slug', $this->student_type)
            ->value('name');

        return $name ?? ucwords(str_replace('_', ' ', $this->student_type));
    }

    public function getAdmissionSourceLabelAttribute(): string
    {
        return match($this->admission_source) {
            'direct'          => 'Direct',
            'center'          => 'Center',
            'channel_partner' => 'Channel Partner',
            default           => 'All Sources',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'general' => 'General',
            'obc'     => 'OBC',
            'sc'      => 'SC',
            'st'      => 'ST',
            default   => 'All Categories',
        };
    }
}
