<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\User;
use App\Modules\Payment\Contracts\OrderPaymentHandler;
use App\Modules\Payment\Contracts\Payments;
use App\Modules\Payment\DTO\PaymentDto;
use Illuminate\Support\Str;

final class PaymentKit
{
    public static function handler(): FakeOrderPaymentHandler
    {
        $handler = new FakeOrderPaymentHandler;
        app()->instance(OrderPaymentHandler::class, $handler);

        return $handler;
    }

    public static function create(User $user, int $amount = 1000, string $currency = 'USD', ?string $orderUuid = null): PaymentDto
    {
        return app(Payments::class)->create($orderUuid ?? Str::uuid()->toString(), $user->id, $amount, $currency);
    }
}
