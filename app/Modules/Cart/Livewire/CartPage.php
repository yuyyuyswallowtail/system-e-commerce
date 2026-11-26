<?php

declare(strict_types=1);

namespace App\Modules\Cart\Livewire;

use App\Modules\Cart\Contracts\CartReader;
use App\Modules\Cart\DTO\CartLine;
use App\Modules\Cart\Livewire\Concerns\InteractsWithCartActor;
use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Contracts\ProductCatalog;
use App\Modules\Catalog\DTO\ProductSnapshot;
use App\Support\Exceptions\DomainValidationException;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts::storefront')]
#[Title('Your cart')]
class CartPage extends Component
{
    use InteractsWithCartActor;

    public function mount(): void
    {
        $this->actor();
    }

    public function setQuantity(string $productUuid, int|string $quantity): void
    {
        $user = $this->actor();

        if (! Str::isUuid($productUuid) || ! is_numeric($quantity)) {
            return;
        }

        $this->resetErrorBag();

        try {
            app(CartService::class)->setQuantity($user->id, $productUuid, (int) $quantity);
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        } catch (NotFoundException) {
            // The line is not in the cart of this user: nothing to change.
        }
    }

    public function remove(string $productUuid): void
    {
        $user = $this->actor();

        if (! Str::isUuid($productUuid)) {
            return;
        }

        $this->resetErrorBag();

        try {
            app(CartService::class)->remove($user->id, $productUuid);
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        } catch (NotFoundException) {
            // The line is not in the cart of this user: nothing to remove.
        }
    }

    public function clear(): void
    {
        $user = $this->actor();

        $this->resetErrorBag();
        app(CartReader::class)->clear($user->id);
    }

    public function render(): View
    {
        $user = $this->actor();
        $lines = app(CartReader::class)->activeItems($user->id);
        $snapshots = $this->snapshots(array_map(static fn (CartLine $line): string => $line->productUuid, $lines));
        $activeStatus = (string) config('shop.cart_available_product_status');

        $rows = [];
        $totals = [];
        $blocked = false;

        foreach ($lines as $line) {
            $snapshot = $snapshots[$line->productUuid] ?? null;
            $available = $snapshot !== null && ! $snapshot->deleted && $snapshot->status === $activeStatus;
            $unitPrice = $available ? $snapshot->priceAmount : $line->unitPriceAmount;
            $currency = $available ? $snapshot->currency : $line->currency;

            if ($available) {
                $totals[$currency] = ($totals[$currency] ?? 0) + $unitPrice * $line->quantity;
            } else {
                $blocked = true;
            }

            $rows[] = [
                'uuid' => $line->productUuid,
                'name' => $snapshot?->name,
                'available' => $available,
                'quantity' => $line->quantity,
                'unitPrice' => $unitPrice,
                'currency' => $currency,
                'lineTotal' => $unitPrice * $line->quantity,
            ];
        }

        return view('cart.cart-page', [
            'rows' => $rows,
            'totals' => $totals,
            'blocked' => $blocked,
            'canCheckout' => $rows !== [] && ! $blocked && Route::has('checkout'),
            'checkoutRoute' => Route::has('checkout'),
            'maxQuantity' => (int) config('shop.cart_max_quantity'),
        ]);
    }

    /**
     * Enrichment only: a catalog failure marks every line unavailable instead of breaking the page.
     *
     * @param  array<int, string>  $uuids
     * @return array<string, ProductSnapshot>
     */
    private function snapshots(array $uuids): array
    {
        if ($uuids === []) {
            return [];
        }

        try {
            return app(ProductCatalog::class)->lookup($uuids);
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }
}
