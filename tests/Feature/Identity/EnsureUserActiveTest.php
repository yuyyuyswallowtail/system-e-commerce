<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Http\Middleware\EnsureUserActive;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('web')->get('/_probe', fn () => 'ok');
});

it('registers the alias and appends the middleware to the web group', function () {
    $router = app('router');

    expect($router->getMiddleware())->toHaveKey('user.active')
        ->and($router->getMiddlewareGroups()['web'])->toContain(EnsureUserActive::class);
});

it('lets guests and active users through', function () {
    $this->get('/_probe')->assertOk();
    $this->actingAs(User::factory()->create()->refresh())->get('/_probe')->assertOk();
});

it('logs out suspended, banned and invalid status users', function (string $status) {
    $user = User::factory()->create(['status' => $status])->refresh();

    $this->actingAs($user)->get('/_probe')->assertRedirect(route('login'));
    $this->assertGuest();
})->with(['suspended', 'banned', 'weird']);

it('logs out a soft deleted user', function () {
    $user = User::factory()->create()->refresh();
    $user->delete();

    $this->actingAs($user)->get('/_probe')->assertRedirect(route('login'));
    $this->assertGuest();
});
