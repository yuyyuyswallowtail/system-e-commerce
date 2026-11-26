<?php

declare(strict_types=1);

namespace App\Modules\Notification\Notifications;

final class PaymentRefundedNotification extends BaseNotification
{
    public function type(): string
    {
        return 'payment_refunded';
    }

    protected function title(): string
    {
        return 'Payment refunded';
    }

    protected function body(): string
    {
        return 'The payment for your order'.$this->reference().' was refunded.'.$this->total();
    }
}
