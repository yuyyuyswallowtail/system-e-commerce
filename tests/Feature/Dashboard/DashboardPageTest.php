<?php

declare(strict_types=1);

use App\Modules\Dashboard\Livewire\Admin\Dashboard;
use App\Modules\Dashboard\Services\DashboardService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\Support\FakeStatsProvider;
use Tests\Support\IdentityKit;

function dashboardFakes(array $overrides = []): array
{
    $defaults = [
        'orders' => ['total' => 3, 'by_status' => ['awaiting_payment' => 1, 'paid' => 2], 'revenue_amount' => 123456, 'currency' => 'USD'],
        'payments' => ['total' => 2, 'by_status' => ['paid' => 2], 'paid_amount' => 5000, 'currency' => 'USD'],
        'inventory' => ['items' => 5, 'low_stock' => 2, 'out_of_stock' => 1],
        'catalog' => ['total' => 6, 'active' => 4],
        'content' => ['total' => 4, 'published' => 3],
        'comments' => ['total' => 9, 'visible' => 7],
    ];

    $providers = [];

    foreach ($overrides + $defaults as $name => $result) {
        $providers[] = new FakeStatsProvider($name, $result);
    }

    return $providers;
}

function bindDashboard(array $overrides = []): void
{
    app()->instance(DashboardService::class, new DashboardService(dashboardFakes($overrides)));
}

beforeEach(function (): void {
    Cache::flush();
    config(['shop.stats_cache_ttl' => 60]);
});

it('redirects guests to login', function (): void {
    $this->get('/admin/dashboard')->assertRedirect(route('login'));
});

it('forbids a member', function (): void {
    $this->actingAs(IdentityKit::user())->get('/admin/dashboard')->assertForbidden();
});

it('forbids a user holding another permission', function (): void {
    $this->actingAs(IdentityKit::userWithPermissions(['orders.read']))->get('/admin/dashboard')->assertForbidden();
});

it('allows a user holding dashboard.read', function (): void {
    $this->actingAs(IdentityKit::userWithPermissions(['dashboard.read']))->get('/admin/dashboard')->assertOk();
});

it('renders the small boxes with formatted money', function (): void {
    bindDashboard();

    Livewire::actingAs(IdentityKit::userWithPermissions(['dashboard.read']))
        ->test(Dashboard::class)
        ->assertSee('1,234.56 USD')
        ->assertSee('50.00 USD')
        ->assertSee('Low stock')
        ->assertSee('Out of stock')
        ->assertSee('Active products')
        ->assertSee('Published articles')
        ->assertSee('Visible comments')
        ->assertSee('Orders by status')
        ->assertSee('Awaiting payment')
        ->assertDontSee('Unavailable');
});

it('shows a warning alert listing unavailable providers', function (): void {
    bindDashboard(['payments' => new RuntimeException('boom')]);

    Livewire::actingAs(IdentityKit::userWithPermissions(['dashboard.read']))
        ->test(Dashboard::class)
        ->assertSee('Unavailable')
        ->assertSee('payments')
        ->assertSee('1,234.56 USD');
});

it('shows an error state when every provider fails', function (): void {
    $names = ['orders', 'payments', 'inventory', 'catalog', 'content', 'comments'];
    bindDashboard(array_fill_keys($names, new RuntimeException('boom')));

    Livewire::actingAs(IdentityKit::userWithPermissions(['dashboard.read']))
        ->test(Dashboard::class)
        ->assertSee('Dashboard data is unavailable');
});

it('refresh clears the cache before reloading', function (): void {
    bindDashboard();
    $service = app(DashboardService::class);
    $service->forBlock('summary');
    $key = config('shop.dashboard.cache_prefix').'orders';

    expect(Cache::has($key))->toBeTrue();

    $service->refresh();

    expect(Cache::has($key))->toBeFalse();
});

it('forbids a member from mounting and refreshing', function (): void {
    bindDashboard();

    Livewire::actingAs(IdentityKit::user())->test(Dashboard::class)->assertForbidden();
});

it('has a menu entry guarded by dashboard.read', function (): void {
    $entry = collect(config('adminlte.menu'))->first(fn ($item) => is_array($item) && ($item['url'] ?? null) === 'admin/dashboard');

    expect($entry)->not->toBeNull()->and($entry['can'])->toBe('dashboard.read');
});

it('refresh action reloads the stats from the providers', function (): void {
    $providers = dashboardFakes();
    app()->instance(DashboardService::class, new DashboardService($providers));

    $component = Livewire::actingAs(IdentityKit::userWithPermissions(['dashboard.read']))->test(Dashboard::class);

    expect($providers[0]->calls)->toBe(1);

    $component->call('refresh');

    expect($providers[0]->calls)->toBe(2);
});

it('forbids the refresh action for a user without dashboard.read', function (): void {
    bindDashboard();

    $component = Livewire::actingAs(IdentityKit::userWithPermissions(['dashboard.read']))->test(Dashboard::class);

    $this->actingAs(IdentityKit::user());

    $component->call('refresh')->assertForbidden();
});
