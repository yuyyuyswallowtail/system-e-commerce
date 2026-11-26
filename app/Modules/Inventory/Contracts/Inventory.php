<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Contracts;

interface Inventory
{
    /**
     * Available quantity (on hand minus reserved, never negative) for every requested uuid. Unknown = 0.
     *
     * @param  array<int, string>  $productUuids
     * @return array<string, int>
     */
    public function availability(array $productUuids): array;

    /**
     * All-or-nothing and idempotent by order uuid.
     *
     * @param  array<int, array{product_uuid: string, quantity: int}>  $items
     */
    public function reserve(string $orderUuid, array $items): void;

    public function commit(string $orderUuid): void;

    public function release(string $orderUuid): void;
}
