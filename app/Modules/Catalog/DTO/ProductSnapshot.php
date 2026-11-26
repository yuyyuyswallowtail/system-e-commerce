<?php

declare(strict_types=1);

namespace App\Modules\Catalog\DTO;

final readonly class ProductSnapshot
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $sku,
        public int $priceAmount,
        public string $currency,
        public string $status,
        public bool $deleted,
    ) {}
}
