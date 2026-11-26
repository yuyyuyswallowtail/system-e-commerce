<div>
    <h1 class="h3 mb-1">Order {{ $order->uuid }}</h1>
    <p class="text-muted">Placed {{ $order->created_at?->format('Y-m-d H:i') }} &middot; <span class="badge badge-secondary">{{ $order->status }}</span>
        @if ($order->cancel_reason) ({{ $order->cancel_reason }}) @endif
    </p>

    @error('payment')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
    @error('order')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table text-nowrap mb-0">
                <thead>
                    <tr><th>Product</th><th>SKU</th><th>Price</th><th>Quantity</th><th>Total</th></tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr wire:key="item-{{ $item->id }}">
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->sku }}</td>
                            <td>{{ \App\Support\Money::format($item->unit_price_amount, $order->currency) }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ \App\Support\Money::format($item->line_total_amount, $order->currency) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Total</th>
                        <th>{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Payment</div>
                <div class="card-body">
                    @if ($payment)
                        <p class="mb-1">Status: <span class="badge badge-info">{{ $payment->status }}</span></p>
                        <p class="mb-1">Amount: {{ \App\Support\Money::format($payment->amount, $payment->currency) }}</p>
                        @if ($payment->status === config('shop.payment.statuses.pending'))
                            <p class="text-muted mb-0">Expires {{ $payment->expiresAt->format('Y-m-d H:i') }}</p>
                        @endif
                    @else
                        <p class="text-muted mb-0">No payment.</p>
                    @endif
                </div>
                @if ($canPay || $canCancel)
                    <div class="card-footer">
                        @if ($canPay)
                            <button type="button" class="btn btn-success" wire:click="pay">Pay</button>
                        @endif
                        @if ($canFail)
                            <button type="button" class="btn btn-outline-warning" wire:click="fail">Simulate failure</button>
                        @endif
                        @if ($canCancel)
                            <button type="button" class="btn btn-outline-danger" wire:click="cancel" wire:confirm="Cancel this order?">Cancel order</button>
                        @endif
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

    <a href="{{ route('orders.index') }}" class="btn btn-link">Back to orders</a>
</div>
