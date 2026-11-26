<?php

declare(strict_types=1);

function modulesPermissionCodes(): array
{
    $codes = [];

    foreach (file(base_path('docs/MODULES.md'), FILE_IGNORE_NEW_LINES) as $line) {
        if (! str_starts_with($line, '- Permissions (')) {
            continue;
        }

        $list = substr($line, strpos($line, '):') + 2);
        preg_match_all('/\b[a-z_]+\.[a-z_]+\b/', $list, $matches);
        $codes = array_merge($codes, $matches[0]);
    }

    return $codes;
}

it('lists every shop config key', function () {
    $keys = [
        'default_currency', 'display_timezone', 'user_statuses', 'system_roles', 'presence_window_seconds',
        'password_rules', 'login_max_attempts', 'login_decay_seconds', 'product_statuses', 'lookup_max_uuids',
        'availability_max_uuids', 'reservation_statuses', 'adjust_reasons', 'reservation_ttl_seconds',
        'low_stock_default_threshold', 'cart_statuses', 'cart_max_quantity', 'cart_max_items', 'payment_statuses',
        'payment_provider', 'payment_expiry_seconds', 'payment_simulate_failure', 'order_statuses',
        'order_transitions', 'manual_order_transitions', 'max_open_orders_per_user', 'notification_types',
        'article_statuses', 'comment_entity_types', 'comment_statuses', 'comment_max_length',
        'comment_flood_limit', 'comment_flood_decay_seconds', 'stats_cache_ttl', 'pagination', 'uploads',
        'currencies',
    ];

    foreach ($keys as $key) {
        expect(config('shop'))->toHaveKey($key);
    }
});

it('has no empty list or null value in shop config', function () {
    foreach (config('shop') as $key => $value) {
        expect($value)->not->toBeNull("shop.{$key} is null");

        if (is_array($value)) {
            expect($value)->not->toBeEmpty("shop.{$key} is empty");
        }
    }
});

it('keeps pagination and uploads sane', function () {
    expect(config('shop.pagination.default'))->toBeInt()->toBeGreaterThan(0)
        ->and(config('shop.pagination.max'))->toBeGreaterThanOrEqual(config('shop.pagination.default'))
        ->and(config('shop.uploads.mimes'))->toBeArray()->not->toBeEmpty()
        ->and(config('shop.uploads.max_kb'))->toBeInt()->toBeGreaterThan(0)
        ->and(config('shop.currencies'))->toHaveKey(config('shop.default_currency'));
});

it('only uses known statuses in order transitions', function () {
    $statuses = config('shop.order_statuses');

    expect(array_keys(config('shop.order_transitions')))->toEqualCanonicalizing($statuses);

    foreach (config('shop.order_transitions') as $from => $targets) {
        foreach ($targets as $target) {
            expect($statuses)->toContain($target);
        }
    }

    foreach (config('shop.manual_order_transitions') as $from => $targets) {
        expect($statuses)->toContain($from);

        foreach ($targets as $target) {
            expect(config("shop.order_transitions.{$from}"))->toContain($target);
        }
    }
});

it('defines every permission code from docs/MODULES.md exactly once', function () {
    $documented = modulesPermissionCodes();
    $configured = array_keys(config('permissions.permissions'));

    expect($documented)->not->toBeEmpty()
        ->and($documented)->toBe(array_values(array_unique($documented)))
        ->and($configured)->toEqualCanonicalizing($documented);

    foreach (config('permissions.permissions') as $code => $description) {
        expect($description)->toBeString()->not->toBeEmpty();
    }
});

it('defines roles that reference only existing permission codes', function () {
    $codes = array_keys(config('permissions.permissions'));
    $roles = config('permissions.roles');

    expect($roles)->toHaveKeys(config('shop.system_roles'))
        ->and($roles['super_admin'])->toBe('*')
        ->and($roles['member'])->toBe([])
        ->and($roles['admin']['all_except'])->toBe(['permissions.manage']);

    foreach ($roles as $role => $definition) {
        $referenced = is_array($definition) ? ($definition['all_except'] ?? $definition) : [];

        foreach ($referenced as $code) {
            expect($codes)->toContain($code);
        }
    }
});

it('defines table whitelists with a valid default sort', function () {
    expect(config('tables'))->toHaveKeys(['products', 'users']);

    foreach (config('tables') as $table => $definition) {
        expect($definition)->toHaveKeys(['searchable', 'filterable', 'sortable', 'default_sort']);

        foreach (['searchable', 'filterable', 'sortable'] as $list) {
            expect($definition[$list])->toBeArray()->not->toBeEmpty("tables.{$table}.{$list} is empty");
        }

        [$field, $direction] = $definition['default_sort'];
        expect($definition['sortable'])->toContain($field)
            ->and(['asc', 'desc'])->toContain($direction);
    }
});

it('defines the history config structure', function () {
    expect(config('history'))->toHaveKeys(['events', 'subject_types'])
        ->and(config('history.events'))->toBeArray()
        ->and(config('history.subject_types'))->toBeArray();
});
