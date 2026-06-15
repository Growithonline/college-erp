<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $fillable = [
        'institute_id', 'document_category_id',
        'name', 'max_size_kb', 'allowed_formats', 'status',
    ];

    protected $casts = ['status' => 'boolean'];

    public function institute()        { return $this->belongsTo(Institute::class); }
    public function category()         { return $this->belongsTo(DocumentCategory::class, 'document_category_id'); }
    public function uploadRules()      { return $this->hasMany(DocumentUploadRule::class); }
    public function admissionDocs()    { return $this->hasMany(AdmissionDocument::class); }

    public function scopeActive($q)             { return $q->where('status', true); }
    public function scopeForInstitute($q, $id)  { return $q->where('institute_id', $id); }

    public function getAllowedFormatsArrayAttribute(): array
    {
        return array_map('trim', explode(',', $this->allowed_formats));
    }

    public function getMaxSizeBytesAttribute(): int
    {
        return $this->max_size_kb * 1024;
    }
}
