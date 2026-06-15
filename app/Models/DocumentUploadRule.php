<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentUploadRule extends Model
{
    protected $fillable = [
        'institute_id', 'course_id', 'document_type_id', 'user_type', 'requirement',
    ];

    public const USER_TYPES = ['online', 'center', 'partner', 'staff'];
    public const USER_TYPE_LABELS = [
        'online'  => 'Online Admission',
        'center'  => 'Center Admission',
        'partner' => 'Channel Partner',
        'staff'   => 'Staff/Admin',
    ];

    public function institute()    { return $this->belongsTo(Institute::class); }
    public function course()       { return $this->belongsTo(Course::class); }
    public function documentType() { return $this->belongsTo(DocumentType::class); }

    public function isRequired(): bool  { return $this->requirement === 'required'; }
    public function isOptional(): bool  { return $this->requirement === 'optional'; }
    public function isSkipped(): bool   { return $this->requirement === 'skip'; }

    // Returns rules for a course + user_type as [document_type_id => requirement]
    public static function rulesFor(int $courseId, string $userType): array
    {
        return static::where('course_id', $courseId)
            ->where('user_type', $userType)
            ->pluck('requirement', 'document_type_id')
            ->all();
    }
}
