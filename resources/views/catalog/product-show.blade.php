<div>
    <nav aria-label="Breadcrumb" class="mt-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>

    <div class="row mt-3">
        <div class="col-md-5 mb-4">
            @if ($product->image_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('shop.uploads.disk'))->url($product->image_path) }}" alt="{{ $product->name }}" class="img-fluid">
            @else
                <div class="product-card__media" aria-hidden="true"><i class="{{ config('ui.icons.products') }} fa-3x"></i></div>
            @endif
        </div>
        <div class="col-md-7">
            @if ($product->status !== 'active' || $product->trashed())
                <div class="alert alert-warning">Preview: this product is {{ $product->trashed() ? 'deleted' : $product->status }} and hidden from the public.</div>
            @endif
            <h1>{{ $product->name }}</h1>
            @if ($product->category)
                <p class="text-muted">{{ $product->category->name }}</p>
            @endif
            <p class="price">{{ \App\Support\Money::format($product->price_amount, $product->currency) }}</p>
            <livewire:inventory.availability-badge :product-uuid="$product->uuid" :key="'badge-'.$product->uuid" />
            <div class="my-3">
                <livewire:cart.add-button :product-uuid="$product->uuid" :key="'cart-'.$product->uuid" />
            </div>
            <dl class="meta-list">
                <dt>SKU</dt>
                <dd>{{ $product->sku }}</dd>
            </dl>
            @if ($product->description)
                <h2 class="mt-4">About this product</h2>
                <p class="pre-line measure">{{ $product->description }}</p>
            @endif
        </div>
        <div class="col-12 mt-4">
            <livewire:comment::thread entity-type="product" :entity-uuid="$product->uuid" :key="'comments-'.$product->uuid" />
        </div>
    </div>
</div>
