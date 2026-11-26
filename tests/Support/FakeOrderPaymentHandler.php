<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Modules\Payment\Contracts\OrderPaymentHandler;
use Throwable;

final class FakeOrderPaymentHandler implements OrderPaymentHandler
{
    /** @var array<int, array{0: string, 1: string}> */
    public array $calls = [];

    public ?Throwable $throw = null;

    public function handle(string $orderUuid, string $result): void
    {
        $this->calls[] = [$orderUuid, $result];

        if ($this->throw !== null) {
            throw $this->throw;
        }
    }
}
