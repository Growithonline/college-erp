<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientWalletBalanceException extends RuntimeException
{
    public function __construct(public readonly float $available, public readonly float $required)
    {
        parent::__construct(sprintf(
            'Wallet balance insufficient. Available: %.2f, Required: %.2f',
            $available,
            $required
        ));
    }
}
