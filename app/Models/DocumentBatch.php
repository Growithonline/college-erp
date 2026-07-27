<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentBatch extends Model
{
    public static array $documentTypes = [
        'marksheet' => 'Marksheet',
        'degree'    => 'Degree Certificate',
    ];

    public static array $statuses = [
        'pending'    => 'Pending Dispatch',
        'dispatched' => 'Dispatched by University',
        'received'   => 'Received at Institute',
    ];

    protected $fillable = [
        'institute_id', 'academic_session_id', 'course_id', 'document_type',
        'batch_label', 'dispatch_date', 'dispatch_remarks',
        'received_date', 'received_count', 'remarks',
    ];

    protected $casts = [
        'dispatch_date'  => 'date',
        'received_date'  => 'date',
        'received_count' => 'integer',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function students()
    {
        return $this->hasMany(DocumentBatchStudent::class);
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return static::$documentTypes[$this->document_type] ?? ucfirst($this->document_type);
    }

    public function getStatusAttribute(): string
    {
        if ($this->received_date) {
            return 'received';
        }

        if ($this->dispatch_date) {
            return 'dispatched';
        }

        return 'pending';
    }

    public function getStatusLabelAttribute(): string
    {
        return static::$statuses[$this->status] ?? ucfirst($this->status);
    }
}
