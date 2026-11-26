<div class="row">
    <div class="col-md-5">
        @if ($product->image_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('shop.uploads.disk'))->url($product->image_path) }}" alt="{{ $product->name }}" class="img-fluid">
        @endif
    </div>
    <div class="col-md-7">
        @if ($product->status !== 'active' || $product->trashed())
            <div class="alert alert-warning">Preview: this product is {{ $product->trashed() ? 'deleted' : $product->status }} and hidden from the public.</div>
        @endif
        <h2>{{ $product->name }}</h2>
        <p class="text-muted">{{ $product->category?->name }}</p>
        <h4>{{ \App\Support\Money::format($product->price_amount, $product->currency) }}</h4>
        <livewire:inventory.availability-badge :product-uuid="$product->uuid" :key="'badge-'.$product->uuid" />
        <livewire:cart.add-button :product-uuid="$product->uuid" :key="'cart-'.$product->uuid" />
        <p>SKU: {{ $product->sku }}</p>
        <p style="white-space: pre-line;">{{ $product->description }}</p>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Back to products</a>
    </div>
    <div class="col-12 mt-4">
        <livewire:comment::thread entity-type="product" :entity-uuid="$product->uuid" :key="'comments-'.$product->uuid" />
    </div>
</div>
