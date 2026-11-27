@php
    $availability = [];

    try {
        $availability = app(\App\Modules\Inventory\Contracts\Inventory::class)->availability($products->pluck('uuid')->all());
    } catch (\Throwable $e) {
        report($e);
    }

    $activeCategory = (string) ($filters['category_id'] ?? '');
@endphp

<div>
    <section class="hero" aria-labelledby="hero-title">
        <h1 class="hero__title" id="hero-title">{{ __('storefront.hero.headline') }}</h1>
        <p class="hero__text">{{ __('storefront.hero.text') }}</p>
        <div class="hero__actions">
            <a href="{{ route('products.index') }}" class="btn btn-primary">{{ __('storefront.hero.primary') }}</a>
            <a href="{{ route('articles.index') }}" class="btn btn-outline-secondary">{{ __('storefront.hero.secondary') }}</a>
        </div>
    </section>

    <ul class="trust-strip" aria-label="{{ __('storefront.trust.label') }}">
        @foreach ((array) __('storefront.trust.items') as $item)
            <li class="trust-strip__item">
                <i class="{{ $item['icon'] }} trust-strip__icon" aria-hidden="true"></i>
                <span>
                    <span class="trust-strip__title">{{ $item['title'] }}</span>
                    <span class="trust-strip__text">{{ $item['text'] }}</span>
                </span>
            </li>
        @endforeach
    </ul>

    <ul class="chips" aria-label="{{ __('storefront.chips.label') }}">
        <li><button type="button" class="chip" wire:click="$set('filters.category_id', '')" aria-pressed="{{ $activeCategory === '' ? 'true' : 'false' }}">{{ __('storefront.chips.all') }}</button></li>
        @foreach ($categories as $category)
            <li wire:key="chip-{{ $category->id }}"><button type="button" class="chip" wire:click="$set('filters.category_id', '{{ $category->id }}')" aria-pressed="{{ $activeCategory === (string) $category->id ? 'true' : 'false' }}">{{ $category->name }}</button></li>
        @endforeach
    </ul>

    <div class="filter-bar" role="search">
        <div class="filter-bar__field filter-bar__field--grow form-group">
            <label for="product-search">{{ __('storefront.ui.search_label') }}</label>
            <input id="product-search" type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="{{ __('storefront.ui.search_placeholder') }}" autocomplete="off">
        </div>
        <div class="form-group">
            <span class="d-block mb-1 font-weight-bold" id="product-sort-label">{{ __('storefront.ui.sort_by') }}</span>
            <div class="segmented" role="group" aria-labelledby="product-sort-label">
                @foreach (['name' => __('storefront.ui.sort_name'), 'price_amount' => __('storefront.ui.sort_price')] as $field => $label)
                    <button type="button" class="segmented__item" wire:click="sortBy('{{ $field }}')" aria-pressed="{{ $sortField === $field ? 'true' : 'false' }}">
                        {{ $label }}
                        @if ($sortField === $field)
                            <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}" aria-hidden="true"></i>
                            <span class="sr-only">{{ $sortDirection === 'asc' ? __('storefront.ui.ascending') : __('storefront.ui.descending') }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <p class="text-muted" wire:loading.delay role="status">{{ __('storefront.ui.updating') }}</p>

    <div class="row">
        @forelse ($products as $product)
            <div class="col-12 col-sm-6 col-lg-4 mb-4" wire:key="product-{{ $product->id }}">
                <x-product-card :product="$product" :available="$availability[$product->uuid] ?? null" />
            </div>
        @empty
            <div class="col-12">
                <x-empty-state :title="__('storefront.ui.products_empty_title')" :text="__('storefront.ui.products_empty_text')" :action-url="route('products.index')" :action-label="__('storefront.ui.products_empty_action')" />
            </div>
        @endforelse
    </div>

    <div>{{ $products->links() }}</div>
</div>
