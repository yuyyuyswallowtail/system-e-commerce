<div>
    <x-page-header title="Your cart" subtitle="Review your items before checkout." />

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if ($rows === [])
        <div class="alert alert-info">Your cart is empty. <a href="{{ route('products.index') }}">Browse products</a></div>
    @else
        <div class="card">
            <div class="card-body table-responsive p-0">
                <table class="table text-nowrap">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th class="col-qty">Quantity</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr wire:key="line-{{ $row['uuid'] }}">
                                <td>
                                    {{ $row['name'] ?? $row['uuid'] }}
                                    @unless ($row['available'])
                                        <span class="badge badge-danger">Unavailable</span>
                                    @endunless
                                </td>
                                @if ($row['available'])
                                    <td>{{ \App\Support\Money::format($row['unitPrice'], $row['currency']) }}</td>
                                    <td>
                                        <input type="number" aria-label="Quantity for {{ $row['name'] ?? $row['uuid'] }}" min="1" max="{{ $maxQuantity }}" value="{{ $row['quantity'] }}"
                                               class="form-control form-control-sm"
                                               wire:change="setQuantity('{{ $row['uuid'] }}', $event.target.value)">
                                    </td>
                                    <td>{{ \App\Support\Money::format($row['lineTotal'], $row['currency']) }}</td>
                                @else
                                    <td class="text-muted">-</td>
                                    <td>{{ $row['quantity'] }}</td>
                                    <td class="text-muted">-</td>
                                @endif
                                <td class="text-right">
                                    <button type="button" class="btn btn-sm btn-outline-danger" wire:click="remove('{{ $row['uuid'] }}')" wire:confirm="Remove this item from your cart?">Remove</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        @foreach ($totals as $currency => $amount)
                            <tr>
                                <th colspan="3" class="text-right">Total</th>
                                <th>{{ \App\Support\Money::format($amount, (string) $currency) }}</th>
                                <th></th>
                            </tr>
                        @endforeach
                    </tfoot>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary" wire:click="clear" wire:confirm="Clear the whole cart?">Clear cart</button>
                @if ($canCheckout)
                    <a href="{{ route('checkout') }}" class="btn btn-primary">Proceed to checkout</a>
                @elseif ($checkoutRoute && $blocked)
                    <span class="text-muted">Remove unavailable items to continue.</span>
                @endif
            </div>
        </div>
    @endif
</div>
