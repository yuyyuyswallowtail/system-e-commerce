<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tests\Support\LayoutProbe;
use Tests\Support\LayoutProbeStorefront;

beforeEach(function () {
    Route::middleware('web')->group(function () {
        Route::get('/__probe/admin', LayoutProbe::class);
        Route::get('/__probe/storefront', LayoutProbeStorefront::class);
    });
});

it('links css/theme.css once, after the AdminLTE stylesheet', function (string $uri) {
    $html = $this->get($uri)->assertOk()->getContent();

    $adminlte = strpos($html, 'vendor/adminlte/dist/css/adminlte.min.css');
    $theme = strpos($html, 'css/theme.css');

    expect($adminlte)->not->toBeFalse();
    expect($theme)->not->toBeFalse();
    expect($theme)->toBeGreaterThan($adminlte);
    expect(substr_count($html, 'css/theme.css'))->toBe(1);
})->with(['/__probe/admin', '/__probe/storefront']);

it('loads no remote font', function (string $uri) {
    $this->get($uri)
        ->assertOk()
        ->assertDontSee('fonts.googleapis.com', false)
        ->assertDontSee('fonts.gstatic.com', false);
})->with(['/__probe/admin', '/__probe/storefront']);

it('ships a theme.css that git does not ignore', function () {
    expect(is_file(base_path('public/css/theme.css')))->toBeTrue();
});
