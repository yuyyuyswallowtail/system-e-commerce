<?php

use App\Modules\Comment\Events\CommentPosted;
use App\Modules\Content\Events\ArticlePublished;
use App\Modules\Identity\Events\UserRegistered;
use App\Modules\Identity\Events\UserStatusChanged;
use App\Modules\Inventory\Events\StockAdjusted;
use App\Modules\Inventory\Events\StockCommitted;
use App\Modules\Inventory\Events\StockReleased;
use App\Modules\Inventory\Events\StockReserved;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderPaid;
use App\Modules\Order\Events\OrderPlaced;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Payment\Events\PaymentCancelled;
use App\Modules\Payment\Events\PaymentCreated;
use App\Modules\Payment\Events\PaymentExpired;
use App\Modules\Payment\Events\PaymentFailed;
use App\Modules\Payment\Events\PaymentPaid;
use App\Modules\Payment\Events\PaymentRefunded;

// Event class => [subject_type, action]. Classes that do not exist yet are skipped when listeners are registered.
return [

    'events' => [
        UserRegistered::class => ['user', 'registered'],
        UserStatusChanged::class => ['user', 'status_changed'],
        StockAdjusted::class => ['stock', 'adjusted'],
        StockReserved::class => ['stock', 'reserved'],
        StockCommitted::class => ['stock', 'committed'],
        StockReleased::class => ['stock', 'released'],
        PaymentCreated::class => ['payment', 'created'],
        PaymentPaid::class => ['payment', 'paid'],
        PaymentFailed::class => ['payment', 'failed'],
        PaymentExpired::class => ['payment', 'expired'],
        PaymentCancelled::class => ['payment', 'cancelled'],
        PaymentRefunded::class => ['payment', 'refunded'],
        OrderPlaced::class => ['order', 'placed'],
        OrderPaid::class => ['order', 'paid'],
        OrderCancelled::class => ['order', 'cancelled'],
        OrderStatusChanged::class => ['order', 'status_changed'],
        ArticlePublished::class => ['article', 'published'],
        CommentPosted::class => ['comment', 'posted'],
    ],

    'subject_types' => ['user', 'stock', 'payment', 'order', 'article', 'comment'],

    'actions' => [
        'registered', 'status_changed', 'adjusted', 'reserved', 'committed', 'released',
        'created', 'paid', 'failed', 'expired', 'cancelled', 'refunded', 'placed', 'published', 'posted',
    ],

    'tries' => 3,

    // Detail keys containing one of these fragments are dropped before a row is written.
    'secret_keys' => ['password', 'token', 'secret', 'authorization', 'api_key', 'cvv', 'card_number'],

];
