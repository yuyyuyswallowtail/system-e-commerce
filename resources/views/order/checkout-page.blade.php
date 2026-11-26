<div>
    <h1 class="h3 mb-3">Checkout</h1>

    @error('cart')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="row">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">Shipping address</div>
                <form wire:submit="submit">
                    <div class="card-body">
                        @foreach (['name' => 'Full name', 'phone' => 'Phone', 'address_line1' => 'Address line 1', 'address_line2' => 'Address line 2', 'city' => 'City', 'postal_code' => 'Postal code', 'country' => 'Country (2 letters)', 'note' => 'Note'] as $field => $label)
                            <div class="form-group">
                                <label for="form-{{ $field }}">{{ $label }}</label>
                                <input type="text" id="form-{{ $field }}" class="form-control @error('form.'.$field) is-invalid @enderror" wire:model="form.{{ $field }}">
                                @error('form.'.$field)
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Place order</button>
                        <a href="{{ route('cart.index') }}" class="btn btn-link">Back to cart</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">Your order</div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <tbody>
                            @foreach ($rows as $row)
                                <tr wire:key="row-{{ $row['uuid'] }}">
                                    <td>{{ $row['name'] ?? $row['uuid'] }} &times; {{ $row['quantity'] }}</td>
                                    <td class="text-right">{{ \App\Support\Money::format($row['lineTotal'], $row['currency']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @foreach ($totals as $currency => $amount)
                                <tr>
                                    <th>Total</th>
                                    <th class="text-right">{{ \App\Support\Money::format($amount, (string) $currency) }}</th>
                                </tr>
                            @endforeach
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
