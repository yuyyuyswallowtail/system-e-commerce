<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\LayoutProbeStorefront;

function probeUser(int $id): User
{
    return (new User)->forceFill(['id' => $id, 'name' => 'Probe User', 'email' => "probe{$id}@example.test", 'status' => 'active', 'deleted_at' => null]);
}

beforeEach(function () {
    Route::middleware('web')->group(function () {
        Route::get('/__probe/storefront', LayoutProbeStorefront::class);
        Route::get('/__probe/notifications', fn () => 'ok')->name('notifications.index');
    });
    app('router')->getRoutes()->refreshNameLookups();

    if (! Schema::hasTable('notifications')) {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    DB::table('notifications')->where('type', 'probe')->delete();

    $row = fn (int $userId, ?string $readAt) => [
        'id' => (string) Str::uuid(),
        'type' => 'probe',
        'notifiable_type' => User::class,
        'notifiable_id' => $userId,
        'data' => '{}',
        'read_at' => $readAt,
    ];

    DB::table('notifications')->insert([
        $row(1, null),
        $row(1, null),
        $row(1, '2026-01-01 00:00:00'),
        $row(2, null),
    ]);
});

afterEach(function () {
    DB::table('notifications')->where('type', 'probe')->delete();
});

it('shows the user name, the unread count and the logout form to a signed-in user', function () {
    $this->actingAs(probeUser(1))
        ->get('/__probe/storefront')
        ->assertOk()
        ->assertSee('Probe User')
        ->assertSee('data-notification-badge>2</span>', false)
        ->assertSee('Log out');
});

it('hides the badge when the signed-in user has no unread notifications', function () {
    $this->actingAs(probeUser(3))
        ->get('/__probe/storefront')
        ->assertOk()
        ->assertSee('Probe User')
        ->assertDontSee('data-notification-badge', false);
});
