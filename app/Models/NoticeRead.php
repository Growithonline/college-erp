<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoticeRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['notice_id', 'reader_type', 'reader_id', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function notice()
    {
        return $this->belongsTo(Notice::class);
    }
}
