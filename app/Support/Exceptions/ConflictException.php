<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

use RuntimeException;
use Throwable;

class ConflictException extends RuntimeException
{
    public function __construct(string $message = 'The request conflicts with the current state.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
