<?php

declare(strict_types=1);

it('maps every required event class to a subject type and action', function () {
    $events = (array) config('history.events');
    $required = [
        'App\Modules\Identity\Events\UserRegistered', 'App\Modules\Identity\Events\UserStatusChanged',
        'App\Modules\Inventory\Events\StockAdjusted', 'App\Modules\Inventory\Events\StockReserved',
        'App\Modules\Inventory\Events\StockCommitted', 'App\Modules\Inventory\Events\StockReleased',
        'App\Modules\Payment\Events\PaymentCreated', 'App\Modules\Payment\Events\PaymentPaid',
        'App\Modules\Payment\Events\PaymentFailed', 'App\Modules\Payment\Events\PaymentExpired',
        'App\Modules\Payment\Events\PaymentCancelled', 'App\Modules\Payment\Events\PaymentRefunded',
        'App\Modules\Order\Events\OrderPlaced', 'App\Modules\Order\Events\OrderPaid',
        'App\Modules\Order\Events\OrderCancelled', 'App\Modules\Order\Events\OrderStatusChanged',
        'App\Modules\Content\Events\ArticlePublished', 'App\Modules\Comment\Events\CommentPosted',
    ];

    foreach ($required as $class) {
        expect($events)->toHaveKey($class);
        [$subject, $action] = $events[$class];
        expect(config('history.subject_types'))->toContain($subject);
        expect(config('history.actions'))->toContain($action);
    }
});

it('lists the subject types', function () {
    expect(config('history.subject_types'))->toEqualCanonicalizing(['user', 'stock', 'payment', 'order', 'article', 'comment']);
});

it('whitelists the history list', function () {
    $list = config('tables.history');
    expect($list['filterable'])->toEqual(['subject_type', 'action']);
    expect($list['searchable'])->toEqual(['subject_uuid']);
    expect($list['default_sort'])->toEqual(['occurred_at', 'desc']);
});
