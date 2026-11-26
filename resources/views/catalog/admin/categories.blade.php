<div class="row">
    <div class="col-md-8">
        @if ($notice)
            <div class="alert alert-success">{{ $notice }}</div>
        @endif
        @error('category') <div class="alert alert-danger">{{ $message }}</div> @enderror

        <div class="card">
            <div class="card-header">
                <input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search name or slug">
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th><a href="#" wire:click.prevent="sortBy('name')">Name</a></th>
                            <th>Slug</th>
                            <th>Parent</th>
                            <th><a href="#" wire:click.prevent="sortBy('created_at')">Created</a></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr wire:key="category-{{ $category->id }}">
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->slug }}</td>
                                <td>{{ $category->parent?->name }}</td>
                                <td>{{ $category->created_at?->format('Y-m-d') }}</td>
                                <td class="text-right">
                                    <button type="button" wire:click="edit('{{ $category->uuid }}')" class="btn btn-sm btn-outline-primary">Edit</button>
                                    <button type="button" wire:click="delete('{{ $category->uuid }}')" wire:confirm="Delete this category?" class="btn btn-sm btn-outline-danger">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No categories found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $categories->links() }}</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">{{ $editingUuid ? 'Edit category' : 'New category' }}</h3></div>
            <form wire:submit="save">
                <div class="card-body">
                    <div class="form-group">
                        <label for="category-name">Name</label>
                        <input id="category-name" type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror">
                        @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="category-parent">Parent</label>
                        <select id="category-parent" wire:model="parentUuid" class="form-control @error('parent_uuid') is-invalid @enderror">
                            <option value="">No parent</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->uuid }}">{{ $parent->name }}</option>
                            @endforeach
                        </select>
                        @error('parent_uuid') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                    @if ($editingUuid)
                        <button type="button" wire:click="cancel" class="btn btn-default">Cancel</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
