<div>
    <x-page-header title="Checkout" subtitle="Tell us where to send your order. You pay on the next step." />

    @error('cart')
        <div class="alert alert-danger" role="alert">{{ $message }}</div>
    @enderror

    <div class="row">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Shipping address</h2></div>
                <form wire:submit="submit">
                    <div class="card-body">
                        @foreach ([
                            'name' => ['Full name', 'name', 'text'],
                            'phone' => ['Phone', 'tel', 'tel'],
                            'address_line1' => ['Address line 1', 'address-line1', 'text'],
                            'address_line2' => ['Address line 2 (optional)', 'address-line2', 'text'],
                            'city' => ['City', 'address-level2', 'text'],
                            'postal_code' => ['Postal code', 'postal-code', 'text'],
                            'country' => ['Country (2-letter code)', 'country', 'text'],
                            'note' => ['Note for the courier (optional)', 'off', 'text'],
                        ] as $field => [$label, $autocomplete, $type])
                            <div class="form-group">
                                <label for="form-{{ $field }}">{{ $label }}</label>
                                <input type="{{ $type }}" id="form-{{ $field }}" autocomplete="{{ $autocomplete }}" class="form-control @error('form.'.$field) is-invalid @enderror" wire:model="form.{{ $field }}" @error('form.'.$field) aria-invalid="true" aria-describedby="form-{{ $field }}-error" @enderror>
                                @error('form.'.$field)
                                    <span class="invalid-feedback" id="form-{{ $field }}-error" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="submit">
                            <span wire:loading.remove wire:target="submit">Place order</span>
                            <span wire:loading wire:target="submit">Placing order&hellip;</span>
                        </button>
                        <a href="{{ route('cart.index') }}" class="btn btn-link">Back to cart</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card">
                <div class="card-header"><h2 class="card-title">Order summary</h2></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <caption class="sr-only">Items in your order</caption>
                        <tbody>
                            @foreach ($rows as $row)
                                <tr wire:key="row-{{ $row['uuid'] }}">
                                    <td>{{ $row['name'] ?? $row['uuid'] }} &times; {{ $row['quantity'] }}</td>
                                    <td class="money">{{ \App\Support\Money::format($row['lineTotal'], $row['currency']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @foreach ($totals as $currency => $amount)
                                <tr>
                                    <th>Total</th>
                                    <th class="money">{{ \App\Support\Money::format($amount, (string) $currency) }}</th>
                                </tr>
                            @endforeach
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
