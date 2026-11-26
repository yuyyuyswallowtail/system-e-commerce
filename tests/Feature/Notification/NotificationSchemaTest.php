<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

it('runs the notifications migration twice without error and has every column', function () {
    expect(Artisan::call('migrate', ['--force' => true]))->toBe(0);
    expect(Artisan::call('migrate', ['--force' => true]))->toBe(0);
    expect(Schema::hasColumns('notifications', [
        'id', 'type', 'notifiable_type', 'notifiable_id', 'data', 'read_at', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

it('maps every notification type from the config to a notification class', function () {
    $types = [];

    foreach ((array) config('notification.events') as $notification) {
        $types[] = (new $notification('11111111-1111-4111-8111-111111111111'))->type();
    }

    expect($types)->toEqualCanonicalizing((array) config('shop.notification_types'));
});
