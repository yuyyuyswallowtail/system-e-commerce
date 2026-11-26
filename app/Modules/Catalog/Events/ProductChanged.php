<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Events;

use App\Modules\Catalog\Models\Product;
use App\Support\Events\DomainEvent;

final class ProductChanged extends DomainEvent
{
    /**
     * @param  string  $change  created, updated, deleted or restored
     * @param  array<string, mixed>  $details
     */
    public function __construct(Product $product, public readonly string $change, ?int $actorId = null, array $details = [])
    {
        parent::__construct('product', $product->uuid, $actorId, null, $details);
    }
}
