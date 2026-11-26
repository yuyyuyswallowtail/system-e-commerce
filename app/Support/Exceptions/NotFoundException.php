<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

use RuntimeException;
use Throwable;

class NotFoundException extends RuntimeException
{
    public function __construct(string $message = 'The requested resource was not found.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
