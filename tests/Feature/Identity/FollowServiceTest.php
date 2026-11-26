<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Identity\Services\FollowService;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

it('follows, is idempotent and counts', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $c = User::factory()->create();
    $service = app(FollowService::class);

    $service->follow($a, $b);
    $service->follow($a, $b);
    $service->follow($c, $b);

    expect($service->isFollowing($a, $b))->toBeTrue()
        ->and($service->isFollowing($b, $a))->toBeFalse()
        ->and($service->counts($b))->toBe(['followers' => 2, 'following' => 0])
        ->and($service->counts($a))->toBe(['followers' => 0, 'following' => 1])
        ->and(DB::table('user_follows')->count())->toBe(2)
        ->and(Activity::where('event', 'followed')->first()->causer_id)->toBe($a->id);
});

it('unfollows and is idempotent', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $service = app(FollowService::class);

    $service->follow($a, $b);
    $service->unfollow($a, $b);
    $service->unfollow($a, $b);

    expect($service->isFollowing($a, $b))->toBeFalse()
        ->and(DB::table('user_follows')->count())->toBe(0);
});

it('rejects following yourself', function () {
    $a = User::factory()->create();

    app(FollowService::class)->follow($a, $a);
})->throws(DomainValidationException::class);
