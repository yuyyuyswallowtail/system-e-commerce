<?php

declare(strict_types=1);

namespace App\Modules\Cart\Events;

use App\Support\Events\DomainEvent;

/**
 * Subject type cart, subject uuid = the cart uuid.
 */
final class CartCleared extends DomainEvent
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(string $cartUuid, int $userId, array $details = [])
    {
        parent::__construct('cart', $cartUuid, $userId, $userId, $details);
    }
}
