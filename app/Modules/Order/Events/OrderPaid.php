<?php

declare(strict_types=1);

namespace App\Modules\Order\Events;

use App\Support\Events\DomainEvent;

/**
 * Subject type order, subject uuid = the order uuid, user id = the order owner.
 */
final class OrderPaid extends DomainEvent
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(string $orderUuid, int $ownerId, ?int $actorId = null, array $details = [])
    {
        parent::__construct('order', $orderUuid, $actorId, $ownerId, $details);
    }
}
