<?php

declare(strict_types=1);

use App\Modules\Catalog\Contracts\ProductCatalog;
use App\Modules\Catalog\DTO\ProductSnapshot;
use App\Modules\Catalog\Stats\CatalogStats;
use App\Support\Contracts\StatsProvider;
use App\Support\Exceptions\DomainValidationException;
use Tests\Support\CatalogKit;

function catalogContract(): ProductCatalog
{
    return app(ProductCatalog::class);
}

it('binds the contract in the container', function () {
    expect(catalogContract())->toBeInstanceOf(ProductCatalog::class);
});

it('looks up snapshots keyed by uuid', function () {
    $a = CatalogKit::product(['sku' => 'A-1', 'name' => 'Alpha', 'price_amount' => 1250, 'currency' => 'EUR', 'status' => 'active']);
    $b = CatalogKit::product(['status' => 'draft']);

    $result = catalogContract()->lookup([$a->uuid, $b->uuid, '11111111-1111-4111-8111-111111111111']);

    expect($result)->toHaveCount(2)
        ->and($result[$a->uuid])->toBeInstanceOf(ProductSnapshot::class)
        ->and($result[$a->uuid]->uuid)->toBe($a->uuid)
        ->and($result[$a->uuid]->name)->toBe('Alpha')
        ->and($result[$a->uuid]->sku)->toBe('A-1')
        ->and($result[$a->uuid]->priceAmount)->toBe(1250)
        ->and($result[$a->uuid]->currency)->toBe('EUR')
        ->and($result[$a->uuid]->status)->toBe('active')
        ->and($result[$a->uuid]->deleted)->toBeFalse()
        ->and($result[$b->uuid]->status)->toBe('draft');
});

it('returns deleted products flagged as deleted', function () {
    $product = CatalogKit::product(['status' => 'active']);
    $product->delete();

    $result = catalogContract()->lookup([$product->uuid]);

    expect($result[$product->uuid]->deleted)->toBeTrue();
});

it('returns an empty array for an empty lookup', function () {
    expect(catalogContract()->lookup([]))->toBe([]);
});

it('rejects lookups and exists checks over the configured cap', function () {
    config(['shop.lookup_max_uuids' => 3]);
    $uuids = array_map(fn (int $i) => sprintf('00000000-0000-4000-8000-%012d', $i), range(1, 4));

    expect(fn () => catalogContract()->lookup($uuids))->toThrow(DomainValidationException::class)
        ->and(fn () => catalogContract()->exists($uuids))->toThrow(DomainValidationException::class);

    expect(catalogContract()->lookup(array_slice($uuids, 0, 3)))->toBe([]);
});

it('reports existence for every requested uuid and treats deleted as missing', function () {
    $live = CatalogKit::product(['status' => 'draft']);
    $gone = CatalogKit::product();
    $gone->delete();
    $unknown = '11111111-1111-4111-8111-111111111111';

    expect(catalogContract()->exists([$live->uuid, $gone->uuid, $unknown]))->toBe([
        $live->uuid => true,
        $gone->uuid => false,
        $unknown => false,
    ]);
});

it('computes catalog stats', function () {
    CatalogKit::product(['status' => 'active']);
    CatalogKit::product(['status' => 'active']);
    CatalogKit::product(['status' => 'inactive']);
    CatalogKit::product(['status' => 'draft']);
    CatalogKit::product(['status' => 'draft']);
    CatalogKit::product(['status' => 'draft']);
    CatalogKit::product(['status' => 'active'])->delete();
    CatalogKit::category();
    CatalogKit::category();
    CatalogKit::category()->delete();

    $stats = app(CatalogStats::class);

    expect($stats)->toBeInstanceOf(StatsProvider::class)
        ->and($stats->name())->toBe('catalog')
        ->and($stats->stats())->toBe([
            'total' => 6, 'active' => 2, 'inactive' => 1, 'draft' => 3, 'deleted' => 1, 'categories' => 2,
        ]);
});

it('tags the stats provider with module.stats', function () {
    $names = [];

    foreach (app()->tagged('module.stats') as $provider) {
        $names[] = $provider->name();
    }

    expect($names)->toContain('catalog');
});
