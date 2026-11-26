<?php

declare(strict_types=1);

use App\Modules\Payment\Models\Payment;
use App\Modules\Payment\Models\PaymentEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\IdentityKit;
use Tests\Support\PaymentKit;

it('runs the payment migrations twice without error', function () {
    expect(Artisan::call('migrate', ['--force' => true]))->toBe(0)
        ->and(Artisan::call('migrate', ['--force' => true]))->toBe(0)
        ->and(Schema::hasTable('payments'))->toBeTrue()
        ->and(Schema::hasTable('payment_events'))->toBeTrue();
});

it('has the documented columns', function () {
    expect(Schema::hasColumns('payments', [
        'id', 'uuid', 'order_uuid', 'user_id', 'amount', 'currency', 'status', 'provider',
        'provider_reference', 'expires_at', 'paid_at', 'refunded_at', 'refund_reason', 'created_at', 'updated_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('payment_events', ['id', 'payment_id', 'event', 'details', 'created_at']))->toBeTrue();
});

it('only references users with a foreign key', function () {
    $tables = collect(Schema::getForeignKeys('payments'))->pluck('foreign_table')->unique()->values()->all();

    expect($tables)->toBe(['users']);
});

it('keeps order_uuid unique', function () {
    PaymentKit::create(IdentityKit::user());
    $copy = (array) DB::table('payments')->first();
    unset($copy['id']);
    $copy['uuid'] = Str::uuid()->toString();

    expect(fn () => DB::table('payments')->insert($copy))->toThrow(QueryException::class);
});

it('cascades payment events when a payment is deleted', function () {
    $dto = PaymentKit::create(IdentityKit::user());
    expect(PaymentEvent::query()->count())->toBe(1);

    Payment::query()->where('uuid', $dto->uuid)->firstOrFail()->delete();

    expect(PaymentEvent::query()->count())->toBe(0);
});
