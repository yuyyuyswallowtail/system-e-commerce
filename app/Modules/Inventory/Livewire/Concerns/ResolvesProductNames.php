<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Livewire\Concerns;

use App\Modules\Catalog\Contracts\ProductCatalog;
use Throwable;

trait ResolvesProductNames
{
    /**
     * Enrichment only: any failure returns no names and the views fall back to the uuid.
     *
     * @param  array<int, string>  $uuids
     * @return array<string, string>
     */
    protected function productNames(array $uuids): array
    {
        if ($uuids === []) {
            return [];
        }

        try {
            $snapshots = app(ProductCatalog::class)->lookup($uuids);
        } catch (Throwable) {
            return [];
        }

        $names = [];

        foreach ($snapshots as $uuid => $snapshot) {
            $names[(string) $uuid] = $snapshot->name;
        }

        return $names;
    }
}
