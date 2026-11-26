<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Console;

use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Console\Command;
use Throwable;

final class ReleaseExpiredCommand extends Command
{
    protected $signature = 'inventory:release-expired';

    protected $description = 'Expire reservations past their expiry and free the reserved stock';

    public function handle(InventoryService $service): int
    {
        try {
            $count = $service->expire();
        } catch (Throwable $e) {
            report($e);
            $this->error('[FAIL] Could not release expired reservations.');

            return self::FAILURE;
        }

        $this->info('[OK] '.$count);

        return self::SUCCESS;
    }
}
