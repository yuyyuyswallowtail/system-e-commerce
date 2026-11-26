<?php

declare(strict_types=1);

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Tests\Support\ContentKit;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

/**
 * URIs (first segment or full uri) that are public on purpose.
 * Why: the storefront, public articles and products, the auth entry points of Fortify,
 * the Livewire transport and framework endpoints must be reachable by guests.
 *
 * @return array<int, string>
 */
function publicUriAllowList(): array
{
    return [
        '/',
        'products',
        'products/{product}',
        'articles',
        'articles/{article}',
        'login',
        'register',
        'forgot-password',
        'reset-password',
        'reset-password/{token}',
        'two-factor-challenge',
        'up',
        'storage/{path}',
        // AdminLTE package route that only toggles the dark mode flag; it reads and writes no domain data.
        'adminlte/darkmode/toggle',
        'livewire-*',
        'livewire/*',
    ];
}

function isPublicUri(string $uri): bool
{
    $normalized = preg_replace('/\{(\w+)(:\w+)?\}/', '{$1}', $uri) ?? $uri;

    foreach (publicUriAllowList() as $pattern) {
        if ($normalized === $pattern || ($pattern !== '/' && fnmatch($pattern, $normalized))) {
            return true;
        }
    }

    return false;
}

/**
 * @return array<int, string>
 */
function routeMiddleware(LaravelRoute $route): array
{
    return array_map('strval', $route->gatherMiddleware());
}

it('protects every non-public route with auth and every admin route with can', function () {
    $problems = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        $uri = $route->uri();
        $middleware = routeMiddleware($route);
        $hasAuth = collect($middleware)->contains(fn (string $m) => $m === 'auth' || str_starts_with($m, 'auth:') || str_contains($m, 'Authenticate'));
        $hasCan = collect($middleware)->contains(fn (string $m) => str_starts_with($m, 'can:') || str_contains($m, 'Authorize:'));

        if (str_starts_with($uri, 'admin/') || $uri === 'admin') {
            if (! $hasAuth || ! $hasCan) {
                $problems[] = "admin route without auth+can: {$uri}";
            }

            continue;
        }

        if (! isPublicUri($uri) && ! $hasAuth && ! in_array('guest', $middleware, true) && ! collect($middleware)->contains(fn (string $m) => str_starts_with($m, 'guest'))) {
            $problems[] = "route without auth: {$uri}";
        }
    }

    expect($problems)->toBe([]);
});

it('has no duplicated route names', function () {
    $names = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        $name = $route->getName();

        if ($name !== null && $name !== '') {
            $names[] = $name;
        }
    }

    $duplicates = array_keys(array_filter(array_count_values($names), fn (int $count) => $count > 1));

    expect($duplicates)->toBe([]);
});

it('answers every admin GET route with a redirect for guests, 403 for members and 200 for a super admin', function () {
    $superAdmin = IdentityKit::user('super_admin');
    $member = IdentityKit::user('member');
    [$shopper, $products] = OrderKit::shopper();
    $order = OrderKit::place($shopper);
    $article = ContentKit::article();

    $values = [
        'user' => $member->uuid,
        'article' => $article->uuid,
        'product' => $products[0]->uuid,
        'productUuid' => $products[0]->uuid,
        'order' => $order->uuid,
    ];

    $checked = 0;

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if (! in_array('GET', $route->methods(), true) || ! str_starts_with($route->uri(), 'admin/')) {
            continue;
        }

        $parameters = [];

        foreach ($route->parameterNames() as $parameter) {
            expect($values)->toHaveKey($parameter);
            $parameters[$parameter] = $values[$parameter];
        }

        $url = route($route->getName(), $parameters, false);

        $this->get($url)->assertRedirect();
        $this->actingAs($member)->get($url)->assertForbidden();
        $this->actingAs($superAdmin)->get($url)->assertOk();
        auth()->logout();

        $checked++;
    }

    expect($checked)->toBeGreaterThan(20);
});
