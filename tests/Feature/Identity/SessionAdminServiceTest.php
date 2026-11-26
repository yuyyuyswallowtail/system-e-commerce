<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Services\SessionAdminService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Tests\Support\IdentityKit;

function sessionsSvc(): SessionAdminService
{
    return app(SessionAdminService::class);
}

it('lists sessions joined with users and skips guest sessions', function () {
    $user = User::factory()->create(['name' => 'Ann']);
    IdentityKit::session('s1', $user->id);
    IdentityKit::session('guest', null);

    $page = sessionsSvc()->paginate(15);
    $row = $page->items()[0];

    expect($page->total())->toBe(1)
        ->and($row->id)->toBe('s1')
        ->and($row->user_name)->toBe('Ann')
        ->and($row->user_email)->toBe($user->email)
        ->and($row->ip_address)->toBe('127.0.0.1')
        ->and($row->user_agent)->toBe('PestAgent')
        ->and((int) $row->last_activity)->toBeInt();
});

it('lists online users inside the presence window only', function () {
    Carbon::setTestNow('2026-11-26 10:00:00');
    $window = (int) config('shop.presence_window_seconds');
    $online = User::factory()->create();
    $offline = User::factory()->create();
    IdentityKit::session('a', $online->id, now()->subSeconds($window - 10)->timestamp);
    IdentityKit::session('b', $online->id, now()->subSeconds(5)->timestamp);
    IdentityKit::session('c', $offline->id, now()->subSeconds($window + 10)->timestamp);

    $users = sessionsSvc()->onlineUsers();

    expect($users)->toHaveCount(1)
        ->and($users->first()->user_id)->toBe($online->id);

    Carbon::setTestNow();
});

it('revokes one session with sessions.revoke and audits it', function () {
    $admin = IdentityKit::user('admin');
    $member = IdentityKit::user('member');
    $user = User::factory()->create();
    IdentityKit::session('s1', $user->id);
    IdentityKit::session('s2', $user->id);

    expect(fn () => sessionsSvc()->revoke($member, 's1'))->toThrow(AuthorizationException::class);

    sessionsSvc()->revoke($admin, 's1');

    expect(DB::table('sessions')->where('id', 's1')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 's2')->exists())->toBeTrue()
        ->and(Activity::where('event', 'session_revoked')->first()->causer_id)->toBe($admin->id);
});

it('revokes all sessions of a user', function () {
    $admin = IdentityKit::user('admin');
    $user = User::factory()->create();
    $other = User::factory()->create();
    IdentityKit::session('s1', $user->id);
    IdentityKit::session('s2', $user->id);
    IdentityKit::session('s3', $other->id);

    expect(sessionsSvc()->revokeAllForUser($admin, $user))->toBe(2)
        ->and(DB::table('sessions')->count())->toBe(1);
});
