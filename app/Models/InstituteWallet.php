<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstituteWallet extends Model
{
    protected $fillable = ['institute_id', 'academic_session_id', 'main_b'];

    public function institute() { return $this->belongsTo(Institute::class); }
    public function session()   { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }
}