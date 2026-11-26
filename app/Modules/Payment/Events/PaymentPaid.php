<?php

declare(strict_types=1);

namespace App\Modules\Payment\Events;

use App\Support\Events\DomainEvent;

/**
 * Subject type payment, subject uuid = the payment uuid, user id = the payment owner.
 */
final class PaymentPaid extends DomainEvent
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(string $paymentUuid, int $ownerId, ?int $actorId = null, array $details = [])
    {
        parent::__construct('payment', $paymentUuid, $actorId, $ownerId, $details);
    }
}
