<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CenterIdCounter extends Model
{
    protected $fillable = ['institute_id', 'year', 'last_seq'];
}
