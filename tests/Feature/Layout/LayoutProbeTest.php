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

it('configures the default Livewire layout', function () {
    expect(config('livewire.component_layout'))->toBe('layouts::app');
});

it('renders a full-page component inside the admin layout', function () {
    $this->get('/__probe/admin')
        ->assertOk()
        ->assertSee('layout probe content')
        ->assertSee('main-sidebar', false)
        ->assertDontSee('data-layout="storefront"', false);
});

it('renders a full-page component inside the storefront layout', function () {
    $this->get('/__probe/storefront')
        ->assertOk()
        ->assertSee('layout probe content')
        ->assertSee('data-layout="storefront"', false)
        ->assertDontSee('main-sidebar', false);
});

it('shows no notification badge to guests', function () {
    $this->get('/__probe/storefront')
        ->assertOk()
        ->assertDontSee('data-notification-badge', false);
});
