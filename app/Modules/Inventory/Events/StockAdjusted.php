<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Events;

use App\Support\Events\DomainEvent;

/**
 * Subject type stock, subject uuid = the product uuid.
 */
final class StockAdjusted extends DomainEvent
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(string $productUuid, ?int $actorId = null, array $details = [])
    {
        parent::__construct('stock', $productUuid, $actorId, null, $details);
    }
}
