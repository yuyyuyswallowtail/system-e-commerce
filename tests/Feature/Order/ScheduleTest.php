<?php

declare(strict_types=1);

use App\Modules\Payment\Contracts\Payments;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\OrderKit;

function scheduledEvent(string $command)
{
    foreach (app(Schedule::class)->events() as $event) {
        if (str_contains((string) $event->command, $command)) {
            return $event;
        }
    }

    return null;
}

it('schedules inventory:release-expired and payments:expire every minute without overlapping', function () {
    foreach (['inventory:release-expired', 'payments:expire'] as $command) {
        $event = scheduledEvent($command);

        expect($event)->not->toBeNull()
            ->and($event->expression)->toBe('* * * * *')
            ->and($event->withoutOverlapping)->toBeTrue();
    }
});

it('cancels an unpaid order and releases its stock when payments:expire runs after the expiry', function () {
    [$user, $products] = OrderKit::shopper([[1500, 2, 10]]);
    $order = OrderKit::place($user);
    expect(OrderKit::reserved($products[0]->uuid))->toBe(2);

    Carbon::setTestNow(Carbon::now()->addSeconds((int) config('shop.payment_expiry_seconds') + 1));
    Artisan::call('payments:expire');
    $output = Artisan::output();
    Carbon::setTestNow();

    expect($output)->toContain('[OK] 1')
        ->and($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->cancel_reason)->toBe('payment_expired')
        ->and(OrderKit::reserved($products[0]->uuid))->toBe(0)
        ->and(app(Payments::class)->byOrder($order->uuid)->status)->toBe('expired');
});

it('leaves a fresh order alone when payments:expire runs early', function () {
    [$user] = OrderKit::shopper();
    $order = OrderKit::place($user);

    Artisan::call('payments:expire');

    expect($order->fresh()->status)->toBe('awaiting_payment');
});
