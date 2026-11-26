<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Exceptions;

use RuntimeException;

final class InsufficientStockException extends RuntimeException
{
    public function __construct(private readonly string $productUuid, private readonly int $available)
    {
        parent::__construct('Not enough stock for the requested product.');
    }

    public function productUuid(): string
    {
        return $this->productUuid;
    }

    public function available(): int
    {
        return $this->available;
    }

    /**
     * @return array{product_uuid: string, available: int}
     */
    public function fields(): array
    {
        return ['product_uuid' => $this->productUuid, 'available' => $this->available];
    }
}
