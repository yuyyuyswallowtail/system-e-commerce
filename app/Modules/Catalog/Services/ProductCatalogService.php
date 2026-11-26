<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Contracts\ProductCatalog;
use App\Modules\Catalog\DTO\ProductSnapshot;
use App\Modules\Catalog\Models\Product;
use App\Support\Exceptions\DomainValidationException;

final class ProductCatalogService implements ProductCatalog
{
    public function lookup(array $uuids): array
    {
        $uuids = $this->normalize($uuids);

        if ($uuids === []) {
            return [];
        }

        $products = Product::withTrashed()->whereIn('uuid', $uuids)->get()->keyBy('uuid');
        $result = [];

        foreach ($uuids as $uuid) {
            $product = $products->get($uuid);

            if ($product === null) {
                continue;
            }

            $result[$uuid] = new ProductSnapshot(
                $product->uuid,
                $product->name,
                $product->sku,
                $product->price_amount,
                $product->currency,
                $product->status,
                $product->trashed(),
            );
        }

        return $result;
    }

    public function exists(array $uuids): array
    {
        $uuids = $this->normalize($uuids);

        if ($uuids === []) {
            return [];
        }

        $found = Product::query()->whereIn('uuid', $uuids)->pluck('uuid')->flip();
        $result = [];

        foreach ($uuids as $uuid) {
            $result[$uuid] = $found->has($uuid);
        }

        return $result;
    }

    /**
     * @param  array<int, mixed>  $uuids
     * @return array<int, string>
     */
    private function normalize(array $uuids): array
    {
        $uuids = array_values(array_unique(array_filter($uuids, 'is_string')));

        if (count($uuids) > (int) config('shop.lookup_max_uuids')) {
            throw new DomainValidationException(['uuids' => 'Too many uuids requested.']);
        }

        return $uuids;
    }
}
