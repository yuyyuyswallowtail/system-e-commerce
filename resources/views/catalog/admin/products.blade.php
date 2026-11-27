<div>
    <x-page-header title="Products" />

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-3"><input aria-label="Search name or SKU" type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search name or SKU"></div>
            <div class="col-md-2">
                <select aria-label="Status" wire:model.live="filters.status" class="form-control">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select aria-label="Category" wire:model.live="filters.category_id" class="form-control">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select aria-label="Deleted" wire:model.live="deleted" class="form-control">
                    @foreach ($modes as $mode)
                        <option value="{{ $mode }}">Deleted: {{ $mode }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 text-right">
                @can('products.create')
                    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">New product</a>
                @endcan
            </div>
        </div>
    </div>
    <div class="card-body table-responsive table-sticky p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th><a href="#" wire:click.prevent="sortBy('name')">Name</a></th>
                    <th>SKU</th>
                    <th><a href="#" wire:click.prevent="sortBy('price_amount')">Price</a></th>
                    <th>Status</th>
                    <th>Category</th>
                    <th><a href="#" wire:click.prevent="sortBy('created_at')">Created</a></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr wire:key="product-{{ $product->id }}">
                        <td>{{ $product->name }} @if ($product->trashed()) <span class="badge badge-secondary">deleted</span> @endif</td>
                        <td>{{ $product->sku }}</td>
                        <td class="money">{{ \App\Support\Money::format($product->price_amount, $product->currency) }}</td>
                        <td><x-status-badge group="product_statuses" :status="$product->status" /></td>
                        <td>{{ $product->category?->name }}</td>
                        <td>{{ $product->created_at?->format('Y-m-d') }}</td>
                        <td class="text-right">
                            @can('products.update')
                                <a href="{{ route('admin.products.edit', ['product' => $product->uuid]) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $products->links() }}</div>
</div>
</div>
