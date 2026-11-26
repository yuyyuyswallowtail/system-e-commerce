<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Inventory\Contracts\Inventory;
use App\Modules\Inventory\Events\StockAdjusted;
use App\Modules\Inventory\Events\StockCommitted;
use App\Modules\Inventory\Events\StockReleased;
use App\Modules\Inventory\Events\StockReserved;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\StockItem;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\StockReservation;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class InventoryService implements Inventory
{
    public function availability(array $productUuids): array
    {
        $uuids = array_values(array_unique(array_map('strval', $productUuids)));

        if (count($uuids) > (int) config('shop.availability_max_uuids')) {
            throw new InvalidArgumentException('Too many product uuids requested.');
        }

        if ($uuids === []) {
            return [];
        }

        $this->expire($uuids);

        $rows = StockItem::query()->whereIn('product_uuid', $uuids)->get()->keyBy('product_uuid');
        $result = [];

        foreach ($uuids as $uuid) {
            $row = $rows->get($uuid);
            $result[$uuid] = $row === null ? 0 : $row->available();
        }

        return $result;
    }

    public function reserve(string $orderUuid, array $items): void
    {
        $lines = $this->normalizeItems($orderUuid, $items);
        $uuids = array_keys($lines);
        $ttl = (int) config('shop.reservation_ttl_seconds');

        $created = DB::transaction(function () use ($orderUuid, $lines, $uuids, $ttl): bool {
            $this->expire($uuids);

            $stock = StockItem::query()
                ->whereIn('product_uuid', $uuids)
                ->orderBy('product_uuid')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_uuid');

            $existing = StockReservation::query()->where('order_uuid', $orderUuid)->get();

            if ($existing->isNotEmpty()) {
                $usable = [$this->status('reserved'), $this->status('committed')];

                if ($existing->contains(fn (StockReservation $row): bool => ! in_array($row->status, $usable, true))) {
                    throw new ConflictException('The reservation for this order is no longer active.');
                }

                return false;
            }

            foreach ($lines as $productUuid => $quantity) {
                $item = $stock->get($productUuid);
                $available = $item === null ? 0 : $item->available();

                if ($available < $quantity) {
                    throw new InsufficientStockException($productUuid, $available);
                }
            }

            $expiresAt = Carbon::now()->addSeconds($ttl);

            foreach ($lines as $productUuid => $quantity) {
                StockReservation::create([
                    'uuid' => Str::uuid()->toString(),
                    'order_uuid' => $orderUuid,
                    'product_uuid' => $productUuid,
                    'quantity' => $quantity,
                    'status' => $this->status('reserved'),
                    'expires_at' => $expiresAt,
                ]);

                $item = $stock->get($productUuid);
                $item->quantity_reserved += $quantity;
                $item->save();
            }

            return true;
        });

        if ($created) {
            StockReserved::dispatch($orderUuid, null, ['items' => $lines]);
        }
    }

    public function commit(string $orderUuid): void
    {
        $this->assertUuid($orderUuid, 'order_uuid');

        $done = DB::transaction(function () use ($orderUuid): bool {
            $uuids = StockReservation::query()->where('order_uuid', $orderUuid)->orderBy('product_uuid')->pluck('product_uuid')->all();

            if ($uuids === []) {
                throw new ConflictException('There is no reservation for this order.');
            }

            $stock = StockItem::query()
                ->whereIn('product_uuid', $uuids)
                ->orderBy('product_uuid')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_uuid');

            $rows = StockReservation::query()->where('order_uuid', $orderUuid)->orderBy('product_uuid')->lockForUpdate()->get();
            $reserved = $rows->where('status', $this->status('reserved'));
            $usable = [$this->status('reserved'), $this->status('committed')];

            if ($rows->contains(fn (StockReservation $row): bool => ! in_array($row->status, $usable, true))) {
                throw new ConflictException('The reservation for this order is no longer active.');
            }

            if ($reserved->isEmpty()) {
                return false;
            }

            $now = Carbon::now();

            if ($reserved->contains(fn (StockReservation $row): bool => $row->expires_at->lessThan($now))) {
                throw new ConflictException('The reservation for this order has expired.');
            }

            foreach ($reserved as $row) {
                $item = $stock->get($row->product_uuid);

                if ($item === null || $item->quantity_on_hand < $row->quantity || $item->quantity_reserved < $row->quantity) {
                    throw new ConflictException('The stock no longer covers this reservation.');
                }

                $item->quantity_on_hand -= $row->quantity;
                $item->quantity_reserved -= $row->quantity;
                $item->save();

                StockMovement::create([
                    'product_uuid' => $row->product_uuid,
                    'delta' => -$row->quantity,
                    'reason' => (string) config('shop.inventory.commit_reason'),
                    'reference' => $orderUuid,
                    'actor_id' => null,
                ]);

                $row->status = $this->status('committed');
                $row->save();
            }

            return true;
        });

        if ($done) {
            StockCommitted::dispatch($orderUuid);
        }
    }

    public function release(string $orderUuid): void
    {
        $this->assertUuid($orderUuid, 'order_uuid');

        $done = DB::transaction(function () use ($orderUuid): bool {
            $uuids = StockReservation::query()
                ->where('order_uuid', $orderUuid)
                ->where('status', $this->status('reserved'))
                ->orderBy('product_uuid')
                ->pluck('product_uuid')
                ->all();

            if ($uuids === []) {
                return false;
            }

            $stock = StockItem::query()
                ->whereIn('product_uuid', $uuids)
                ->orderBy('product_uuid')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_uuid');

            $rows = StockReservation::query()
                ->where('order_uuid', $orderUuid)
                ->where('status', $this->status('reserved'))
                ->orderBy('product_uuid')
                ->lockForUpdate()
                ->get();

            foreach ($rows as $row) {
                $item = $stock->get($row->product_uuid);

                if ($item !== null) {
                    $item->quantity_reserved = max(0, $item->quantity_reserved - $row->quantity);
                    $item->save();
                }

                $row->status = $this->status('released');
                $row->save();
            }

            return $rows->isNotEmpty();
        });

        if ($done) {
            StockReleased::dispatch($orderUuid);
        }
    }

    /**
     * Manual stock change. The caller must already hold stock.adjust (checked at the UI layer).
     */
    public function adjust(string $productUuid, int $delta, string $reason, ?int $actorId): StockItem
    {
        $this->assertUuid($productUuid, 'product_uuid');

        $allowed = array_values(array_diff((array) config('shop.adjust_reasons'), [(string) config('shop.inventory.commit_reason')]));

        if (! in_array($reason, $allowed, true)) {
            throw new DomainValidationException(['reason' => 'The reason is not allowed.']);
        }

        if ($delta === 0) {
            throw new DomainValidationException(['delta' => 'The change must not be zero.']);
        }

        $productUuid = strtolower($productUuid);

        $item = DB::transaction(function () use ($productUuid, $delta, $reason, $actorId): StockItem {
            if ($delta > 0) {
                StockItem::query()->insertOrIgnore([
                    'product_uuid' => $productUuid,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

            $item = StockItem::query()->whereKey($productUuid)->lockForUpdate()->first();

            if ($item === null) {
                throw new DomainValidationException(['delta' => 'There is no stock to reduce.']);
            }

            $newOnHand = $item->quantity_on_hand + $delta;

            if ($newOnHand < $item->quantity_reserved) {
                throw new DomainValidationException(['delta' => 'The stock cannot drop below the reserved quantity.']);
            }

            $item->quantity_on_hand = $newOnHand;
            $item->save();

            StockMovement::create([
                'product_uuid' => $productUuid,
                'delta' => $delta,
                'reason' => $reason,
                'reference' => null,
                'actor_id' => $actorId,
            ]);

            $logger = activity('inventory')->event('adjusted');

            if ($actorId !== null) {
                $logger->causedBy(User::query()->find($actorId));
            }

            $logger->withProperties(['product_uuid' => $productUuid, 'delta' => $delta, 'reason' => $reason, 'on_hand' => $newOnHand])
                ->log('Stock adjusted');

            return $item;
        });

        StockAdjusted::dispatch($productUuid, $actorId, ['delta' => $delta, 'reason' => $reason, 'on_hand' => $item->quantity_on_hand]);

        return $item;
    }

    /**
     * Marks reserved rows past their expiry as expired and frees the stock. Returns the number of rows expired.
     *
     * @param  array<int, string>|null  $productUuids  Limit to these products, or null for all.
     */
    public function expire(?array $productUuids = null): int
    {
        $now = Carbon::now();

        return DB::transaction(function () use ($productUuids, $now): int {
            $query = StockReservation::query()
                ->where('status', $this->status('reserved'))
                ->where('expires_at', '<', $now);

            if ($productUuids !== null) {
                $query->whereIn('product_uuid', $productUuids);
            }

            $uuids = (clone $query)->distinct()->orderBy('product_uuid')->pluck('product_uuid')->all();

            if ($uuids === []) {
                return 0;
            }

            $stock = StockItem::query()
                ->whereIn('product_uuid', $uuids)
                ->orderBy('product_uuid')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_uuid');

            $rows = $query->orderBy('id')->lockForUpdate()->get();

            foreach ($rows as $row) {
                $item = $stock->get($row->product_uuid);

                if ($item !== null) {
                    $item->quantity_reserved = max(0, $item->quantity_reserved - $row->quantity);
                    $item->save();
                }

                $row->status = $this->status('expired');
                $row->save();
            }

            return $rows->count();
        });
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<string, int> product uuid => quantity, sorted by product uuid
     */
    private function normalizeItems(string $orderUuid, array $items): array
    {
        $this->assertUuid($orderUuid, 'order_uuid');

        if ($items === []) {
            throw new DomainValidationException(['items' => 'At least one item is required.']);
        }

        if (count($items) > (int) config('shop.inventory.reservation_max_items')) {
            throw new DomainValidationException(['items' => 'Too many items.']);
        }

        $lines = [];

        foreach ($items as $item) {
            $product = is_array($item) ? ($item['product_uuid'] ?? null) : null;
            $quantity = is_array($item) ? ($item['quantity'] ?? null) : null;

            if (! is_string($product) || ! Str::isUuid($product)) {
                throw new DomainValidationException(['product_uuid' => 'The product uuid is invalid.']);
            }

            if (! is_int($quantity) || $quantity < 1) {
                throw new DomainValidationException(['quantity' => 'The quantity must be an integer of at least 1.']);
            }

            $product = strtolower($product);

            if (isset($lines[$product])) {
                throw new DomainValidationException(['product_uuid' => 'The same product appears more than once.']);
            }

            $lines[$product] = $quantity;
        }

        ksort($lines, SORT_STRING);

        return $lines;
    }

    private function assertUuid(string $value, string $field): void
    {
        if (! Str::isUuid($value)) {
            throw new DomainValidationException([$field => 'The uuid is invalid.']);
        }
    }

    private function status(string $key): string
    {
        return (string) config('shop.inventory.statuses.'.$key);
    }
}
