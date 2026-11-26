<?php

declare(strict_types=1);

use App\Modules\Catalog\Stats\CatalogStats;
use App\Modules\Dashboard\Services\DashboardService;
use App\Support\Contracts\StatsProvider;

it('tags the six real providers', function (): void {
    $names = collect(app()->tagged(StatsProvider::TAG))->map(fn ($p) => $p->name())->sort()->values()->all();

    expect($names)->toBe(['catalog', 'comments', 'content', 'inventory', 'orders', 'payments']);
});

it('serves every block from the real providers on an empty database', function (): void {
    $service = app(DashboardService::class);

    foreach (['sales', 'stock', 'content', 'summary'] as $block) {
        $result = $service->forBlock($block);

        expect($result['warnings'])->toBe([])->and($result['data'])->toBeArray();
    }

    expect($service->forBlock('stock')['data']['catalog'])->toBe(app(CatalogStats::class)->stats());
});
