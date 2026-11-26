<div>
    <div class="card">
        <div class="card-header">
            <div class="form-row">
                <div class="col-md-4 mb-2">
                    <input type="search" class="form-control" placeholder="Search by order uuid or user email" wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-2 mb-2">
                    <select class="form-control" wire:model.live="status">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <input type="date" class="form-control @error('dateFrom') is-invalid @enderror" wire:model.live="dateFrom" aria-label="From date">
                    @error('dateFrom')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
                <div class="col-md-3 mb-2">
                    <input type="date" class="form-control @error('dateTo') is-invalid @enderror" wire:model.live="dateTo" aria-label="To date">
                    @error('dateTo')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table text-nowrap">
                <thead>
                    <tr><th>Order</th><th>User</th><th>Placed</th><th>Status</th><th>Total</th></tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="order-{{ $order->uuid }}">
                            <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->uuid }}</a></td>
                            <td>{{ $order->user?->email }}</td>
                            <td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
                            <td><span class="badge badge-secondary">{{ $order->status }}</span></td>
                            <td>{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $orders->links() }}</div>
    </div>
</div>
