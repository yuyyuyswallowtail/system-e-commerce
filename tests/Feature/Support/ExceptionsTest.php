<?php

declare(strict_types=1);

use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use App\Support\Exceptions\NotFoundException;

it('carries the failing fields in a domain validation exception', function () {
    $exception = new DomainValidationException(['quantity' => 'Too many.'], 'Invalid input.');

    expect($exception)->toBeInstanceOf(RuntimeException::class)
        ->and($exception->getMessage())->toBe('Invalid input.')
        ->and($exception->fields())->toBe(['quantity' => 'Too many.']);
});

it('has a default message for a domain validation exception', function () {
    expect((new DomainValidationException(['a' => 'b']))->getMessage())->not->toBeEmpty();
});

it('builds conflict and not found exceptions', function () {
    expect((new ConflictException('Already paid.'))->getMessage())->toBe('Already paid.')
        ->and(new ConflictException)->toBeInstanceOf(RuntimeException::class)
        ->and((new NotFoundException('Missing.'))->getMessage())->toBe('Missing.')
        ->and(new NotFoundException)->toBeInstanceOf(RuntimeException::class);
});
