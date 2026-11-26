<?php

declare(strict_types=1);

namespace App\Modules\Notification\Notifications;

final class OrderCancelledNotification extends BaseNotification
{
    public function type(): string
    {
        return 'order_cancelled';
    }

    protected function title(): string
    {
        return 'Order cancelled';
    }

    protected function body(): string
    {
        return 'Your order'.$this->reference().' was cancelled.'.($this->detail('reason') === '' ? '' : ' Reason: '.$this->detail('reason').'.');
    }
}
