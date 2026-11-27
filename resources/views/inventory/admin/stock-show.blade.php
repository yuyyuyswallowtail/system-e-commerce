<div>
    <x-page-header title="Stock details" />

<div>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">{{ $name ?? $productUuid }}</h2>
            <div class="card-tools"><a href="{{ route('admin.stock.index') }}" class="btn btn-sm btn-outline-secondary">Back to stock</a></div>
        </div>
        <div class="card-body">
            <p class="text-muted mb-2">{{ $productUuid }}</p>
            @if ($item)
                <p class="mb-0">On hand: <strong>{{ $item->quantity_on_hand }}</strong>, reserved: <strong>{{ $item->quantity_reserved }}</strong>, available: <strong>{{ $item->available() }}</strong></p>
            @else
                <p class="mb-0 text-muted">No stock recorded yet.</p>
            @endif
        </div>
    </div>

    @if ($canAdjust)
        <div class="card">
            <div class="card-header"><h2 class="card-title">Adjust stock</h2></div>
            <div class="card-body">
                @if ($notice !== '')
                    <div class="alert alert-success">{{ $notice }}</div>
                @endif
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="delta">Change (use a minus sign to remove)</label>
                        <input type="text" id="delta" wire:model="delta" class="form-control @error('delta') is-invalid @enderror" inputmode="numeric">
                        @error('delta') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label for="reason">Reason</label>
                        <select id="reason" wire:model="reason" class="form-control @error('reason') is-invalid @enderror">
                            @foreach ($reasons as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('reason') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group col-md-3 d-flex align-items-end">
                        <button type="button" wire:click="adjust" class="btn btn-primary">Apply</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header"><h2 class="card-title">Movements</h2></div>
        <div class="card-body table-responsive table-sticky p-0">
            <table class="table table-hover text-nowrap">
                <thead>
                    <tr>
                        <th><a href="#" wire:click.prevent="$set('sortField', 'created_at')">When</a></th>
                        <th><a href="#" wire:click.prevent="$set('sortField', 'delta')">Change</a></th>
                        <th>Reason</th>
                        <th>Reference</th>
                        <th>Actor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr wire:key="movement-{{ $movement->id }}">
                            <td>{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $movement->delta > 0 ? '+' : '' }}{{ $movement->delta }}</td>
                            <td>{{ $movement->reason }}</td>
                            <td>{{ $movement->reference }}</td>
                            <td>{{ $movement->actor_id }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No movements yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $movements->links() }}</div>
    </div>
</div>
</div>
