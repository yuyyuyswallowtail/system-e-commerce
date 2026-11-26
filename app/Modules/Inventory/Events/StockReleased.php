<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Events;

use App\Support\Events\DomainEvent;

/**
 * Subject type stock, subject uuid = the order uuid.
 */
final class StockReleased extends DomainEvent
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(string $orderUuid, ?int $actorId = null, array $details = [])
    {
        parent::__construct('stock', $orderUuid, $actorId, null, $details);
    }
}
