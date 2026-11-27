<div>
    <x-page-header title="Your orders" subtitle="Track what you have ordered and open an order to pay or cancel it." />

    <div class="filter-bar">
        <div class="filter-bar__field form-group">
            <label for="order-status">Status</label>
            <select id="order-status" class="form-control filter-select" wire:model.live="status">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ \Illuminate\Support\Str::of($status)->replace('_', ' ')->ucfirst() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($orders->isEmpty())
        <x-empty-state title="No orders yet" text="Your orders will show up here once you check out." :action-url="route('products.index')" action-label="Start shopping" />
    @else
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-stack">
                    <thead>
                        <tr><th>Order</th><th>Placed</th><th>Status</th><th class="num">Total</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr wire:key="order-{{ $order->uuid }}">
                                <td data-label="Order" class="text-break"><a href="{{ route('orders.show', $order) }}">{{ $order->uuid }}</a></td>
                                <td data-label="Placed">{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                                <td data-label="Status"><x-status-badge group="order_statuses" :status="$order->status" /></td>
                                <td data-label="Total" class="money">{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $orders->links() }}</div>
        </div>
    @endif
</div>
