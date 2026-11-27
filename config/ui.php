<?php

declare(strict_types=1);

return [

    // Badge and stat-card variants. Each one has matching rules in public/css/theme.css.
    'variants' => ['neutral', 'accent', 'success', 'warning', 'danger', 'info'],

    // Tones a stat card may carry. A tone is a status color, so it is used only when it means something.
    'stat_tones' => ['neutral', 'success', 'warning', 'danger', 'info'],

    // Status to badge variant. Keys must match the status lists in config/shop.php (tests enforce it).
    'badge_variants' => [
        'order_statuses' => [
            'awaiting_payment' => 'warning',
            'paid' => 'success',
            'processing' => 'info',
            'shipped' => 'info',
            'completed' => 'success',
            'cancelled' => 'neutral',
        ],
        'payment_statuses' => [
            'pending' => 'warning',
            'paid' => 'success',
            'failed' => 'danger',
            'expired' => 'neutral',
            'cancelled' => 'neutral',
            'refunded' => 'info',
        ],
        'product_statuses' => [
            'draft' => 'neutral',
            'active' => 'success',
            'inactive' => 'neutral',
        ],
        'article_statuses' => [
            'draft' => 'neutral',
            'published' => 'success',
            'archived' => 'neutral',
        ],
        'comment_statuses' => [
            'visible' => 'success',
            'hidden' => 'neutral',
        ],
        'user_statuses' => [
            'active' => 'success',
            'suspended' => 'warning',
            'banned' => 'danger',
        ],
        'reservation_statuses' => [
            'reserved' => 'info',
            'committed' => 'success',
            'released' => 'neutral',
            'expired' => 'warning',
        ],
        'cart_statuses' => [
            'active' => 'info',
            'converted' => 'success',
            'abandoned' => 'neutral',
        ],
    ],

    // Font Awesome Free icon classes used by presentation code.
    'icons' => [
        'orders' => 'fas fa-clipboard-list',
        'revenue' => 'fas fa-coins',
        'payments' => 'fas fa-credit-card',
        'low_stock' => 'fas fa-exclamation-triangle',
        'out_of_stock' => 'fas fa-box-open',
        'products' => 'fas fa-box',
        'articles' => 'fas fa-newspaper',
        'comments' => 'fas fa-comments',
        'refresh' => 'fas fa-sync',
        'empty' => 'fas fa-folder-open',
    ],


    // Product visual and article cover: tint tokens live in public/css/theme.css (--tint-*-bg and --tint-*-fg).
    'visuals' => [
        'tints' => ['sage', 'sand', 'sky', 'clay', 'stone'],
        'default' => ['tint' => 'stone', 'icon' => 'fas fa-box'],
        'article' => ['tint' => 'sand'],
        // Keyed by category slug. A test checks that every seeded category has an entry.
        'categories' => [
            'home-living' => ['tint' => 'clay', 'icon' => 'fas fa-couch'],
            'workspace' => ['tint' => 'sky', 'icon' => 'fas fa-desktop'],
            'kitchen' => ['tint' => 'sage', 'icon' => 'fas fa-mug-hot'],
            'travel' => ['tint' => 'sand', 'icon' => 'fas fa-suitcase-rolling'],
            'stationery' => ['tint' => 'stone', 'icon' => 'fas fa-pen-nib'],
        ],
    ],

];
