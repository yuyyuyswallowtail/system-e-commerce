<div>
    <x-page-header title="Products" subtitle="Browse the range and add what you need to your cart." />

    <div class="filter-bar" role="search">
        <div class="filter-bar__field filter-bar__field--grow form-group">
            <label for="product-search">Search</label>
            <input id="product-search" type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Name or SKU" autocomplete="off">
        </div>
        <div class="filter-bar__field form-group">
            <label for="product-category">Category</label>
            <select id="product-category" wire:model.live="filters.category_id" class="form-control">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <span class="d-block mb-1 font-weight-bold" id="product-sort-label">Sort by</span>
            <div class="segmented" role="group" aria-labelledby="product-sort-label">
                @foreach (['name' => 'Name', 'price_amount' => 'Price'] as $field => $label)
                    <button type="button" class="segmented__item" wire:click="sortBy('{{ $field }}')" aria-pressed="{{ $sortField === $field ? 'true' : 'false' }}">
                        {{ $label }}
                        @if ($sortField === $field)
                            <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                            <span class="sr-only">{{ $sortDirection === 'asc' ? 'ascending' : 'descending' }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <p class="text-muted" wire:loading.delay role="status">Updating results&hellip;</p>

    <div class="row">
        @forelse ($products as $product)
            <div class="col-12 col-sm-6 col-lg-4 mb-4" wire:key="product-{{ $product->id }}">
                <article class="card product-card">
                    <div class="product-card__media">
                        @if ($product->image_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('shop.uploads.disk'))->url($product->image_path) }}" alt="{{ $product->name }}" loading="lazy">
                        @else
                            <i class="{{ config('ui.icons.products') }} fa-2x" aria-hidden="true"></i>
                        @endif
                    </div>
                    <div class="product-card__body">
                        @if ($product->category)
                            <p class="product-card__category">{{ $product->category->name }}</p>
                        @endif
                        <h2 class="product-card__name"><a href="{{ route('products.show', ['product' => $product->uuid]) }}">{{ $product->name }}</a></h2>
                        <p class="product-card__price">{{ \App\Support\Money::format($product->price_amount, $product->currency) }}</p>
                        <livewire:inventory.availability-badge :product-uuid="$product->uuid" :key="'badge-'.$product->uuid" />
                        <div class="product-card__actions">
                            <livewire:cart.add-button :product-uuid="$product->uuid" :key="'cart-'.$product->uuid" />
                        </div>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <x-empty-state title="No products found" text="Try a different search or choose another category." :action-url="route('products.index')" action-label="Show all products" />
            </div>
        @endforelse
    </div>

    <div>{{ $products->links() }}</div>
</div>
