<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\History\Listeners\RecordHistory;
use App\Modules\History\Models\HistoryEvent;
use App\Modules\History\Providers\HistoryServiceProvider;
use App\Modules\Identity\Events\UserRegistered;
use App\Modules\Inventory\Events\StockAdjusted;
use App\Modules\Order\Events\OrderPlaced;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\Support\HistoryKit;

it('writes one row for each configured event that exists', function () {
    $checked = 0;

    foreach ((array) config('history.events') as $class => [$subject, $action]) {
        if (! class_exists($class)) {
            continue;
        }

        $event = HistoryKit::makeEvent($class);
        (new RecordHistory)->handle($event);

        $row = HistoryEvent::query()->where('event_uuid', $event->eventUuid)->first();
        expect($row)->not->toBeNull();
        expect($row->subject_type)->toBe($subject);
        expect($row->action)->toBe($action);
        expect($row->subject_uuid)->toBe($event->subjectUuid);
        $checked++;
    }

    expect($checked)->toBeGreaterThanOrEqual(16);
});

it('records the user and actor of the event', function () {
    $user = User::factory()->create();
    $event = new OrderPlaced('11111111-1111-4111-8111-111111111111', $user->id, $user->id, ['total' => 10]);

    (new RecordHistory)->handle($event);

    $row = HistoryEvent::query()->where('event_uuid', $event->eventUuid)->firstOrFail();
    expect($row->user_id)->toBe($user->id);
    expect($row->actor_id)->toBe($user->id);
    expect($row->details)->toEqual(['total' => 10]);
});

it('does not duplicate on replay', function () {
    $event = HistoryKit::makeEvent(OrderPlaced::class);

    (new RecordHistory)->handle($event);
    (new RecordHistory)->handle($event);
    event($event);

    expect(HistoryEvent::query()->where('event_uuid', $event->eventUuid)->count())->toBe(1);
});

it('records through the dispatcher with the sync queue', function () {
    $user = User::factory()->create();
    $event = new UserRegistered($user);

    event($event);

    expect(HistoryEvent::query()->where('event_uuid', $event->eventUuid)->exists())->toBeTrue();
});

it('queues the listener with the queue fake', function () {
    $user = User::factory()->create();
    Queue::fake();

    $event = new UserRegistered($user);
    event($event);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === RecordHistory::class);
    expect(HistoryEvent::query()->where('event_uuid', $event->eventUuid)->exists())->toBeFalse();
});

it('is a queued after-commit listener with tries from config', function () {
    $listener = new RecordHistory;

    expect($listener)->toBeInstanceOf(ShouldQueue::class);
    expect($listener->afterCommit)->toBeTrue();
    expect($listener->tries())->toBe((int) config('history.tries'));
});

it('removes secrets from details', function () {
    $event = new StockAdjusted('22222222-2222-4222-8222-222222222222', null, [
        'password' => 'x', 'token' => 'y', 'amount' => 5,
        'nested' => ['secret' => 'z', 'ok' => 1],
    ]);

    (new RecordHistory)->handle($event);

    $details = HistoryEvent::query()->where('event_uuid', $event->eventUuid)->firstOrFail()->details;
    expect($details)->toEqual(['amount' => 5, 'nested' => ['ok' => 1]]);
});

it('logs a failure and never throws into the dispatcher', function () {
    Log::spy();
    $event = new StockAdjusted('33333333-3333-4333-8333-333333333333', null, ['bad' => "\xB1\x31"]);

    event($event);
    (new RecordHistory)->handle($event);

    Log::shouldHaveReceived('error')->atLeast()->once();
    expect(HistoryEvent::query()->where('event_uuid', $event->eventUuid)->exists())->toBeFalse();
});

it('ignores an event class that is not configured', function () {
    config(['history.events' => []]);
    $event = HistoryKit::makeEvent(OrderPlaced::class);

    (new RecordHistory)->handle($event);

    expect(HistoryEvent::query()->count())->toBe(0);
});

it('registers listeners from the real config', function () {
    expect(Event::hasListeners(OrderPlaced::class))->toBeTrue();
    expect(Event::hasListeners(UserRegistered::class))->toBeTrue();
});

it('skips configured classes that do not exist', function () {
    $missing = 'App\\Modules\\Content\\Events\\NoSuchEvent';
    config(['history.events' => [
        $missing => ['article', 'published'],
        UserRegistered::class => ['user', 'registered'],
    ]]);
    Event::forget(UserRegistered::class);

    app()->getProvider(HistoryServiceProvider::class)->registerListeners();

    expect(Event::hasListeners(UserRegistered::class))->toBeTrue();
    expect(Event::hasListeners($missing))->toBeFalse();
});

it('registers a configured class once it exists', function () {
    $alias = 'Tests\\Aliases\\ArticlePublished';

    if (! class_exists($alias)) {
        class_alias(UserRegistered::class, $alias);
    }

    config(['history.events' => [$alias => ['article', 'published']]]);
    app()->getProvider(HistoryServiceProvider::class)->registerListeners();

    expect(Event::hasListeners($alias))->toBeTrue();
});
