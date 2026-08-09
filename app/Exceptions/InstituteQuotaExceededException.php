<?php

namespace App\Exceptions;

use RuntimeException;

class InstituteQuotaExceededException extends RuntimeException
{
    public function __construct(public readonly int $quota)
    {
        parent::__construct("Institute quota reached ({$quota}).");
    }
}
