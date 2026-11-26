<?php

return [

    'default_currency' => env('SHOP_DEFAULT_CURRENCY', 'USD'),

    // Minor-unit decimals per currency code, used by App\Support\Money.
    'currencies' => [
        'USD' => 2,
        'EUR' => 2,
        'IDR' => 0,
        'JPY' => 0,
    ],

    'display_timezone' => env('SHOP_DISPLAY_TIMEZONE', 'Asia/Jakarta'),

    'user_statuses' => ['active', 'suspended', 'banned'],
    'system_roles' => ['super_admin', 'admin', 'member'],
    'presence_window_seconds' => 300,

    'password_rules' => [
        'min' => 10,
        'max' => 128,
        'mixed_case' => true,
        'numbers' => true,
        'symbols' => false,
        'uncompromised' => false,
    ],
    'login_max_attempts' => 5,
    'login_decay_seconds' => 60,

    'product_statuses' => ['draft', 'active', 'inactive'],
    'lookup_max_uuids' => 100,
    'availability_max_uuids' => 100,

    'reservation_statuses' => ['reserved', 'committed', 'released', 'expired'],
    'adjust_reasons' => ['restock', 'correction', 'damage', 'return', 'order_commit'],
    'reservation_ttl_seconds' => 900,
    'low_stock_default_threshold' => 5,

    'cart_statuses' => ['active', 'converted', 'abandoned'],
    'cart_max_quantity' => 99,
    'cart_max_items' => 50,

    'payment_statuses' => ['pending', 'paid', 'failed', 'expired', 'cancelled', 'refunded'],
    'payment_provider' => 'simulated',
    'payment_expiry_seconds' => 900,
    'payment_simulate_failure' => (bool) env('SHOP_PAYMENT_SIMULATE_FAILURE', false),

    'order_statuses' => ['awaiting_payment', 'paid', 'processing', 'shipped', 'completed', 'cancelled'],
    'order_transitions' => [
        'awaiting_payment' => ['paid', 'cancelled'],
        'paid' => ['processing', 'cancelled'],
        'processing' => ['shipped'],
        'shipped' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ],
    'manual_order_transitions' => [
        'paid' => ['processing'],
        'processing' => ['shipped'],
        'shipped' => ['completed'],
    ],
    'max_open_orders_per_user' => 5,

    'notification_types' => [
        'order_placed',
        'order_paid',
        'order_cancelled',
        'order_status_changed',
        'payment_failed',
        'payment_refunded',
    ],

    'article_statuses' => ['draft', 'published', 'archived'],

    'comment_entity_types' => ['article', 'product'],
    'comment_statuses' => ['visible', 'hidden'],
    'comment_max_length' => 1000,
    'comment_flood_limit' => 5,
    'comment_flood_decay_seconds' => 60,

    'stats_cache_ttl' => 60,

    'pagination' => [
        'default' => 15,
        'max' => 100,
    ],

    'uploads' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 2048,
    ],

];
