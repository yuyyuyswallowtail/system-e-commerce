<?php

declare(strict_types=1);

use App\Modules\Dashboard\Services\DashboardService;
use Illuminate\Support\Facades\Cache;
use Tests\Support\FakeStatsProvider;

function fakeProviders(): array
{
    $providers = [];

    foreach (['orders', 'payments', 'inventory', 'catalog', 'content', 'comments'] as $name) {
        $providers[$name] = new FakeStatsProvider($name, ['total' => strlen($name)]);
    }

    return $providers;
}

function dashboardService(array $providers): DashboardService
{
    return new DashboardService(array_values($providers));
}

beforeEach(function (): void {
    Cache::flush();
    config(['shop.stats_cache_ttl' => 60]);
});

it('returns the provider arrays of a block when all providers work', function (): void {
    $result = dashboardService(fakeProviders())->forBlock('sales');

    expect($result['warnings'])->toBe([])
        ->and($result['data'])->toBe([
            'orders' => ['total' => 6],
            'payments' => ['total' => 8],
        ]);
});

it('gives null and a warning for a throwing provider', function (): void {
    $providers = fakeProviders();
    $providers['payments']->setResult(new RuntimeException('secret-detail'));

    $result = dashboardService($providers)->forBlock('sales');

    expect($result['data']['payments'])->toBeNull()
        ->and($result['data']['orders'])->toBe(['total' => 6])
        ->and($result['warnings'])->toBe(['payments']);
});

it('returns null data when all providers of a block fail and keeps other blocks working', function (): void {
    $providers = fakeProviders();
    $providers['orders']->setResult(new RuntimeException('a'));
    $providers['payments']->setResult(new RuntimeException('b'));
    $service = dashboardService($providers);

    $sales = $service->forBlock('sales');
    $stock = $service->forBlock('stock');

    expect($sales['data'])->toBeNull()
        ->and($sales['warnings'])->toBe(['orders', 'payments'])
        ->and($stock['warnings'])->toBe([])
        ->and($stock['data'])->not->toBeNull();
});

it('summary covers every block provider', function (): void {
    $providers = fakeProviders();
    $providers['comments']->setResult(new RuntimeException('x'));

    $result = dashboardService($providers)->forBlock('summary');

    expect(array_keys($result['data']))->toBe(['orders', 'payments', 'inventory', 'catalog', 'content', 'comments'])
        ->and($result['data']['comments'])->toBeNull()
        ->and($result['warnings'])->toBe(['comments']);
});

it('summary returns null data when every provider fails', function (): void {
    $providers = fakeProviders();

    foreach ($providers as $provider) {
        $provider->setResult(new RuntimeException('x'));
    }

    $result = dashboardService($providers)->forBlock('summary');

    expect($result['data'])->toBeNull()->and($result['warnings'])->toHaveCount(6);
});

it('rejects an unknown block', function (): void {
    dashboardService(fakeProviders())->forBlock('nope');
})->throws(InvalidArgumentException::class);

it('serves the second call from the cache', function (): void {
    $providers = fakeProviders();
    $service = dashboardService($providers);

    $service->forBlock('sales');
    $service->forBlock('sales');

    expect($providers['orders']->calls)->toBe(1)->and($providers['payments']->calls)->toBe(1);
});

it('does not cache when the ttl is 0', function (): void {
    config(['shop.stats_cache_ttl' => 0]);
    $providers = fakeProviders();
    $service = dashboardService($providers);

    $service->forBlock('sales');
    $service->forBlock('sales');

    expect($providers['orders']->calls)->toBe(2);
});

it('never caches failures', function (): void {
    $providers = fakeProviders();
    $providers['orders']->setResult(new RuntimeException('down'));
    $service = dashboardService($providers);

    $service->forBlock('sales');
    $service->forBlock('sales');
    expect($providers['orders']->calls)->toBe(2);

    $providers['orders']->setResult(['total' => 9]);
    $recovered = $service->forBlock('sales');
    $service->forBlock('sales');

    expect($recovered['data']['orders'])->toBe(['total' => 9])
        ->and($recovered['warnings'])->toBe([])
        ->and($providers['orders']->calls)->toBe(3);
});

it('uses a cache key without user data', function (): void {
    dashboardService(fakeProviders())->forBlock('sales');

    expect(Cache::has('dashboard:stats:orders'))->toBeTrue();
});

it('refresh forgets every provider cache entry', function (): void {
    $providers = fakeProviders();
    $service = dashboardService($providers);

    $service->forBlock('summary');
    $service->refresh();
    $service->forBlock('summary');

    expect($providers['orders']->calls)->toBe(2)->and($providers['comments']->calls)->toBe(2);
});

it('is bound in the container with the tagged providers', function (): void {
    expect(app(DashboardService::class))->toBeInstanceOf(DashboardService::class);
});
