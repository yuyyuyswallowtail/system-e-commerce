<div>
    <h1 class="h3 mb-3">Your orders</h1>

    <div class="form-group">
        <label for="order-status">Status</label>
        <select id="order-status" class="form-control" style="max-width: 240px;" wire:model.live="status">
            <option value="">All</option>
            @foreach ($statuses as $status)
                <option value="{{ $status }}">{{ $status }}</option>
            @endforeach
        </select>
    </div>

    @if ($orders->isEmpty())
        <div class="alert alert-info">No orders yet.</div>
    @else
        <div class="card">
            <div class="card-body table-responsive p-0">
                <table class="table text-nowrap">
                    <thead>
                        <tr><th>Order</th><th>Placed</th><th>Status</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr wire:key="order-{{ $order->uuid }}">
                                <td><a href="{{ route('orders.show', $order) }}">{{ $order->uuid }}</a></td>
                                <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                                <td><span class="badge badge-secondary">{{ $order->status }}</span></td>
                                <td>{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $orders->links() }}</div>
        </div>
    @endif
</div>
