<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class AdmissionDocument extends Model
{
    protected $fillable = [
        'institute_id', 'student_id', 'document_type_id',
        'file_path', 'original_name', 'mime_type', 'file_size_kb',
        'uploaded_by_type', 'uploaded_by_id',
        'verification_status', 'verified_by', 'verified_at', 'rejection_reason',
    ];

    protected $casts = ['verified_at' => 'datetime'];

    public function institute()    { return $this->belongsTo(Institute::class); }
    public function student()      { return $this->belongsTo(Student::class); }
    public function documentType() { return $this->belongsTo(DocumentType::class); }

    public function verifiedByStaff()
    {
        return $this->belongsTo(StaffMember::class, 'verified_by');
    }

    public function isPending(): bool  { return $this->verification_status === 'pending'; }
    public function isApproved(): bool { return $this->verification_status === 'approved'; }
    public function isRejected(): bool { return $this->verification_status === 'rejected'; }

    public function getFileUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function isImage(): bool
    {
        return in_array(strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    public function isPdf(): bool
    {
        return strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function scopePending($q)    { return $q->where('verification_status', 'pending'); }
    public function scopeApproved($q)   { return $q->where('verification_status', 'approved'); }
    public function scopeRejected($q)   { return $q->where('verification_status', 'rejected'); }
}
