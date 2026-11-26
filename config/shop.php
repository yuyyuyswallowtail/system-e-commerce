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
    'default_user_status' => 'active',
    'default_role' => 'member',
    'super_admin_role' => 'super_admin',
    'seed_admin' => [
        'email' => env('SEED_ADMIN_EMAIL'),
        'password' => env('SEED_ADMIN_PASSWORD'),
    ],

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
    'product_default_status' => 'draft',
    'product_public_status' => 'active',
    'product_public_sortable' => ['name', 'price_amount'],
    'product_inactive_status' => 'inactive',
    'lookup_max_uuids' => 100,
    'availability_max_uuids' => 100,

    'reservation_statuses' => ['reserved', 'committed', 'released', 'expired'],
    'adjust_reasons' => ['restock', 'correction', 'damage', 'return', 'order_commit'],
    'reservation_ttl_seconds' => 900,
    'low_stock_default_threshold' => 5,
    'inventory' => ['reservation_max_items' => 50, 'commit_reason' => 'order_commit', 'statuses' => ['reserved' => 'reserved', 'committed' => 'committed', 'released' => 'released', 'expired' => 'expired']],

    'cart_statuses' => ['active', 'converted', 'abandoned'],
    'cart_max_quantity' => 99,
    'cart_max_items' => 50,
    'cart_available_product_status' => 'active',
    'cart' => ['statuses' => ['active' => 'active', 'converted' => 'converted', 'abandoned' => 'abandoned']],

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
        'disk' => 'public',
        'directory' => 'products',
    ],

    'payment' => [
        'reference_prefix' => 'sim_',
        'reference_bytes' => 8,
        'refund_reason_max' => 255,
        'statuses' => [
            'pending' => 'pending',
            'paid' => 'paid',
            'failed' => 'failed',
            'expired' => 'expired',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
        ],
        'results' => [
            'paid' => 'paid',
            'failed' => 'failed',
            'expired' => 'expired',
            'refunded' => 'refunded',
        ],
        'events' => [
            'created' => 'created',
            'paid' => 'paid',
            'failed' => 'failed',
            'expired' => 'expired',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
        ],
    ],
    'order' => [
        'statuses' => [
            'awaiting_payment' => 'awaiting_payment',
            'paid' => 'paid',
            'processing' => 'processing',
            'shipped' => 'shipped',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
        ],
        'cancel_reasons' => [
            'payment_failed' => 'payment_failed',
            'payment_expired' => 'payment_expired',
            'refunded' => 'refunded',
            'owner' => 'owner_cancelled',
        ],
        'paid_after_cancel_log' => 'paid_after_cancel',
        'revenue_statuses' => ['paid', 'processing', 'shipped', 'completed'],
        'note_max' => 500,
        'shipping_rules' => [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^[0-9+() -]{6,20}$/'],
            'address_line1' => ['required', 'string', 'max:150'],
            'address_line2' => ['nullable', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'alpha', 'size:2'],
        ],
    ],
];
