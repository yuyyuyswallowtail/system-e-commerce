<?php

declare(strict_types=1);

use App\Modules\Catalog\Models\Product;
use App\Modules\Content\Models\Article;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\IdentityKit;

uses(RefreshDatabase::class);

function richDom(string $html): DOMXPath
{
    $dom = new DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?>'.$html);
    libxml_clear_errors();

    return new DOMXPath($dom);
}

function richPage($test, string $url): DOMXPath
{
    $response = $test->get($url);
    $response->assertOk();

    return richDom((string) $response->getContent());
}

function richAssertBasics(DOMXPath $x): void
{
    expect($x->query('//h1')->length)->toBe(1);

    foreach ($x->query('//img') as $img) {
        expect($img->hasAttribute('alt'))->toBeTrue();
    }

    $footerNav = $x->query('//footer//nav[@aria-label]');
    expect($footerNav->length)->toBeGreaterThanOrEqual(2);
}

beforeEach(function () {
    IdentityKit::user();
    $this->seed(DemoDataSeeder::class);
});

it('renders the home page with hero, chips, trust strip and complete product cards', function () {
    $x = richPage($this, '/');

    richAssertBasics($x);

    expect($x->query('//*[contains(@class,"hero")]')->length)->toBeGreaterThan(0);
    expect($x->query('//a[contains(@class,"btn-primary")][normalize-space()="Shop products"]')->length)->toBe(1);
    expect($x->query('//a[normalize-space()="Read articles"]')->length)->toBeGreaterThanOrEqual(1);
    expect($x->query('//*[contains(@class,"chip")]')->length)->toBeGreaterThanOrEqual(6);
    expect($x->query('//*[contains(@class,"trust-strip")]//li')->length)->toBeGreaterThanOrEqual(3);

    $cards = $x->query('//article[contains(@class,"product-card")]');
    expect($cards->length)->toBeGreaterThan(0);

    foreach ($cards as $card) {
        $inner = new DOMXPath($card->ownerDocument);
        expect($inner->query('.//*[contains(@class,"visual")]', $card)->length)->toBeGreaterThan(0);
        expect($inner->query('.//*[contains(@class,"product-card__price")]', $card)->length)->toBe(1);
        expect($card->textContent)->toMatch('/In stock|left|Out of stock/');
    }

    expect($x->query('//footer[contains(concat(" ", normalize-space(@class), " "), " site-footer ")]')->length)->toBe(1);
    expect(strtolower((string) $x->document->textContent))->not->toContain('lorem');
});

it('shows seeded products on the home page', function () {
    $x = richPage($this, '/');

    expect($x->document->textContent)->toContain('Brass Desk Lamp');
});

it('renders a product detail page with visual, details list and stock', function () {
    $product = Product::query()->where('sku', 'DEMO-0001')->firstOrFail();
    $x = richPage($this, route('products.show', ['product' => $product->uuid], false));

    richAssertBasics($x);

    expect($x->query('//*[contains(@class,"visual")]')->length)->toBeGreaterThan(0);
    expect($x->query('//dl[contains(@class,"meta-list")]//dt[normalize-space()="SKU"]')->length)->toBe(1);
    expect($x->query('//dl[contains(@class,"meta-list")]//dt[normalize-space()="Category"]')->length)->toBe(1);
    expect($x->query('//dl[contains(@class,"meta-list")]//dt[normalize-space()="Availability"]')->length)->toBe(1);
    expect($x->document->textContent)->toContain('DEMO-0001')->toContain('Brass Desk Lamp');
});

it('disables the action with a clear reason for an out-of-stock product', function () {
    $product = Product::query()->where('sku', 'DEMO-0010')->firstOrFail();
    $x = richPage($this, route('products.show', ['product' => $product->uuid], false));

    expect($x->query('//button[@disabled]')->length)->toBeGreaterThanOrEqual(1);
    expect($x->document->textContent)->toContain('Out of stock');
});

it('renders the articles list with a featured card and a grid', function () {
    $x = richPage($this, '/articles');

    richAssertBasics($x);

    expect($x->query('//article[contains(@class,"featured-card")]')->length)->toBe(1);
    expect($x->query('//article[contains(@class,"article-card")]')->length)->toBeGreaterThanOrEqual(3);
    expect($x->query('//article//*[contains(@class,"visual")]')->length)->toBeGreaterThanOrEqual(4);
    expect($x->query('//time[@datetime]')->length)->toBeGreaterThanOrEqual(4);
    expect($x->document->textContent)->toContain('Welcome to the shop');
});

it('renders an article detail page with cover, readable measure and back link', function () {
    $article = Article::query()->where('slug', 'caring-for-stoneware')->firstOrFail();
    $x = richPage($this, route('articles.show', ['article' => $article->slug], false));

    richAssertBasics($x);

    expect($x->query('//*[contains(@class,"visual")]')->length)->toBeGreaterThan(0);
    expect($x->query('//*[contains(@class,"measure") or contains(@class,"prose")]')->length)->toBeGreaterThan(0);
    expect($x->query('//a[contains(@href,"/articles")][contains(normalize-space(),"Back to articles")]')->length)->toBe(1);
    expect($x->document->textContent)->toContain('How to care for stoneware');
});

it('keeps all storefront copy in the language file', function () {
    $lang = require dirname(__DIR__, 3).'/lang/en/storefront.php';

    expect($lang)->toBeArray()->toHaveKeys(['hero', 'trust', 'footer', 'card', 'detail', 'articles']);
});
