<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAuthInstitute;
use Illuminate\Database\Eloquent\Model;

class InstituteWallet extends Model
{
    use BelongsToAuthInstitute;

    protected $fillable = ['institute_id', 'academic_session_id', 'main_b'];

    protected $casts = ['main_b' => 'decimal:2'];

    public function institute() { return $this->belongsTo(Institute::class); }
    public function session()   { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }
}