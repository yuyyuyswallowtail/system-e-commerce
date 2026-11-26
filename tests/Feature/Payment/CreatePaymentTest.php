<?php

declare(strict_types=1);

use App\Modules\Payment\Contracts\Payments;
use App\Modules\Payment\DTO\PaymentDto;
use App\Modules\Payment\Events\PaymentCancelled;
use App\Modules\Payment\Events\PaymentCreated;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\PaymentEvent;
use App\Modules\Payment\Services\PaymentService;
use App\Support\Events\DomainEvent;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\IdentityKit;
use Tests\Support\PaymentKit;

afterEach(fn () => Carbon::setTestNow());

function paymentsContract(): Payments
{
    return app(Payments::class);
}

it('binds the contract to the service', function () {
    expect(paymentsContract())->toBeInstanceOf(PaymentService::class);
});

it('creates a pending payment with values from config', function () {
    Carbon::setTestNow('2026-11-26 10:00:00');
    Event::fake([PaymentCreated::class]);
    $user = IdentityKit::user();
    $order = Str::uuid()->toString();

    $dto = paymentsContract()->create($order, $user->id, 2599, 'USD');

    expect($dto)->toBeInstanceOf(PaymentDto::class)
        ->and($dto->orderUuid)->toBe($order)
        ->and($dto->userId)->toBe($user->id)
        ->and($dto->status)->toBe('pending')
        ->and($dto->amount)->toBe(2599)
        ->and($dto->currency)->toBe('USD')
        ->and($dto->provider)->toBe(config('shop.payment_provider'))
        ->and($dto->providerReference)->toMatch('/^sim_[0-9a-f]{16}$/')
        ->and($dto->expiresAt->equalTo(Carbon::now()->addSeconds((int) config('shop.payment_expiry_seconds'))))->toBeTrue()
        ->and($dto->paidAt)->toBeNull()
        ->and(PaymentEvent::query()->where('event', 'created')->count())->toBe(1);

    Event::assertDispatched(PaymentCreated::class, fn (PaymentCreated $e) => $e->subjectUuid === $dto->uuid && $e->userId === $user->id);
});

it('dispatches domain events that wait for the commit', function () {
    expect(is_subclass_of(PaymentCreated::class, DomainEvent::class))->toBeTrue()
        ->and(new PaymentCreated(Str::uuid()->toString(), 1))->toBeInstanceOf(ShouldDispatchAfterCommit::class);
});

it('is idempotent by order uuid for the same amount and currency', function () {
    Event::fake([PaymentCreated::class]);
    $user = IdentityKit::user();
    $order = Str::uuid()->toString();

    $first = paymentsContract()->create($order, $user->id, 1000, 'USD');
    $second = paymentsContract()->create($order, $user->id, 1000, 'USD');

    expect($second->uuid)->toBe($first->uuid)
        ->and(Payment::query()->count())->toBe(1)
        ->and(PaymentEvent::query()->count())->toBe(1);
    Event::assertDispatchedTimes(PaymentCreated::class, 1);
});

it('conflicts when the same order is created with another amount, currency or user', function () {
    $user = IdentityKit::user();
    $other = IdentityKit::user();
    $order = Str::uuid()->toString();
    paymentsContract()->create($order, $user->id, 1000, 'USD');

    expect(fn () => paymentsContract()->create($order, $user->id, 1001, 'USD'))->toThrow(ConflictException::class)
        ->and(fn () => paymentsContract()->create($order, $user->id, 1000, 'EUR'))->toThrow(ConflictException::class)
        ->and(fn () => paymentsContract()->create($order, $other->id, 1000, 'USD'))->toThrow(ConflictException::class)
        ->and(Payment::query()->count())->toBe(1);
});

it('validates the amount, currency and order uuid', function () {
    $user = IdentityKit::user();
    $order = Str::uuid()->toString();

    expect(fn () => paymentsContract()->create($order, $user->id, 0, 'USD'))->toThrow(DomainValidationException::class)
        ->and(fn () => paymentsContract()->create($order, $user->id, -5, 'USD'))->toThrow(DomainValidationException::class)
        ->and(fn () => paymentsContract()->create($order, $user->id, 100, 'XXX'))->toThrow(DomainValidationException::class)
        ->and(fn () => paymentsContract()->create('not-a-uuid', $user->id, 100, 'USD'))->toThrow(DomainValidationException::class)
        ->and(Payment::query()->count())->toBe(0);
});

it('finds a payment by order or returns null', function () {
    $dto = PaymentKit::create(IdentityKit::user());

    expect(paymentsContract()->byOrder($dto->orderUuid)?->uuid)->toBe($dto->uuid)
        ->and(paymentsContract()->byOrder(Str::uuid()->toString()))->toBeNull();
});

it('cancels a pending payment by order', function () {
    Event::fake([PaymentCancelled::class]);
    $dto = PaymentKit::create(IdentityKit::user());

    paymentsContract()->cancelByOrder($dto->orderUuid);

    expect(Payment::query()->where('uuid', $dto->uuid)->value('status'))->toBe('cancelled')
        ->and(PaymentEvent::query()->where('event', 'cancelled')->count())->toBe(1);
    Event::assertDispatchedTimes(PaymentCancelled::class, 1);
});

it('treats a repeated cancel and an unknown order as a no-op', function () {
    Event::fake([PaymentCancelled::class]);
    $dto = PaymentKit::create(IdentityKit::user());

    paymentsContract()->cancelByOrder($dto->orderUuid);
    paymentsContract()->cancelByOrder($dto->orderUuid);
    paymentsContract()->cancelByOrder(Str::uuid()->toString());

    expect(PaymentEvent::query()->where('event', 'cancelled')->count())->toBe(1);
    Event::assertDispatchedTimes(PaymentCancelled::class, 1);
});

it('refuses to cancel a paid payment by order', function () {
    PaymentKit::handler();
    $user = IdentityKit::user();
    $dto = PaymentKit::create($user);
    app(PaymentService::class)->pay($dto->uuid, $user);

    expect(fn () => paymentsContract()->cancelByOrder($dto->orderUuid))->toThrow(ConflictException::class)
        ->and(Payment::query()->where('uuid', $dto->uuid)->value('status'))->toBe('paid');
});
