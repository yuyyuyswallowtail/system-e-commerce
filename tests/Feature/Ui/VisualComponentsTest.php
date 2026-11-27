<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Category;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CatalogKit;
use Tests\Support\ContentKit;
use Tests\Support\IdentityKit;

uses(RefreshDatabase::class);

function visualHex(string $css, string $name): string
{
    preg_match('/(?<![a-z0-9-])'.preg_quote($name, '/').':\s*([^;]+);/', $css, $match);
    $value = trim($match[1] ?? '');

    return preg_match('/^var\((--[a-z0-9-]+)\)$/', $value, $inner) ? visualHex($css, $inner[1]) : $value;
}

function visualLum(string $hex): float
{
    $c = array_map(function (string $p): float {
        $v = hexdec($p) / 255;

        return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
    }, str_split(ltrim($hex, '#'), 2));

    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

function visualRatio(string $a, string $b): float
{
    $x = visualLum($a);
    $y = visualLum($b);

    return (max($x, $y) + 0.05) / (min($x, $y) + 0.05);
}

it('renders the product image with the product name as alt text', function () {
    $category = CatalogKit::category(['slug' => 'kitchen']);
    $product = CatalogKit::product(['name' => 'Pour-Over Coffee Set', 'category_id' => $category->id, 'image_path' => 'products/a.jpg'])->load('category');

    $html = (string) $this->blade('<x-product-visual :product="$product" />', ['product' => $product]);

    expect($html)->toContain('<img')->toContain('alt="Pour-Over Coffee Set"')->not->toContain('visual__placeholder');
});

it('renders a tinted placeholder with the category icon when there is no image', function () {
    $category = CatalogKit::category(['slug' => 'kitchen']);
    $product = CatalogKit::product(['name' => 'Pour-Over Coffee Set', 'category_id' => $category->id, 'image_path' => null])->load('category');

    $html = (string) $this->blade('<x-product-visual :product="$product" />', ['product' => $product]);

    expect($html)->not->toContain('<img')
        ->toContain('visual__placeholder')
        ->toContain('visual--'.config('ui.visuals.categories.kitchen.tint'))
        ->toContain(config('ui.visuals.categories.kitchen.icon'))
        ->toContain('aria-label="Pour-Over Coffee Set"');
});

it('falls back to the default visual for an unknown category', function () {
    $product = CatalogKit::product(['name' => 'Mystery Item', 'image_path' => null]);

    $html = (string) $this->blade('<x-product-visual :product="$product" />', ['product' => $product]);

    expect($html)->toContain('visual--'.config('ui.visuals.default.tint'))->toContain('aria-label="Mystery Item"');
});

it('renders the article cover as an image or a placeholder', function () {
    $with = ContentKit::published(['title' => 'Caring for stoneware', 'cover_path' => 'articles/a.jpg']);
    $without = ContentKit::published(['title' => 'Packing a bag', 'cover_path' => null]);

    $a = (string) $this->blade('<x-article-cover :article="$article" />', ['article' => $with]);
    $b = (string) $this->blade('<x-article-cover :article="$article" />', ['article' => $without]);

    expect($a)->toContain('<img')->toContain('alt="Caring for stoneware"')
        ->and($b)->not->toContain('<img')->toContain('visual__placeholder')->toContain('aria-label="Packing a bag"');
});

it('maps every seeded category to a known tint and icon', function () {
    IdentityKit::user();
    $this->seed(DemoDataSeeder::class);

    $map = (array) config('ui.visuals.categories');
    $slugs = Category::query()->pluck('slug')->all();

    expect($slugs)->not->toBeEmpty();

    foreach ($slugs as $slug) {
        expect($map)->toHaveKey($slug);
        expect((array) config('ui.visuals.tints'))->toContain($map[$slug]['tint']);
    }
});

it('defines tint tokens with AA contrast', function () {
    $css = (string) file_get_contents(dirname(__DIR__, 3).'/public/css/theme.css');
    $text = visualHex($css, '--text');
    $muted = visualHex($css, '--text-muted');

    foreach ((array) config('ui.visuals.tints') as $tint) {
        expect($css)->toContain('.visual--'.$tint);
        $bg = visualHex($css, '--tint-'.$tint.'-bg');
        $fg = visualHex($css, '--tint-'.$tint.'-fg');
        expect(visualRatio($text, $bg))->toBeGreaterThanOrEqual(4.5);
        expect(visualRatio($muted, $bg))->toBeGreaterThanOrEqual(4.5);
        expect(visualRatio($fg, $bg))->toBeGreaterThanOrEqual(4.5);
    }
});
