<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    protected $fillable = ['institute_id', 'name', 'status'];

    protected $casts = ['status' => 'boolean'];

    public function institute()     { return $this->belongsTo(Institute::class); }
    public function documentTypes() { return $this->hasMany(DocumentType::class); }

    public function scopeActive($q)             { return $q->where('status', true); }
    public function scopeForInstitute($q, $id)  { return $q->where('institute_id', $id); }
}
