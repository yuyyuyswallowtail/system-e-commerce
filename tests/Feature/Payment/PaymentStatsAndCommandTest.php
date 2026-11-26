<?php

declare(strict_types=1);

use App\Modules\Payment\Services\PaymentService;
use App\Modules\Payment\Stats\PaymentStats;
use App\Support\Contracts\StatsProvider;
use Illuminate\Support\Carbon;
use Tests\Support\IdentityKit;
use Tests\Support\PaymentKit;

afterEach(fn () => Carbon::setTestNow());

it('reports payment stats with every status present', function () {
    PaymentKit::handler();
    $user = IdentityKit::user();
    PaymentKit::create($user, 1000);
    $paid = PaymentKit::create($user, 2500);
    app(PaymentService::class)->pay($paid->uuid, $user);

    $stats = app(PaymentStats::class)->stats();

    expect(app(PaymentStats::class)->name())->toBe('payments')
        ->and($stats['total'])->toBe(2)
        ->and($stats['paid_amount'])->toBe(2500)
        ->and($stats['currency'])->toBe(config('shop.default_currency'))
        ->and($stats['by_status'])->toBe(['pending' => 1, 'paid' => 1, 'failed' => 0, 'expired' => 0, 'cancelled' => 0, 'refunded' => 0]);
});

it('registers the stats provider under the module.stats tag', function () {
    $names = collect(app()->tagged(StatsProvider::TAG))->map(fn (StatsProvider $p) => $p->name())->all();

    expect($names)->toContain('payments');
});

it('reports zero stats on an empty table', function () {
    expect(app(PaymentStats::class)->stats())->toMatchArray(['total' => 0, 'paid_amount' => 0]);
});

it('runs payments:expire and prints the count', function () {
    Carbon::setTestNow('2026-11-26 10:00:00');
    PaymentKit::handler();
    PaymentKit::create(IdentityKit::user());
    Carbon::setTestNow(Carbon::now()->addSeconds((int) config('shop.payment_expiry_seconds') + 1));

    $this->artisan('payments:expire')->expectsOutputToContain('[OK] 1')->assertExitCode(0);
    $this->artisan('payments:expire')->expectsOutputToContain('[OK] 0')->assertExitCode(0);
});

it('prints a failure line when expiry throws', function () {
    app()->bind(PaymentService::class, fn () => new class extends PaymentService
    {
        public function expirePending(): int
        {
            throw new RuntimeException('boom');
        }
    });

    $this->artisan('payments:expire')->expectsOutputToContain('[FAIL]')->assertExitCode(1);
});
