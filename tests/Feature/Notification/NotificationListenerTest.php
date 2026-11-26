<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Notification\Listeners\NotifyUser;
use App\Modules\Notification\Providers\NotificationServiceProvider;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderPaid;
use App\Modules\Order\Events\OrderPlaced;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Payment\Events\PaymentFailed;
use App\Modules\Payment\Events\PaymentRefunded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

function notifyCases(User $user, string $order): array
{
    $details = ['order_uuid' => $order, 'total_amount' => 2500, 'currency' => 'USD'];

    return [
        'order_placed' => new OrderPlaced($order, $user->id, null, $details),
        'order_paid' => new OrderPaid($order, $user->id, null, $details),
        'order_cancelled' => new OrderCancelled($order, $user->id, null, $details + ['reason' => 'user']),
        'order_status_changed' => new OrderStatusChanged($order, $user->id, null, $details + ['to' => 'shipped']),
        'payment_failed' => new PaymentFailed('99999999-9999-4999-8999-999999999999', $user->id, null, $details),
        'payment_refunded' => new PaymentRefunded('99999999-9999-4999-8999-999999999999', $user->id, null, $details),
    ];
}

it('creates one database notification per configured event with the right content', function () {
    $user = User::factory()->create();
    $order = '11111111-1111-4111-8111-111111111111';

    foreach (notifyCases($user, $order) as $type => $event) {
        (new NotifyUser)->handle($event);

        $row = $user->notifications()->where('data', 'like', '%"event_uuid":"'.$event->eventUuid.'"%')->first();
        expect($row)->not->toBeNull();
        expect($row->data['type'])->toBe($type);
        expect($row->data['title'])->toBeString()->not->toBe('');
        expect($row->data['body'])->toBeString()->not->toBe('');
        expect($row->data['data']['order_uuid'])->toBe($order);
        expect($row->data['data']['amount'])->toBe(2500);
        expect($row->data['data']['event_uuid'])->toBe($event->eventUuid);
        expect(config('shop.notification_types'))->toContain($type);
    }

    expect($user->notifications()->count())->toBe(6);
});

it('does not duplicate on a retry', function () {
    $user = User::factory()->create();
    $event = new OrderPlaced('11111111-1111-4111-8111-111111111111', $user->id, null, ['total_amount' => 10, 'currency' => 'USD']);

    (new NotifyUser)->handle($event);
    (new NotifyUser)->handle($event);
    event($event);

    expect($user->notifications()->count())->toBe(1);
});

it('skips and logs when the user is missing', function () {
    Log::spy();
    $event = new OrderPlaced('11111111-1111-4111-8111-111111111111', 987654, null, []);

    (new NotifyUser)->handle($event);

    Log::shouldHaveReceived('warning')->atLeast()->once();
    expect(DB::table('notifications')->count())->toBe(0);
});

it('ignores an event class that is not configured', function () {
    config(['notification.events' => []]);
    $user = User::factory()->create();

    (new NotifyUser)->handle(new OrderPlaced('11111111-1111-4111-8111-111111111111', $user->id));

    expect($user->notifications()->count())->toBe(0);
});

it('is a queued after-commit listener', function () {
    $listener = new NotifyUser;

    expect($listener)->toBeInstanceOf(ShouldQueue::class);
    expect($listener->afterCommit)->toBeTrue();
    expect($listener->tries())->toBe((int) config('notification.tries'));
});

it('notifies through the dispatcher with the sync queue', function () {
    $user = User::factory()->create();

    event(new OrderPaid('11111111-1111-4111-8111-111111111111', $user->id, null, ['total_amount' => 5, 'currency' => 'USD']));

    expect($user->notifications()->count())->toBe(1);
});

it('queues the listener with the queue fake', function () {
    $user = User::factory()->create();
    Queue::fake();

    event(new OrderPaid('11111111-1111-4111-8111-111111111111', $user->id));

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === NotifyUser::class);
    expect($user->notifications()->count())->toBe(0);
});

it('never throws into the dispatcher when queueing fails', function () {
    Log::spy();
    $user = User::factory()->create();
    $event = new OrderPlaced('11111111-1111-4111-8111-111111111111', $user->id, null, ['bad' => "\xB1\x31"]);

    event($event);

    Log::shouldHaveReceived('error')->atLeast()->once();
    expect($user->notifications()->count())->toBe(0);
});

it('registers only listeners for classes that exist', function () {
    $missing = 'App\\Modules\\Nope\\Events\\Missing';
    config(['notification.events' => [$missing => ['order_placed', 'OrderPlacedNotification']]]);
    app()->getProvider(NotificationServiceProvider::class)->registerListeners();

    expect(Event::hasListeners($missing))->toBeFalse();
    expect(Event::hasListeners(OrderPlaced::class))->toBeTrue();
});
