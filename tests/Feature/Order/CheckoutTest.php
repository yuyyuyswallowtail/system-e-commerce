<?php

declare(strict_types=1);

use App\Modules\Cart\Contracts\CartReader;
use App\Modules\Cart\Services\CartService;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Order\Events\OrderPlaced;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Services\CheckoutService;
use App\Modules\Payment\Contracts\Payments;
use App\Modules\Payment\DTO\PaymentDto;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Support\CartKit;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

function nothingLeftBehind(): void
{
    expect(Order::query()->count())->toBe(0)
        ->and(OrderItem::query()->count())->toBe(0)
        ->and(DB::table('stock_reservations')->count())->toBe(0)
        ->and(DB::table('payments')->count())->toBe(0);
}

it('places an order with snapshots, a reservation and a pending payment', function () {
    Event::fake([OrderPlaced::class]);
    [$user, [$product]] = OrderKit::shopper([[1500, 2, 10]]);
    $key = Str::uuid()->toString();

    $order = OrderKit::place($user, $key, 'Leave at the door');

    expect($order->status)->toBe('awaiting_payment')
        ->and($order->total_amount)->toBe(3000)
        ->and($order->total_amount)->toBeInt()
        ->and($order->currency)->toBe('USD')
        ->and($order->user_id)->toBe($user->id)
        ->and($order->idempotency_key)->toBe($key)
        ->and($order->note)->toBe('Leave at the door')
        ->and($order->shipping_address)->toMatchArray(['name' => 'Jane Doe', 'city' => 'Jakarta', 'country' => 'ID']);

    $item = $order->items()->firstOrFail();
    expect($item->product_uuid)->toBe($product->uuid)
        ->and($item->product_name)->toBe($product->name)
        ->and($item->sku)->toBe($product->sku)
        ->and($item->unit_price_amount)->toBe(1500)
        ->and($item->quantity)->toBe(2)
        ->and($item->line_total_amount)->toBe(3000)
        ->and(OrderKit::available($product->uuid))->toBe(8);

    $payment = app(Payments::class)->byOrder($order->uuid);
    expect($payment)->not->toBeNull()
        ->and($payment->amount)->toBe(3000)
        ->and($payment->currency)->toBe('USD')
        ->and($payment->status)->toBe('pending')
        ->and($payment->userId)->toBe($user->id);

    Event::assertDispatched(OrderPlaced::class, fn (OrderPlaced $e) => $e->subjectUuid === $order->uuid && $e->userId === $user->id);
});

it('sums several lines as integers', function () {
    [$user] = OrderKit::shopper([[1999, 3, 10], [500, 1, 5]]);

    $order = OrderKit::place($user);

    expect($order->total_amount)->toBe(6497)->and($order->items()->count())->toBe(2);
});

it('ignores the cart price and uses the current catalog price', function () {
    [$user, [$product]] = OrderKit::shopper([[1500, 2, 10]]);
    $product->update(['price_amount' => 2000]);

    $order = OrderKit::place($user);

    expect($order->total_amount)->toBe(4000)
        ->and($order->items()->firstOrFail()->unit_price_amount)->toBe(2000)
        ->and(app(Payments::class)->byOrder($order->uuid)?->amount)->toBe(4000);
});

it('rejects an empty cart', function () {
    $user = IdentityKit::user();

    expect(fn () => OrderKit::place($user))->toThrow(DomainValidationException::class);
    nothingLeftBehind();
});

it('rejects an inactive or deleted product without leaving anything behind', function () {
    [$user, [$product]] = OrderKit::shopper([[1500, 1, 10]]);
    $product->update(['status' => 'draft']);

    expect(fn () => OrderKit::place($user))->toThrow(DomainValidationException::class);
    nothingLeftBehind();

    $product->update(['status' => 'active']);
    $product->delete();

    expect(fn () => OrderKit::place($user))->toThrow(DomainValidationException::class);
    nothingLeftBehind();
});

it('rolls back everything when stock is insufficient', function () {
    [$user, [$a, $b]] = OrderKit::shopper([[1500, 2, 10], [700, 3, 10]]);
    DB::table('stock_items')->where('product_uuid', $b->uuid)->update(['quantity_on_hand' => 1]);

    try {
        OrderKit::place($user);
        $this->fail('Expected InsufficientStockException.');
    } catch (InsufficientStockException $e) {
        expect($e->productUuid())->toBe($b->uuid)->and($e->available())->toBe(1);
    }

    nothingLeftBehind();
    expect(OrderKit::reserved($a->uuid))->toBe(0)->and(OrderKit::reserved($b->uuid))->toBe(0);
});

it('rejects an order past the open orders cap', function () {
    config(['shop.max_open_orders_per_user' => 1]);
    [$user] = OrderKit::shopper([[1500, 1, 10]]);
    OrderKit::place($user);

    expect(fn () => OrderKit::place($user))->toThrow(ConflictException::class)
        ->and(Order::query()->count())->toBe(1)
        ->and(DB::table('payments')->count())->toBe(1);
});

it('rolls back the order and the reservation when Payments::create throws', function () {
    [$user, [$product]] = OrderKit::shopper([[1500, 2, 10]]);
    app()->bind(Payments::class, fn () => new class implements Payments
    {
        public function create(string $orderUuid, int $userId, int $amount, string $currency): PaymentDto
        {
            throw new RuntimeException('payments down');
        }

        public function cancelByOrder(string $orderUuid): void {}

        public function byOrder(string $orderUuid): ?PaymentDto
        {
            return null;
        }
    });

    expect(fn () => app(CheckoutService::class)->place($user, OrderKit::shipping(), null, Str::uuid()->toString()))
        ->toThrow(RuntimeException::class);

    nothingLeftBehind();
    expect(OrderKit::available($product->uuid))->toBe(10)->and(OrderKit::reserved($product->uuid))->toBe(0);
});

it('replays the same order for the same idempotency key', function () {
    [$user, [$product]] = OrderKit::shopper([[1500, 2, 10]]);
    $key = Str::uuid()->toString();

    $first = OrderKit::place($user, $key);
    $second = OrderKit::place($user, $key);

    expect($second->id)->toBe($first->id)
        ->and(Order::query()->count())->toBe(1)
        ->and(DB::table('payments')->count())->toBe(1)
        ->and(OrderKit::available($product->uuid))->toBe(8);
});

it('replays before it looks at the cart', function () {
    [$user] = OrderKit::shopper([[1500, 2, 10]]);
    $key = Str::uuid()->toString();
    $first = OrderKit::place($user, $key);
    app(CartReader::class)->clear($user->id);

    expect(OrderKit::place($user, $key)->id)->toBe($first->id);
});

it('lets two users use the same idempotency key', function () {
    [$one] = OrderKit::shopper([[1500, 1, 10]]);
    $two = IdentityKit::user();
    $product = CartKit::product(['price_amount' => 900], 5);
    app(CartService::class)->add($two->id, $product->uuid, 1);
    $key = Str::uuid()->toString();

    $a = OrderKit::place($one, $key);
    $b = OrderKit::place($two, $key);

    expect($a->id)->not->toBe($b->id)->and(Order::query()->count())->toBe(2);
});

it('validates the shipping address, note and idempotency key', function () {
    [$user] = OrderKit::shopper();
    $service = app(CheckoutService::class);

    try {
        $service->place($user, [], null, Str::uuid()->toString());
        $this->fail('Expected DomainValidationException.');
    } catch (DomainValidationException $e) {
        expect(array_keys($e->fields()))->toContain('name', 'phone', 'address_line1', 'city', 'postal_code', 'country');
    }

    expect(fn () => $service->place($user, OrderKit::shipping(['country' => 'Indonesia']), null, Str::uuid()->toString()))->toThrow(DomainValidationException::class)
        ->and(fn () => $service->place($user, OrderKit::shipping(['phone' => 'abc']), null, Str::uuid()->toString()))->toThrow(DomainValidationException::class)
        ->and(fn () => $service->place($user, OrderKit::shipping(), str_repeat('a', (int) config('shop.order.note_max') + 1), Str::uuid()->toString()))->toThrow(DomainValidationException::class)
        ->and(fn () => $service->place($user, OrderKit::shipping(), null, 'not-a-uuid'))->toThrow(DomainValidationException::class);

    nothingLeftBehind();
});

it('dispatches OrderPlaced only after a successful commit', function () {
    $hits = 0;
    Event::listen(OrderPlaced::class, function () use (&$hits): void {
        $hits++;
    });
    [$user, [$product]] = OrderKit::shopper([[1500, 2, 10]]);
    DB::table('stock_items')->where('product_uuid', $product->uuid)->update(['quantity_on_hand' => 1]);

    expect(fn () => OrderKit::place($user))->toThrow(InsufficientStockException::class)
        ->and($hits)->toBe(0);

    DB::table('stock_items')->where('product_uuid', $product->uuid)->update(['quantity_on_hand' => 10]);
    OrderKit::place($user);

    expect($hits)->toBe(1);
});
