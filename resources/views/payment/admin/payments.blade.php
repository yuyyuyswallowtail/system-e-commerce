<div>
    @error('refund')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="card">
        <div class="card-header">
            <select class="form-control filter-select" wire:model.live="status" aria-label="Status">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table text-nowrap">
                <thead>
                    <tr><th>Payment</th><th>Order</th><th>Amount</th><th>Status</th><th>Paid</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr wire:key="payment-{{ $payment->uuid }}">
                            <td>{{ $payment->provider_reference }}</td>
                            <td>{{ $payment->order_uuid }}</td>
                            <td>{{ \App\Support\Money::format($payment->amount, $payment->currency) }}</td>
                            <td><span class="badge badge-info">{{ $payment->status }}</span></td>
                            <td>{{ $payment->paid_at?->format('Y-m-d H:i') }}</td>
                            <td class="text-right">
                                @if ($canRefund && $payment->status === $paidStatus)
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" placeholder="Reason" wire:model="reasons.{{ $payment->uuid }}">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-danger" wire:click="refund('{{ $payment->uuid }}')" wire:confirm="Refund this payment?">Refund</button>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-muted">No payments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $payments->links() }}</div>
    </div>
</div>
