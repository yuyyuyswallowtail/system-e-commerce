<?php

declare(strict_types=1);

namespace App\Modules\Cart\DTO;

final readonly class CartLine
{
    public function __construct(
        public string $productUuid,
        public int $quantity,
        public int $unitPriceAmount,
        public string $currency,
    ) {}
}
