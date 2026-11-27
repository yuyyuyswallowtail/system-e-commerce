<div>
    <x-page-header :title="'Order '.$order->uuid" :subtitle="'Placed '.$order->created_at?->format('Y-m-d H:i')" />

    <p>
        <x-status-badge group="order_statuses" :status="$order->status" />
        @if ($order->cancel_reason) <span class="text-muted">({{ $order->cancel_reason }})</span> @endif
    </p>

    @error('payment')
        <div class="alert alert-danger" role="alert">{{ $message }}</div>
    @enderror
    @error('order')
        <div class="alert alert-danger" role="alert">{{ $message }}</div>
    @enderror

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-stack mb-0">
                <thead>
                    <tr><th>Product</th><th>SKU</th><th class="num">Price</th><th class="num">Quantity</th><th class="num">Total</th></tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr wire:key="item-{{ $item->id }}">
                            <td data-label="Product">{{ $item->product_name }}</td>
                            <td data-label="SKU">{{ $item->sku }}</td>
                            <td data-label="Price" class="money">{{ \App\Support\Money::format($item->unit_price_amount, $order->currency) }}</td>
                            <td data-label="Quantity" class="num">{{ $item->quantity }}</td>
                            <td data-label="Total" class="money">{{ \App\Support\Money::format($item->line_total_amount, $order->currency) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Total</th>
                        <th class="money">{{ \App\Support\Money::format($order->total_amount, $order->currency) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Payment</h2></div>
                <div class="card-body">
                    @if ($payment)
                        <p class="mb-1">Status: <x-status-badge group="payment_statuses" :status="$payment->status" /></p>
                        <p class="mb-1">Amount: <span class="num">{{ \App\Support\Money::format($payment->amount, $payment->currency) }}</span></p>
                        @if ($payment->status === config('shop.payment.statuses.pending'))
                            <p class="text-muted mb-0">Pay before {{ $payment->expiresAt->format('Y-m-d H:i') }} or the order is cancelled.</p>
                        @endif
                    @else
                        <p class="text-muted mb-0">There is no payment for this order.</p>
                    @endif
                </div>
                @if ($canPay || $canCancel)
                    <div class="card-footer">
                        @if ($canPay)
                            <button type="button" class="btn btn-primary" wire:click="pay" wire:loading.attr="disabled" wire:target="pay">Pay now</button>
                        @endif
                        @if ($canFail)
                            <button type="button" class="btn btn-outline-secondary" wire:click="fail" wire:loading.attr="disabled" wire:target="fail">Simulate failure</button>
                        @endif
                        @if ($canCancel)
                            <button type="button" class="btn btn-outline-danger" wire:click="cancel" wire:loading.attr="disabled" wire:target="cancel" wire:confirm="Cancel this order? Reserved stock is released and this cannot be undone.">Cancel order</button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Shipping address</h2></div>
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
