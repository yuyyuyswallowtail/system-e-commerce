<?php

declare(strict_types=1);

namespace App\Modules\Order\Livewire;

use App\Modules\Cart\Contracts\CartReader;
use App\Modules\Cart\DTO\CartLine;
use App\Modules\Catalog\Contracts\ProductCatalog;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Order\Livewire\Concerns\InteractsWithOrderActor;
use App\Modules\Order\Livewire\Forms\CheckoutForm;
use App\Modules\Order\Services\CheckoutService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts::storefront')]
#[Title('Checkout')]
class CheckoutPage extends Component
{
    use InteractsWithOrderActor;

    public CheckoutForm $form;

    #[Locked]
    public string $idempotencyKey = '';

    public function mount(): void
    {
        $user = $this->actor();
        $this->idempotencyKey = Str::uuid()->toString();

        if (app(CartReader::class)->activeItems($user->id) === []) {
            $this->redirectRoute('cart.index');
        }
    }

    public function submit(): void
    {
        $user = $this->actor();
        $this->resetErrorBag();
        $note = trim($this->form->note);

        try {
            $order = app(CheckoutService::class)->place(
                $user,
                $this->form->shipping(),
                $note === '' ? null : $note,
                $this->idempotencyKey,
            );
        } catch (DomainValidationException $exception) {
            $this->mapErrors($exception->fields());

            return;
        } catch (InsufficientStockException) {
            $this->addError('cart', 'Not enough stock for one of the products in your cart.');

            return;
        } catch (ConflictException $exception) {
            $this->addError('cart', $exception->getMessage());

            return;
        }

        $this->redirectRoute('orders.show', ['order' => $order->uuid]);
    }

    public function render(): View
    {
        $user = $this->actor();
        $lines = app(CartReader::class)->activeItems($user->id);
        $uuids = array_map(static fn (CartLine $line): string => $line->productUuid, $lines);

        try {
            $snapshots = $uuids === [] ? [] : app(ProductCatalog::class)->lookup($uuids);
        } catch (Throwable $exception) {
            report($exception);
            $snapshots = [];
        }

        $rows = [];
        $totals = [];

        foreach ($lines as $line) {
            $snapshot = $snapshots[$line->productUuid] ?? null;
            $price = $snapshot !== null ? $snapshot->priceAmount : $line->unitPriceAmount;
            $currency = $snapshot !== null ? $snapshot->currency : $line->currency;
            $totals[$currency] = ($totals[$currency] ?? 0) + $price * $line->quantity;
            $rows[] = [
                'uuid' => $line->productUuid,
                'name' => $snapshot?->name,
                'quantity' => $line->quantity,
                'unitPrice' => $price,
                'currency' => $currency,
                'lineTotal' => $price * $line->quantity,
            ];
        }

        return view('order.checkout-page', ['rows' => $rows, 'totals' => $totals]);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function mapErrors(array $fields): void
    {
        $shipping = array_keys((array) config('shop.order.shipping_rules'));

        foreach ($fields as $field => $messages) {
            $key = (string) $field;
            $target = in_array($key, $shipping, true) || $key === 'note' ? 'form.'.$key : 'cart';

            foreach ((array) $messages as $message) {
                $this->addError($target, (string) $message);
            }
        }
    }
}
