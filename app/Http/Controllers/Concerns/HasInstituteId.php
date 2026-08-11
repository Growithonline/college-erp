<?php

namespace App\Http\Controllers\Concerns;

trait HasInstituteId
{
    protected function instituteId(): int
    {
        return (int) auth()->user()->institute_id;
    }
}
