<?php

declare(strict_types=1);

use App\Modules\Payment\Events\PaymentExpired;
use App\Modules\Payment\Events\PaymentFailed;
use App\Modules\Payment\Events\PaymentPaid;
use App\Modules\Payment\Events\PaymentRefunded;
use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\PaymentEvent;
use App\Modules\Payment\Services\PaymentService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\IdentityKit;
use Tests\Support\PaymentKit;

afterEach(fn () => Carbon::setTestNow());

function paymentService(): PaymentService
{
    return app(PaymentService::class);
}

function paymentStatus(string $uuid): string
{
    return (string) Payment::query()->where('uuid', $uuid)->value('status');
}

function pastExpiry(): void
{
    Carbon::setTestNow(Carbon::now()->addSeconds((int) config('shop.payment_expiry_seconds') + 1));
}

it('pays a pending payment and calls the handler', function () {
    Carbon::setTestNow('2026-11-26 10:00:00');
    Event::fake([PaymentPaid::class]);
    $handler = PaymentKit::handler();
    $user = IdentityKit::user();
    $dto = PaymentKit::create($user, 4200);

    $paid = paymentService()->pay($dto->uuid, $user);

    expect($paid->status)->toBe('paid')
        ->and($paid->paidAt?->equalTo(Carbon::now()))->toBeTrue()
        ->and($handler->calls)->toBe([[$dto->orderUuid, 'paid']])
        ->and(PaymentEvent::query()->where('event', 'paid')->count())->toBe(1);
    Event::assertDispatched(PaymentPaid::class, fn (PaymentPaid $e) => $e->userId === $user->id && $e->subjectUuid === $dto->uuid);
});

it('only lets the owner pay', function () {
    $handler = PaymentKit::handler();
    $dto = PaymentKit::create(IdentityKit::user());

    expect(fn () => paymentService()->pay($dto->uuid, IdentityKit::user()))->toThrow(NotFoundException::class)
        ->and(fn () => paymentService()->pay(Str::uuid()->toString(), IdentityKit::user()))->toThrow(NotFoundException::class)
        ->and(paymentStatus($dto->uuid))->toBe('pending')
        ->and($handler->calls)->toBe([]);
});

it('refuses to pay twice', function () {
    $handler = PaymentKit::handler();
    $user = IdentityKit::user();
    $dto = PaymentKit::create($user);
    paymentService()->pay($dto->uuid, $user);

    expect(fn () => paymentService()->pay($dto->uuid, $user))->toThrow(ConflictException::class)
        ->and($handler->calls)->toBe([[$dto->orderUuid, 'paid']]);
});

it('marks an expired payment expired, calls the handler, then conflicts', function () {
    Carbon::setTestNow('2026-11-26 10:00:00');
    $handler = PaymentKit::handler();
    $user = IdentityKit::user();
    $dto = PaymentKit::create($user);
    pastExpiry();

    expect(fn () => paymentService()->pay($dto->uuid, $user))->toThrow(ConflictException::class)
        ->and(paymentStatus($dto->uuid))->toBe('expired')
        ->and($handler->calls)->toBe([[$dto->orderUuid, 'expired']]);
});

it('rolls back the payment when the handler throws', function () {
    Event::fake([PaymentPaid::class]);
    $handler = PaymentKit::handler();
    $handler->throw = new RuntimeException('boom');
    $user = IdentityKit::user();
    $dto = PaymentKit::create($user);

    expect(fn () => paymentService()->pay($dto->uuid, $user))->toThrow(RuntimeException::class)
        ->and(paymentStatus($dto->uuid))->toBe('pending')
        ->and(Payment::query()->where('uuid', $dto->uuid)->value('paid_at'))->toBeNull()
        ->and(PaymentEvent::query()->where('event', 'paid')->count())->toBe(0);
    Event::assertNotDispatched(PaymentPaid::class);
});

it('fails a payment only when the simulate flag is on', function () {
    Event::fake([PaymentFailed::class]);
    $handler = PaymentKit::handler();
    $user = IdentityKit::user();
    $dto = PaymentKit::create($user);

    config(['shop.payment_simulate_failure' => false]);
    expect(fn () => paymentService()->fail($dto->uuid, $user))->toThrow(ConflictException::class)
        ->and(paymentStatus($dto->uuid))->toBe('pending');

    config(['shop.payment_simulate_failure' => true]);
    $failed = paymentService()->fail($dto->uuid, $user);

    expect($failed->status)->toBe('failed')
        ->and($handler->calls)->toBe([[$dto->orderUuid, 'failed']])
        ->and(PaymentEvent::query()->where('event', 'failed')->count())->toBe(1);
    Event::assertDispatched(PaymentFailed::class);
});

it('only lets the owner fail a pending payment', function () {
    config(['shop.payment_simulate_failure' => true]);
    PaymentKit::handler();
    $user = IdentityKit::user();
    $dto = PaymentKit::create($user);

    expect(fn () => paymentService()->fail($dto->uuid, IdentityKit::user()))->toThrow(NotFoundException::class);
    paymentService()->fail($dto->uuid, $user);
    expect(fn () => paymentService()->fail($dto->uuid, $user))->toThrow(ConflictException::class);
});

it('expires pending payments past their expiry and is idempotent', function () {
    Carbon::setTestNow('2026-11-26 10:00:00');
    Event::fake([PaymentExpired::class]);
    $handler = PaymentKit::handler();
    $user = IdentityKit::user();
    $a = PaymentKit::create($user);
    $b = PaymentKit::create($user);
    $paid = PaymentKit::create($user);
    paymentService()->pay($paid->uuid, $user);
    pastExpiry();
    $fresh = PaymentKit::create($user);

    expect(paymentService()->expirePending())->toBe(2)
        ->and(paymentStatus($a->uuid))->toBe('expired')
        ->and(paymentStatus($b->uuid))->toBe('expired')
        ->and(paymentStatus($paid->uuid))->toBe('paid')
        ->and(paymentStatus($fresh->uuid))->toBe('pending')
        ->and(paymentService()->expirePending())->toBe(0)
        ->and(collect($handler->calls)->where(1, 'expired')->count())->toBe(2);
    Event::assertDispatchedTimes(PaymentExpired::class, 2);
});

it('keeps a payment pending and continues when the handler throws during expiry', function () {
    Carbon::setTestNow('2026-11-26 10:00:00');
    $handler = PaymentKit::handler();
    $handler->throw = new RuntimeException('boom');
    $dto = PaymentKit::create(IdentityKit::user());
    pastExpiry();

    expect(paymentService()->expirePending())->toBe(0)
        ->and(paymentStatus($dto->uuid))->toBe('pending');
});

it('refunds a paid payment with permission and a reason', function () {
    Event::fake([PaymentRefunded::class]);
    Carbon::setTestNow('2026-11-26 10:00:00');
    $handler = PaymentKit::handler();
    $user = IdentityKit::user();
    $staff = IdentityKit::userWithPermissions(['payments.refund']);
    $dto = PaymentKit::create($user);
    paymentService()->pay($dto->uuid, $user);

    $refunded = paymentService()->refund($dto->uuid, 'Customer asked', $staff);

    expect($refunded->status)->toBe('refunded')
        ->and($refunded->refundReason)->toBe('Customer asked')
        ->and($refunded->refundedAt?->equalTo(Carbon::now()))->toBeTrue()
        ->and($handler->calls)->toBe([[$dto->orderUuid, 'paid'], [$dto->orderUuid, 'refunded']])
        ->and(PaymentEvent::query()->where('event', 'refunded')->count())->toBe(1);
    Event::assertDispatched(PaymentRefunded::class, fn (PaymentRefunded $e) => $e->userId === $user->id && $e->actorId === $staff->id);
});

it('enforces the refund rules', function () {
    $handler = PaymentKit::handler();
    $user = IdentityKit::user();
    $staff = IdentityKit::userWithPermissions(['payments.refund']);
    $dto = PaymentKit::create($user);

    expect(fn () => paymentService()->refund($dto->uuid, 'x', $user))->toThrow(AuthorizationException::class)
        ->and(fn () => paymentService()->refund($dto->uuid, 'x', $staff))->toThrow(ConflictException::class);

    paymentService()->pay($dto->uuid, $user);

    expect(fn () => paymentService()->refund($dto->uuid, '   ', $staff))->toThrow(DomainValidationException::class)
        ->and(fn () => paymentService()->refund($dto->uuid, str_repeat('a', (int) config('shop.payment.refund_reason_max') + 1), $staff))->toThrow(DomainValidationException::class)
        ->and(fn () => paymentService()->refund(Str::uuid()->toString(), 'x', $staff))->toThrow(NotFoundException::class)
        ->and(paymentStatus($dto->uuid))->toBe('paid');

    paymentService()->refund($dto->uuid, 'ok', $staff);

    expect(fn () => paymentService()->refund($dto->uuid, 'again', $staff))->toThrow(ConflictException::class)
        ->and($handler->calls)->toBe([[$dto->orderUuid, 'paid'], [$dto->orderUuid, 'refunded']]);
});
