<?php

declare(strict_types=1);

use App\Support\Contracts\StatsProvider;
use App\Support\Events\DomainEvent;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Carbon;

function makeDomainEvent(array $details = ['k' => 'v']): DomainEvent
{
    return new class('order', 'abc', 5, 7, $details) extends DomainEvent {};
}

it('implements the after commit interface', function () {
    expect(makeDomainEvent())->toBeInstanceOf(ShouldDispatchAfterCommit::class);
});

it('generates a v4 event uuid and a fresh uuid per event', function () {
    $first = makeDomainEvent();
    $second = makeDomainEvent();

    expect($first->eventUuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/')
        ->and($first->eventUuid)->not->toBe($second->eventUuid);
});

it('captures time, actor, user, subject and details', function () {
    Carbon::setTestNow('2026-01-02 03:04:05');

    $event = makeDomainEvent(['k' => 'v']);

    expect($event->occurredAt->toDateTimeString())->toBe('2026-01-02 03:04:05')
        ->and($event->actorId)->toBe(5)
        ->and($event->userId)->toBe(7)
        ->and($event->subjectType)->toBe('order')
        ->and($event->subjectUuid)->toBe('abc')
        ->and($event->details)->toBe(['k' => 'v']);

    Carbon::setTestNow();
});

it('allows an event without actor or user', function () {
    $event = new class('order', 'abc') extends DomainEvent {};

    expect($event->actorId)->toBeNull()->and($event->userId)->toBeNull()->and($event->details)->toBe([]);
});

it('exposes the stats provider contract and tag', function () {
    expect(StatsProvider::TAG)->toBe('module.stats')
        ->and(method_exists(StatsProvider::class, 'name'))->toBeTrue()
        ->and(method_exists(StatsProvider::class, 'stats'))->toBeTrue();
});
