<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-5"><input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search by user name or email"></div>
            <div class="col-md-3">
                <select wire:model.live="filters.status" class="form-control">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th><a href="#" wire:click.prevent="sortBy('status')">Status</a></th>
                    <th><a href="#" wire:click.prevent="sortBy('updated_at')">Updated</a></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($carts as $cart)
                    <tr wire:key="cart-{{ $cart->id }}">
                        <td>{{ $cart->user_name }}<br><small class="text-muted">{{ $cart->user_email }}</small></td>
                        <td>{{ $cart->items_count }}</td>
                        <td>{{ \App\Support\Money::format($totals[$cart->id]['amount'], $totals[$cart->id]['currency']) }}</td>
                        <td>{{ $cart->status }}</td>
                        <td>{{ $cart->updated_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No carts found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $carts->links() }}</div>
</div>
