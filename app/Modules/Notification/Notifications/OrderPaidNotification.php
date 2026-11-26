<?php

declare(strict_types=1);

namespace App\Modules\Notification\Notifications;

final class OrderPaidNotification extends BaseNotification
{
    public function type(): string
    {
        return 'order_paid';
    }

    protected function title(): string
    {
        return 'Order paid';
    }

    protected function body(): string
    {
        return 'Payment for your order'.$this->reference().' was received.'.$this->total();
    }
}
