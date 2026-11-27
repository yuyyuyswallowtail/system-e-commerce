<div class="row">
    <div class="col-md-6">
        @if ($notice)
            <div class="alert alert-success">{{ $notice }}</div>
        @endif
        @error('product') <div class="alert alert-danger">{{ $message }}</div> @enderror

        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">{{ $target ? 'Edit product' : 'New product' }}</h3></div>
            <form wire:submit="save">
                <div class="card-body">
                    <div class="form-group">
                        <label for="product-sku">SKU</label>
                        <input id="product-sku" type="text" wire:model="sku" @if ($target) readonly @endif class="form-control @error('sku') is-invalid @enderror">
                        @error('sku') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="product-name">Name</label>
                        <input id="product-name" type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror">
                        @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="product-description">Description</label>
                        <textarea id="product-description" wire:model="description" rows="4" class="form-control @error('description') is-invalid @enderror"></textarea>
                        @error('description') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="product-price">Price (minor units)</label>
                            <input id="product-price" type="text" inputmode="numeric" wire:model="priceAmount" class="form-control @error('price_amount') is-invalid @enderror">
                            @error('price_amount') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group col-md-6">
                            <label for="product-currency">Currency</label>
                            <select id="product-currency" wire:model="currency" class="form-control @error('currency') is-invalid @enderror">
                                @foreach ($currencies as $code)
                                    <option value="{{ $code }}">{{ $code }}</option>
                                @endforeach
                            </select>
                            @error('currency') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="product-category">Category</label>
                        <select id="product-category" wire:model="categoryUuid" class="form-control @error('category_uuid') is-invalid @enderror">
                            <option value="">No category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->uuid }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_uuid') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>

    @if ($target)
        <div class="col-md-6">
            <div class="card card-outline card-warning">
                <div class="card-header"><h3 class="card-title">Status</h3></div>
                <form wire:submit="changeStatus">
                    <div class="card-body">
                        <select wire:model="status" class="form-control @error('status') is-invalid @enderror">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                        @error('status') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="card-footer"><button type="submit" class="btn btn-warning">Change status</button></div>
                </form>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header"><h3 class="card-title">Image</h3></div>
                <form wire:submit="saveImage">
                    <div class="card-body">
                        @if ($target->image_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('shop.uploads.disk'))->url($target->image_path) }}" alt="{{ $target->name }}" class="img-fluid mb-3 img-preview">
                        @endif
                        <input type="file" wire:model="image" accept="image/*" class="form-control-file @error('image') is-invalid @enderror">
                        @error('image') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="card-footer"><button type="submit" class="btn btn-info">Upload image</button></div>
                </form>
            </div>

            <div class="card card-outline card-danger">
                <div class="card-header"><h3 class="card-title">Danger zone</h3></div>
                <div class="card-body">
                    @if ($target->trashed())
                        @can('products.restore')
                            <button type="button" wire:click="restore" class="btn btn-success">Restore</button>
                        @endcan
                    @else
                        @can('products.delete')
                            <button type="button" wire:click="delete" wire:confirm="Delete this product?" class="btn btn-danger">Delete</button>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
