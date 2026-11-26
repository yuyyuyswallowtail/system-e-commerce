<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    /**
     * Format integer minor units as a display string such as "1,234.56 USD".
     * Works on the digit string so large amounts never pass through a float.
     */
    public static function format(int $amount, ?string $currency = null): string
    {
        $currency ??= (string) config('shop.default_currency');
        $decimals = config("shop.currencies.{$currency}");

        if (! is_int($decimals)) {
            throw new InvalidArgumentException("Unknown currency [{$currency}].");
        }

        $digits = ltrim((string) $amount, '-');

        if ($decimals > 0) {
            $digits = str_pad($digits, $decimals + 1, '0', STR_PAD_LEFT);
            $whole = substr($digits, 0, -$decimals);
            $fraction = '.'.substr($digits, -$decimals);
        } else {
            $whole = $digits;
            $fraction = '';
        }

        $grouped = strrev(implode(',', str_split(strrev($whole), 3)));

        return ($amount < 0 ? '-' : '').$grouped.$fraction.' '.$currency;
    }
}
