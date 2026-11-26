<div>
    <div class="row mb-3">
        <div class="col-md-5"><input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search products"></div>
        <div class="col-md-4">
            <select wire:model.live="filters.category_id" class="form-control">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 text-md-right mt-2 mt-md-0">
            <button type="button" wire:click="sortBy('name')" class="btn btn-sm btn-outline-secondary">Sort by name</button>
            <button type="button" wire:click="sortBy('price_amount')" class="btn btn-sm btn-outline-secondary">Sort by price</button>
        </div>
    </div>

    <div class="row">
        @forelse ($products as $product)
            <div class="col-md-4 mb-3" wire:key="product-{{ $product->id }}">
                <div class="card h-100">
                    @if ($product->image_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('shop.uploads.disk'))->url($product->image_path) }}" alt="{{ $product->name }}" class="card-img-top">
                    @endif
                    <div class="card-body">
                        <h5 class="card-title"><a href="{{ route('products.show', ['product' => $product->uuid]) }}">{{ $product->name }}</a></h5>
                        <p class="card-text text-muted mb-1">{{ $product->category?->name }}</p>
                        <p class="card-text font-weight-bold">{{ \App\Support\Money::format($product->price_amount, $product->currency) }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center text-muted">No products found.</div>
        @endforelse
    </div>

    <div>{{ $products->links() }}</div>
</div>
