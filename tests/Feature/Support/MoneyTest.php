<?php

declare(strict_types=1);

use App\Support\Money;

it('formats minor units with the currency decimals from config', function () {
    expect(Money::format(123456, 'USD'))->toBe('1,234.56 USD')
        ->and(Money::format(5, 'USD'))->toBe('0.05 USD')
        ->and(Money::format(0, 'USD'))->toBe('0.00 USD')
        ->and(Money::format(1500, 'JPY'))->toBe('1,500 JPY')
        ->and(Money::format(-5, 'USD'))->toBe('-0.05 USD')
        ->and(Money::format(-123456, 'USD'))->toBe('-1,234.56 USD');
});

it('uses the default currency when none is given', function () {
    $default = config('shop.default_currency');

    expect(Money::format(100))->toEndWith(' '.$default);
});

it('rejects an unknown currency', function () {
    Money::format(100, 'XXX');
})->throws(InvalidArgumentException::class);

it('formats very large amounts without float loss', function () {
    expect(Money::format(PHP_INT_MAX, 'JPY'))->toBe('9,223,372,036,854,775,807 JPY');
});
