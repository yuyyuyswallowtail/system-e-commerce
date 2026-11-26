<?php

declare(strict_types=1);
use App\Modules\Notification\Notifications\OrderCancelledNotification;
use App\Modules\Notification\Notifications\OrderPaidNotification;
use App\Modules\Notification\Notifications\OrderPlacedNotification;
use App\Modules\Notification\Notifications\OrderStatusChangedNotification;
use App\Modules\Notification\Notifications\PaymentFailedNotification;
use App\Modules\Notification\Notifications\PaymentRefundedNotification;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderPaid;
use App\Modules\Order\Events\OrderPlaced;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Payment\Events\PaymentFailed;
use App\Modules\Payment\Events\PaymentRefunded;

// Event class => notification class. Classes that do not exist are skipped when listeners are registered.
return [

    'events' => [
        OrderPlaced::class => OrderPlacedNotification::class,
        OrderPaid::class => OrderPaidNotification::class,
        OrderCancelled::class => OrderCancelledNotification::class,
        OrderStatusChanged::class => OrderStatusChangedNotification::class,
        PaymentFailed::class => PaymentFailedNotification::class,
        PaymentRefunded::class => PaymentRefundedNotification::class,
    ],

    'tries' => 3,

    // Unread badge polling interval in seconds. 0 turns polling off.
    'poll_seconds' => 0,

];
