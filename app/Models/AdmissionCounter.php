<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AdmissionCounter extends Model
{
    protected $fillable = ['institute_id', 'year', 'last_seq'];
}
