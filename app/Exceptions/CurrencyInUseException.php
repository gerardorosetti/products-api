<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class CurrencyInUseException extends RuntimeException
{
    public function __construct(string $message = 'The currency is in use and cannot be deleted.')
    {
        parent::__construct($message);
    }
}
