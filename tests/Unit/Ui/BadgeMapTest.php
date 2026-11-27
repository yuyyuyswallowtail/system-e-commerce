<?php

declare(strict_types=1);

$groups = [
    'order_statuses',
    'payment_statuses',
    'product_statuses',
    'article_statuses',
    'comment_statuses',
    'user_statuses',
    'reservation_statuses',
    'cart_statuses',
];

it('maps every status of the group to a known badge variant', function (string $group) {
    $statuses = config("shop.{$group}");
    $map = config("ui.badge_variants.{$group}");

    expect($statuses)->toBeArray()->not->toBeEmpty();
    expect($map)->toBeArray();

    foreach ($statuses as $status) {
        expect($map)->toHaveKey($status);
        expect(in_array($map[$status], config('ui.variants'), true))->toBeTrue("{$group}.{$status} has an unknown variant");
    }
})->with($groups);

it('has no badge variant for a status that no longer exists', function (string $group) {
    expect(array_keys(config("ui.badge_variants.{$group}")))->toEqualCanonicalizing(config("shop.{$group}"));
})->with($groups);

it('names Font Awesome icons only', function () {
    $icons = config('ui.icons');

    expect($icons)->toBeArray()->not->toBeEmpty();
    foreach ($icons as $name => $icon) {
        expect($icon)->toMatch('/^fa[srb] fa-[a-z0-9-]+$/', "ui.icons.{$name}");
    }
});

it('lists stat tones that are also badge variants', function () {
    foreach (config('ui.stat_tones') as $tone) {
        expect(in_array($tone, config('ui.variants'), true))->toBeTrue();
    }
});
