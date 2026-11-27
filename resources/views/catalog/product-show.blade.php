@php
    $available = null;

    try {
        $available = app(\App\Modules\Inventory\Contracts\Inventory::class)->availability([$product->uuid])[$product->uuid] ?? null;
    } catch (\Throwable $e) {
        report($e);
    }

    $soldOut = $available === 0;
    $availabilityText = match (true) {
        $available === null => __('storefront.detail.unavailable'),
        $available === 0 => __('storefront.detail.out_of_stock'),
        $available <= (int) config('shop.low_stock_default_threshold') => __('storefront.detail.low_stock', ['count' => $available]),
        default => __('storefront.detail.in_stock'),
    };
@endphp

<div>
    <nav aria-label="{{ __('storefront.detail.breadcrumb') }}" class="mt-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">{{ __('storefront.nav.products') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="product-detail">
        <div class="product-detail__visual">
            <x-product-visual :product="$product" />
        </div>
        <div class="product-detail__info">
            @if ($product->status !== 'active' || $product->trashed())
                <div class="alert alert-warning">Preview: this product is {{ $product->trashed() ? 'deleted' : $product->status }} and hidden from the public.</div>
            @endif
            @if ($product->category)
                <p class="product-card__category">{{ $product->category->name }}</p>
            @endif
            <h1 class="product-detail__name">{{ $product->name }}</h1>
            <p class="price">{{ \App\Support\Money::format($product->price_amount, $product->currency) }}</p>
            <livewire:inventory.availability-badge :product-uuid="$product->uuid" :key="'badge-'.$product->uuid" />
            <div>
                @if ($soldOut)
                    <button type="button" class="btn btn-secondary" disabled aria-describedby="detail-reason">{{ __('storefront.card.add') }}</button>
                    <p class="product-card__reason mt-2" id="detail-reason">{{ __('storefront.detail.out_reason') }}</p>
                @else
                    <livewire:cart.add-button :product-uuid="$product->uuid" :key="'cart-'.$product->uuid" />
                @endif
            </div>
            @if ($product->description)
                <h2 class="h5 mt-4">{{ __('storefront.detail.about') }}</h2>
                <p class="pre-line measure">{{ $product->description }}</p>
            @endif
            <h2 class="h5 mt-4">{{ __('storefront.detail.details') }}</h2>
            <dl class="meta-list">
                <dt>{{ __('storefront.detail.sku') }}</dt>
                <dd>{{ $product->sku }}</dd>
                @if ($product->category)
                    <dt>{{ __('storefront.detail.category') }}</dt>
                    <dd>{{ $product->category->name }}</dd>
                @endif
                <dt>{{ __('storefront.detail.availability') }}</dt>
                <dd>{{ $availabilityText }}</dd>
            </dl>
        </div>
    </div>

    <div class="mt-5">
        <livewire:comment::thread entity-type="product" :entity-uuid="$product->uuid" :key="'comments-'.$product->uuid" />
    </div>
</div>
