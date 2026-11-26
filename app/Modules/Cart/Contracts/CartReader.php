<?php

declare(strict_types=1);

namespace App\Modules\Cart\Contracts;

use App\Modules\Cart\DTO\CartLine;

interface CartReader
{
    /**
     * Lines of the active cart of the user, oldest first. Empty when there is no cart or no item.
     *
     * @return array<int, CartLine>
     */
    public function activeItems(int $userId): array;

    /**
     * Marks the active cart converted, frees the active key and dispatches CartCleared. No-op without an active cart.
     */
    public function clear(int $userId): void;
}
