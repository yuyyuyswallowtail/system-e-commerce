<?php

declare(strict_types=1);

namespace App\Modules\Payment\Console;

use App\Modules\Payment\Services\PaymentService;
use Illuminate\Console\Command;
use Throwable;

final class ExpirePendingCommand extends Command
{
    protected $signature = 'payments:expire';

    protected $description = 'Expire pending payments past their expiry and notify the order handler';

    public function handle(PaymentService $service): int
    {
        try {
            $count = $service->expirePending();
        } catch (Throwable $e) {
            report($e);
            $this->error('[FAIL] Could not expire pending payments.');

            return self::FAILURE;
        }

        $this->info('[OK] '.$count);

        return self::SUCCESS;
    }
}
