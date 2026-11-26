<?php

use App\Modules\Cart\Providers\CartServiceProvider;
use App\Modules\Catalog\Providers\CatalogServiceProvider;
use App\Modules\Comment\Providers\CommentServiceProvider;
use App\Modules\Content\Providers\ContentServiceProvider;
use App\Modules\History\Providers\HistoryServiceProvider;
use App\Modules\Identity\Providers\IdentityServiceProvider;
use App\Modules\Inventory\Providers\InventoryServiceProvider;
use App\Modules\Notification\Providers\NotificationServiceProvider;
use App\Modules\Order\Providers\OrderServiceProvider;
use App\Modules\Payment\Providers\PaymentServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    IdentityServiceProvider::class,
    CatalogServiceProvider::class,
    InventoryServiceProvider::class,
    CartServiceProvider::class,
    PaymentServiceProvider::class,
    OrderServiceProvider::class,
    HistoryServiceProvider::class,
    NotificationServiceProvider::class,
    ContentServiceProvider::class,
    CommentServiceProvider::class,
];
