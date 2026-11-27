<div>
    <x-page-header title="Stock" />

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-5"><input aria-label="Search by product uuid" type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search by product uuid"></div>
            <div class="col-md-4">
                <div class="custom-control custom-switch mt-2">
                    <input type="checkbox" class="custom-control-input" id="lowOnly" wire:model.live="lowOnly">
                    <label class="custom-control-label" for="lowOnly">Low stock only</label>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive table-sticky p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>Product</th>
                    <th><a href="#" wire:click.prevent="sortBy('quantity_on_hand')">On hand</a></th>
                    <th><a href="#" wire:click.prevent="sortBy('quantity_reserved')">Reserved</a></th>
                    <th>Available</th>
                    <th>Threshold</th>
                    <th><a href="#" wire:click.prevent="sortBy('updated_at')">Updated</a></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($items as $item)
                    @php($available = $item->available())
                    @php($threshold = $item->low_stock_threshold ?? $defaultThreshold)
                    <tr wire:key="stock-{{ $item->product_uuid }}">
                        <td>
                            @if (isset($names[$item->product_uuid]))
                                {{ $names[$item->product_uuid] }}<br><small class="text-muted">{{ $item->product_uuid }}</small>
                            @else
                                {{ $item->product_uuid }}
                            @endif
                        </td>
                        <td>{{ $item->quantity_on_hand }}</td>
                        <td>{{ $item->quantity_reserved }}</td>
                        <td>
                            {{ $available }}
                            @if ($available === 0)
                                <span class="badge badge-danger">out</span>
                            @elseif ($available <= $threshold)
                                <span class="badge badge-warning">low</span>
                            @endif
                        </td>
                        <td>{{ $threshold }}</td>
                        <td>{{ $item->updated_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.stock.show', ['productUuid' => $item->product_uuid]) }}" class="btn btn-sm btn-outline-primary">Details</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No stock items found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $items->links() }}</div>
</div>
</div>
