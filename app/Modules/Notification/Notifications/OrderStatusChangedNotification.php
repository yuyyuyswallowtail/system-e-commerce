<?php

declare(strict_types=1);

namespace App\Modules\Notification\Notifications;

final class OrderStatusChangedNotification extends BaseNotification
{
    public function type(): string
    {
        return 'order_status_changed';
    }

    protected function title(): string
    {
        return 'Order status changed';
    }

    protected function body(): string
    {
        return 'Your order'.$this->reference().' changed status'.($this->detail('to') === '' ? '' : ' to '.$this->detail('to')).'.';
    }
}
