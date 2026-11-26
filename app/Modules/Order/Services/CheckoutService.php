<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Cart\Contracts\CartReader;
use App\Modules\Cart\DTO\CartLine;
use App\Modules\Catalog\Contracts\ProductCatalog;
use App\Modules\Inventory\Contracts\Inventory;
use App\Modules\Order\Events\OrderPlaced;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Payment\Contracts\Payments;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

final class CheckoutService
{
    public function __construct(
        private readonly CartReader $cart,
        private readonly ProductCatalog $catalog,
        private readonly Inventory $inventory,
        private readonly Payments $payments,
    ) {}

    /**
     * Places an order from the active cart in one transaction: order, items, reservation and payment.
     *
     * @param  array<string, mixed>  $shipping
     */
    public function place(User $user, array $shipping, ?string $note, string $idempotencyKey): Order
    {
        if (! Str::isUuid($idempotencyKey)) {
            throw new DomainValidationException(['idempotency_key' => 'The idempotency key is invalid.']);
        }

        $replay = $this->findReplay($user, $idempotencyKey);

        if ($replay !== null) {
            return $replay;
        }

        $address = $this->validateShipping($shipping);
        $note = $this->normalizeNote($note);
        [$items, $total, $currency] = $this->priceCart($user);

        $open = Order::query()
            ->where('user_id', $user->id)
            ->where('status', $this->status('awaiting_payment'))
            ->count();

        if ($open >= (int) config('shop.max_open_orders_per_user')) {
            throw new ConflictException('You have too many orders waiting for payment.');
        }

        try {
            $order = DB::transaction(function () use ($user, $idempotencyKey, $address, $note, $items, $total, $currency): Order {
                $order = Order::create([
                    'user_id' => $user->id,
                    'status' => $this->status('awaiting_payment'),
                    'total_amount' => $total,
                    'currency' => $currency,
                    'idempotency_key' => $idempotencyKey,
                    'shipping_address' => $address,
                    'note' => $note,
                ]);

                foreach ($items as $item) {
                    OrderItem::create($item + ['order_id' => $order->id]);
                }

                $this->inventory->reserve($order->uuid, array_map(
                    fn (array $item): array => ['product_uuid' => $item['product_uuid'], 'quantity' => $item['quantity']],
                    $items,
                ));
                $this->payments->create($order->uuid, $user->id, $total, $currency);

                return $order;
            });
        } catch (UniqueConstraintViolationException $e) {
            $existing = $this->findReplay($user, $idempotencyKey);

            if ($existing === null) {
                throw $e;
            }

            return $existing;
        }

        OrderPlaced::dispatch($order->uuid, $user->id, $user->id, ['total_amount' => $total, 'currency' => $currency]);

        return $order;
    }

    private function findReplay(User $user, string $idempotencyKey): ?Order
    {
        return Order::query()
            ->where('user_id', $user->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int, 2: string}
     */
    private function priceCart(User $user): array
    {
        $lines = $this->cart->activeItems($user->id);

        if ($lines === []) {
            throw new DomainValidationException(['cart' => 'Your cart is empty.']);
        }

        $snapshots = $this->catalog->lookup(array_map(fn (CartLine $line): string => $line->productUuid, $lines));
        $available = (string) config('shop.cart_available_product_status');
        $maxQuantity = (int) config('shop.cart_max_quantity');
        $items = [];
        $total = 0;
        $currency = null;

        foreach ($lines as $line) {
            $snapshot = $snapshots[$line->productUuid] ?? null;

            if ($snapshot === null || $snapshot->deleted || $snapshot->status !== $available) {
                throw new DomainValidationException(['cart' => 'A product in your cart is no longer available.']);
            }

            if ($line->quantity < 1 || $line->quantity > $maxQuantity) {
                throw new DomainValidationException(['cart' => 'A quantity in your cart is invalid.']);
            }

            $currency ??= $snapshot->currency;

            if ($snapshot->currency !== $currency) {
                throw new DomainValidationException(['cart' => 'All products in an order must use the same currency.']);
            }

            $lineTotal = $snapshot->priceAmount * $line->quantity;
            $total += $lineTotal;
            $items[] = [
                'product_uuid' => $snapshot->uuid,
                'product_name' => $snapshot->name,
                'sku' => $snapshot->sku,
                'unit_price_amount' => $snapshot->priceAmount,
                'quantity' => $line->quantity,
                'line_total_amount' => $lineTotal,
            ];
        }

        if ($total <= 0 || $currency === null) {
            throw new DomainValidationException(['cart' => 'The order total must be greater than zero.']);
        }

        return [$items, $total, $currency];
    }

    /**
     * @param  array<string, mixed>  $shipping
     * @return array<string, mixed>
     */
    private function validateShipping(array $shipping): array
    {
        $rules = (array) config('shop.order.shipping_rules');
        $input = [];

        foreach (array_keys($rules) as $field) {
            $value = $shipping[$field] ?? null;
            $input[$field] = is_string($value) ? trim($value) : $value;
        }

        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            $fields = [];

            foreach ($validator->errors()->messages() as $field => $messages) {
                $fields[(string) $field] = (string) $messages[0];
            }

            throw new DomainValidationException($fields);
        }

        $address = [];

        foreach (array_keys($rules) as $field) {
            $address[$field] = $input[$field];
        }

        $address['country'] = strtoupper((string) $address['country']);

        return $address;
    }

    private function normalizeNote(?string $note): ?string
    {
        $note = $note === null ? null : trim($note);

        if ($note === null || $note === '') {
            return null;
        }

        if (mb_strlen($note) > (int) config('shop.order.note_max')) {
            throw new DomainValidationException(['note' => 'The note is too long.']);
        }

        return $note;
    }

    private function status(string $key): string
    {
        return (string) config("shop.order.statuses.{$key}");
    }
}
