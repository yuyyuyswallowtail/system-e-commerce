<?php

declare(strict_types=1);

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Inventory\Contracts\Inventory;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Events\OrderStatusChanged;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Contracts\Payments;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use App\Support\Exceptions\NotFoundException;
use App\Support\Sql;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class OrderService
{
    public function __construct(
        private readonly Payments $payments,
        private readonly Inventory $inventory,
    ) {}

    public function cancelByOwner(string $orderUuid, User $actor): Order
    {
        $order = Order::query()->where('uuid', $orderUuid)->first();

        if ($order === null || $order->user_id !== $actor->id) {
            throw new NotFoundException;
        }

        if ($order->status !== $this->status('awaiting_payment')) {
            throw new ConflictException('Only an order waiting for payment can be cancelled.');
        }

        $cancelled = DB::transaction(function () use ($order): Order {
            // Payment row first, then the order row: the same lock order as a payment result.
            $this->payments->cancelByOrder($order->uuid);

            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== $this->status('awaiting_payment')) {
                throw new ConflictException('Only an order waiting for payment can be cancelled.');
            }

            $locked->forceFill([
                'status' => $this->status('cancelled'),
                'cancel_reason' => (string) config('shop.order.cancel_reasons.owner'),
                'cancelled_at' => Carbon::now(),
            ])->save();
            $this->inventory->release($locked->uuid);

            return $locked;
        });

        OrderCancelled::dispatch($cancelled->uuid, $cancelled->user_id, $actor->id, ['reason' => $cancelled->cancel_reason]);

        return $cancelled;
    }

    public function changeStatus(string $orderUuid, string $to, User $actor): Order
    {
        if (! $actor->can('orders.manage')) {
            throw new AuthorizationException;
        }

        $from = '';

        $order = DB::transaction(function () use ($orderUuid, $to, &$from): Order {
            $order = Order::query()->where('uuid', $orderUuid)->lockForUpdate()->first() ?? throw new NotFoundException;
            $from = $order->status;
            $allowed = (array) config("shop.manual_order_transitions.{$from}", []);

            if (! in_array($to, $allowed, true)) {
                throw new ConflictException('This status change is not allowed.');
            }

            $order->forceFill(['status' => $to])->save();

            return $order;
        });

        OrderStatusChanged::dispatch($order->uuid, $order->user_id, $actor->id, ['from' => $from, 'to' => $to]);

        return $order;
    }

    /**
     * @return LengthAwarePaginator<int, Order>
     */
    public function listForUser(User $user, ?string $status = null, ?int $perPage = null): LengthAwarePaginator
    {
        $query = Order::query()->where('user_id', $user->id);

        if ($status !== null && in_array($status, (array) config('shop.order_statuses'), true)) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate($this->perPage($perPage));
    }

    /**
     * @param  array<string, mixed>  $filters  search, status, date_from, date_to (Y-m-d)
     * @return LengthAwarePaginator<int, Order>
     */
    public function adminList(array $filters, string $sortField = '', string $sortDirection = '', ?int $perPage = null): LengthAwarePaginator
    {
        $config = (array) config('tables.orders');
        $field = in_array($sortField, $config['sortable'], true) ? $sortField : $config['default_sort'][0];
        $direction = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : $config['default_sort'][1];

        $query = Order::query()
            ->select('orders.*')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->with('user');

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $like = '%'.Sql::escapeLike($search).'%';

            $query->where(function ($inner) use ($config, $like): void {
                foreach ($config['searchable'] as $column) {
                    $inner->orWhere($column, 'like', $like);
                }
            });
        }

        $status = (string) ($filters['status'] ?? '');

        if ($status !== '' && in_array($status, (array) config('shop.order_statuses'), true)) {
            $query->where('orders.status', $status);
        }

        $from = $this->date($filters['date_from'] ?? null, 'date_from');
        $to = $this->date($filters['date_to'] ?? null, 'date_to');

        if ($from !== null) {
            $query->where('orders.created_at', '>=', $from->startOfDay());
        }

        if ($to !== null) {
            $query->where('orders.created_at', '<=', $to->endOfDay());
        }

        return $query->orderBy($field, $direction)->orderByDesc('orders.id')->paginate($this->perPage($perPage));
    }

    private function date(mixed $value, string $field): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (! Carbon::hasFormat($value, 'Y-m-d')) {
            throw new DomainValidationException([$field => 'The date must use the format YYYY-MM-DD.']);
        }

        return Carbon::createFromFormat('Y-m-d', $value) ?: throw new DomainValidationException([$field => 'The date is invalid.']);
    }

    private function perPage(?int $perPage): int
    {
        $default = (int) config('shop.pagination.default');
        $max = (int) config('shop.pagination.max');

        return $perPage === null || $perPage < 1 ? $default : min($perPage, $max);
    }

    private function status(string $key): string
    {
        return (string) config("shop.order.statuses.{$key}");
    }
}
