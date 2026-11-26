<?php

declare(strict_types=1);

namespace App\Modules\Notification\Notifications;

final class PaymentFailedNotification extends BaseNotification
{
    public function type(): string
    {
        return 'payment_failed';
    }

    protected function title(): string
    {
        return 'Payment failed';
    }

    protected function body(): string
    {
        return 'The payment for your order'.$this->reference().' failed.'.$this->total();
    }
}
