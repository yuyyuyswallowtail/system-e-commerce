<?php

declare(strict_types=1);

namespace App\Modules\Notification\Notifications;

final class OrderPlacedNotification extends BaseNotification
{
    public function type(): string
    {
        return 'order_placed';
    }

    protected function title(): string
    {
        return 'Order placed';
    }

    protected function body(): string
    {
        return 'Your order'.$this->reference().' was placed.'.$this->total();
    }
}
