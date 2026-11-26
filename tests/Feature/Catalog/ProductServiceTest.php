<?php

declare(strict_types=1);

use App\Modules\Catalog\Events\ProductChanged;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\ProductService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\CatalogKit;

function catalogProducts(): ProductService
{
    return app(ProductService::class);
}

/**
 * @return array<string, mixed>
 */
function catalogPayload(array $override = []): array
{
    return array_merge([
        'sku' => 'SKU-100',
        'name' => 'Blue Shirt',
        'description' => 'Soft cotton',
        'price_amount' => 1999,
        'currency' => 'USD',
        'category_uuid' => null,
    ], $override);
}

it('creates a draft product with defaults and dispatches ProductChanged', function () {
    Event::fake([ProductChanged::class]);
    $actor = CatalogKit::staff();
    $category = CatalogKit::category();

    $product = catalogProducts()->create($actor, catalogPayload(['category_uuid' => $category->uuid]));

    expect($product->status)->toBe('draft')
        ->and($product->slug)->toBe('blue-shirt')
        ->and($product->currency)->toBe('USD')
        ->and($product->price_amount)->toBe(1999)
        ->and($product->category_id)->toBe($category->id)
        ->and($product->created_by)->toBe($actor->id)
        ->and($product->updated_by)->toBe($actor->id);

    Event::assertDispatched(ProductChanged::class, fn (ProductChanged $e) => $e->subjectUuid === $product->uuid
        && $e->change === 'created' && $e->actorId === $actor->id);
});

it('uses the default currency when none is given', function () {
    $payload = catalogPayload();
    unset($payload['currency']);

    $product = catalogProducts()->create(CatalogKit::staff(), $payload);

    expect($product->currency)->toBe(config('shop.default_currency'));
});

it('rejects invalid product data', function (array $override, string $field) {
    $actor = CatalogKit::staff();
    CatalogKit::product(['sku' => 'TAKEN']);
    $before = Product::withTrashed()->count();

    try {
        catalogProducts()->create($actor, catalogPayload($override));
        $this->fail('expected a validation error');
    } catch (DomainValidationException $e) {
        expect($e->fields())->toHaveKey($field);
    }

    expect(Product::withTrashed()->count())->toBe($before);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'missing sku' => [['sku' => ''], 'sku'],
    'duplicate sku' => [['sku' => 'TAKEN'], 'sku'],
    'negative price' => [['price_amount' => -1], 'price_amount'],
    'float price' => [['price_amount' => 19.99], 'price_amount'],
    'unknown currency' => [['currency' => 'XXX'], 'currency'],
    'unknown status' => [['status' => 'bogus'], 'status'],
    'unknown category' => [['category_uuid' => '11111111-1111-4111-8111-111111111111'], 'category_uuid'],
]);

it('rejects a sku that belongs to a soft-deleted product', function () {
    $old = CatalogKit::product(['sku' => 'OLD-1']);
    $old->delete();

    expect(fn () => catalogProducts()->create(CatalogKit::staff(), catalogPayload(['sku' => 'OLD-1'])))
        ->toThrow(DomainValidationException::class);
});

it('updates fields but never the sku, slug or status', function () {
    Event::fake([ProductChanged::class]);
    $actor = CatalogKit::staff();
    $product = CatalogKit::product(['sku' => 'KEEP-1', 'name' => 'Old', 'status' => 'draft']);
    $slug = $product->slug;

    $updated = catalogProducts()->update($actor, $product, [
        'name' => 'New Name', 'description' => 'New text', 'price_amount' => 500, 'currency' => 'EUR',
        'sku' => 'HACK-1', 'status' => 'active', 'slug' => 'hacked',
    ]);

    expect($updated->name)->toBe('New Name')
        ->and($updated->price_amount)->toBe(500)
        ->and($updated->currency)->toBe('EUR')
        ->and($updated->sku)->toBe('KEEP-1')
        ->and($updated->slug)->toBe($slug)
        ->and($updated->status)->toBe('draft')
        ->and($updated->updated_by)->toBe($actor->id);

    Event::assertDispatched(ProductChanged::class, fn (ProductChanged $e) => $e->change === 'updated' && $e->subjectUuid === $product->uuid);
});

it('rejects invalid update data and updates of deleted products', function () {
    $actor = CatalogKit::staff();
    $product = CatalogKit::product();

    expect(fn () => catalogProducts()->update($actor, $product, ['name' => '', 'price_amount' => 100, 'currency' => 'USD']))
        ->toThrow(DomainValidationException::class);

    $product->delete();

    expect(fn () => catalogProducts()->update($actor, $product, ['name' => 'X', 'price_amount' => 100, 'currency' => 'USD']))
        ->toThrow(ConflictException::class);
});

it('changes status within the configured list', function () {
    Event::fake([ProductChanged::class]);
    $actor = CatalogKit::staff();
    $product = CatalogKit::product(['status' => 'draft']);

    $changed = catalogProducts()->changeStatus($actor, $product, 'active');

    expect($changed->status)->toBe('active')
        ->and($changed->updated_by)->toBe($actor->id);

    Event::assertDispatched(ProductChanged::class, fn (ProductChanged $e) => $e->change === 'updated'
        && ($e->details['from'] ?? null) === 'draft' && ($e->details['to'] ?? null) === 'active');

    expect(fn () => catalogProducts()->changeStatus($actor, $product, 'bogus'))->toThrow(DomainValidationException::class);

    $product->delete();

    expect(fn () => catalogProducts()->changeStatus($actor, $product, 'inactive'))->toThrow(ConflictException::class);
});

it('soft deletes and restores with events and conflicts', function () {
    Event::fake([ProductChanged::class]);
    $actor = CatalogKit::staff();
    $product = CatalogKit::product();

    catalogProducts()->delete($actor, $product);

    expect(Product::find($product->id))->toBeNull()
        ->and(Product::withTrashed()->find($product->id))->not->toBeNull();
    Event::assertDispatched(ProductChanged::class, fn (ProductChanged $e) => $e->change === 'deleted');

    expect(fn () => catalogProducts()->delete($actor, $product))->toThrow(ConflictException::class);

    catalogProducts()->restore($actor, $product);

    expect(Product::find($product->id))->not->toBeNull();
    Event::assertDispatched(ProductChanged::class, fn (ProductChanged $e) => $e->change === 'restored');

    expect(fn () => catalogProducts()->restore($actor, $product))->toThrow(ConflictException::class);
});

it('writes activity rows with the acting user as causer', function () {
    $actor = CatalogKit::staff();
    $this->actingAs($actor);

    $product = catalogProducts()->create($actor, catalogPayload());
    catalogProducts()->delete($actor, $product);
    catalogProducts()->restore($actor, $product);

    $events = Activity::where('subject_type', $product->getMorphClass())->where('subject_id', $product->id)
        ->pluck('event')->all();

    expect($events)->toContain('created')->toContain('deleted')->toContain('restored');
    expect(Activity::where('subject_id', $product->id)->where('event', 'created')->first()->causer_id)->toBe($actor->id);
});

it('stores an uploaded image under a random name and removes the old file', function () {
    Storage::fake(config('shop.uploads.disk'));
    $actor = CatalogKit::staff();
    $product = CatalogKit::product();

    $first = catalogProducts()->uploadImage($actor, $product, UploadedFile::fake()->create('my-photo.jpg', 100, 'image/jpeg'));
    $firstPath = $first->image_path;

    expect($firstPath)->not->toBeNull()
        ->and($firstPath)->toStartWith(config('shop.uploads.directory').'/')
        ->and($firstPath)->not->toContain('my-photo')
        ->and($firstPath)->toEndWith('.jpg');
    Storage::disk(config('shop.uploads.disk'))->assertExists($firstPath);

    $second = catalogProducts()->uploadImage($actor, $first, UploadedFile::fake()->create('other.png', 100, 'image/png'));

    expect($second->image_path)->not->toBe($firstPath)
        ->and($second->updated_by)->toBe($actor->id);
    Storage::disk(config('shop.uploads.disk'))->assertMissing($firstPath);
    Storage::disk(config('shop.uploads.disk'))->assertExists($second->image_path);
});

it('rejects bad image mime, oversize files and deleted products', function () {
    Storage::fake(config('shop.uploads.disk'));
    $actor = CatalogKit::staff();
    $product = CatalogKit::product();

    foreach ([
        UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
        UploadedFile::fake()->create('big.jpg', (int) config('shop.uploads.max_kb') + 1, 'image/jpeg'),
    ] as $file) {
        try {
            catalogProducts()->uploadImage($actor, $product, $file);
            $this->fail('expected a validation error');
        } catch (DomainValidationException $e) {
            expect($e->fields())->toHaveKey('image');
        }
    }

    expect($product->fresh()->image_path)->toBeNull()
        ->and(Storage::disk(config('shop.uploads.disk'))->allFiles())->toBe([]);

    $product->delete();

    expect(fn () => catalogProducts()->uploadImage($actor, $product, UploadedFile::fake()->create('a.jpg', 10, 'image/jpeg')))
        ->toThrow(ConflictException::class);
});
