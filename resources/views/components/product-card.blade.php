@props(['product', 'available' => null])

@php
    $soldOut = $available === 0;
    $reasonId = 'reason-'.$product->uuid;
@endphp

<article class="card product-card">
    <x-product-visual :product="$product" />
    <div class="product-card__body">
        @if ($product->category)
            <p class="product-card__category">{{ $product->category->name }}</p>
        @endif
        <h2 class="product-card__name"><a href="{{ route('products.show', ['product' => $product->uuid]) }}">{{ $product->name }}</a></h2>
        <p class="product-card__price">{{ \App\Support\Money::format($product->price_amount, $product->currency) }}</p>
        <livewire:inventory.availability-badge :product-uuid="$product->uuid" :key="'badge-'.$product->uuid" />
        <div class="product-card__actions">
            @if ($soldOut)
                <button type="button" class="btn btn-sm btn-secondary" disabled aria-describedby="{{ $reasonId }}">{{ __('storefront.card.add') }}</button>
                <p class="product-card__reason mt-2" id="{{ $reasonId }}">{{ __('storefront.card.out_of_stock_reason') }}</p>
            @else
                <livewire:cart.add-button :product-uuid="$product->uuid" :key="'cart-'.$product->uuid" />
            @endif
        </div>
    </div>
</article>
