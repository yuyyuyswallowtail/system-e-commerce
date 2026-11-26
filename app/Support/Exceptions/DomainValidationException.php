<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

use RuntimeException;
use Throwable;

class DomainValidationException extends RuntimeException
{
    /**
     * @param  array<string, string>  $fields  Field name => message.
     */
    public function __construct(private readonly array $fields, string $message = 'The given data was invalid.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @return array<string, string>
     */
    public function fields(): array
    {
        return $this->fields;
    }
}
