<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Contracts;

use App\Modules\Catalog\DTO\ProductSnapshot;

interface ProductCatalog
{
    /**
     * Snapshots keyed by product uuid. Deleted products are returned with deleted=true.
     *
     * @param  array<int, string>  $uuids
     * @return array<string, ProductSnapshot>
     */
    public function lookup(array $uuids): array;

    /**
     * For every requested uuid: true when the product exists and is not deleted.
     *
     * @param  array<int, string>  $uuids
     * @return array<string, bool>
     */
    public function exists(array $uuids): array;
}
