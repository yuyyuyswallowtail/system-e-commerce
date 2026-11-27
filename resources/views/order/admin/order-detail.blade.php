<div>
    <h1 class="h4 mb-3">Order {{ $order->uuid }}</h1>

    @error('status')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Summary</div>
                <div class="card-body">
                    <p class="mb-1">User: {{ $order->user?->email }}</p>
                    <p class="mb-1">Placed: {{ $order->created_at?->format('Y-m-d H:i') }}</p>
                    <p class="mb-1">Status: <span class="badge badge-secondary">{{ $order->status }}</span>
                        @if ($order->cancel_reason) ({{ $order->cancel_reason }}) @endif
                    </p>
                    <p class="mb-1">Total: {{ \App\Support\Money::format($order->total_amount, $order->currency) }}</p>
                    @if ($payment)
                        <p class="mb-0">Payment: <span class="badge badge-info">{{ $payment->status }}</span> {{ \App\Support\Money::format($payment->amount, $payment->currency) }}</p>
                    @else
                        <p class="mb-0 text-muted">No payment.</p>
                    @endif
                </div>
                @if ($canManage && $transitions !== [])
                    <div class="card-footer">
                        @foreach ($transitions as $next)
                            <button type="button" class="btn btn-primary btn-sm" wire:click="changeStatus('{{ $next }}')">Mark as {{ $next }}</button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Shipping address</div>
                <div class="card-body">
                    @php($address = (array) $order->shipping_address)
                    <p class="mb-0">
                        {{ $address['name'] ?? '' }}<br>
                        {{ $address['address_line1'] ?? '' }}<br>
                        @if (! empty($address['address_line2'])) {{ $address['address_line2'] }}<br> @endif
                        {{ $address['postal_code'] ?? '' }} {{ $address['city'] ?? '' }}, {{ $address['country'] ?? '' }}<br>
                        {{ $address['phone'] ?? '' }}
                    </p>
                    @if ($order->note)
                        <hr><p class="mb-0 text-muted">{{ $order->note }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive table-sticky p-0">
            <table class="table text-nowrap mb-0">
                <thead>
                    <tr><th>Product</th><th>SKU</th><th class="num">Price</th><th class="num">Quantity</th><th class="num">Total</th></tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr wire:key="item-{{ $item->id }}">
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->sku }}</td>
                            <td class="money">{{ \App\Support\Money::format($item->unit_price_amount, $order->currency) }}</td>
                            <td class="num">{{ $item->quantity }}</td>
                            <td class="money">{{ \App\Support\Money::format($item->line_total_amount, $order->currency) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('admin.orders.index') }}" class="btn btn-link">Back to orders</a>
</div>
