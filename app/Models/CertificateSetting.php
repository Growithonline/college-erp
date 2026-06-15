<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateSetting extends Model
{
    protected $fillable = [
        'institute_id',
        'header_line1',
        'header_line2',
        'header_line3',
        'logo',
        'seal_image',
        'principal_name',
        'principal_designation',
        'principal_signature',
        'registrar_name',
        'registrar_designation',
        'registrar_signature',
        'theme',
        'primary_color',
    ];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }
}
