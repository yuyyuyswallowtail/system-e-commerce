<div>
    <x-page-header title="Article details" />

<div class="row">
    <div class="col-md-8">
        @if ($notice)
            <div class="alert alert-success">{{ $notice }}</div>
        @endif
        @error('article') <div class="alert alert-danger">{{ $message }}</div> @enderror

        <div class="card card-primary card-outline">
            <div class="card-header"><h2 class="card-title">{{ $target ? 'Edit article' : 'New article' }}</h2></div>
            <form wire:submit="save">
                <div class="card-body">
                    <div class="form-group">
                        <label for="article-title">Title</label>
                        <input id="article-title" type="text" wire:model="title" class="form-control @error('title') is-invalid @enderror">
                        @error('title') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="article-summary">Summary</label>
                        <textarea id="article-summary" wire:model="summary" rows="2" class="form-control @error('summary') is-invalid @enderror"></textarea>
                        @error('summary') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="article-body">Body (Markdown)</label>
                        <textarea id="article-body" wire:model="body" rows="12" class="form-control @error('body') is-invalid @enderror"></textarea>
                        @error('body') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    @if ($preview)
                        <div class="card card-outline card-secondary">
                            <div class="card-header"><h2 class="card-title">Preview</h2></div>
                            <div class="card-body article-body">{{ $preview }}</div>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <button type="submit" wire:loading.attr="disabled" class="btn btn-primary">Save</button>
                    <button type="button" wire:click="togglePreview" class="btn btn-outline-secondary">{{ $showPreview ? 'Hide preview' : 'Preview' }}</button>
                </div>
            </form>
        </div>
    </div>

    @if ($target)
        <div class="col-md-4">
            <div class="card card-outline card-warning">
                <div class="card-header"><h2 class="card-title">Status: {{ ucfirst($target->status) }}</h2></div>
                <div class="card-body">
                    @can('articles.publish')
                        @if (! $target->trashed())
                            @if ($target->status !== 'published')
                                <button type="button" wire:click="publish" class="btn btn-success">Publish</button>
                            @else
                                <button type="button" wire:click="unpublish" class="btn btn-warning">Unpublish</button>
                                <button type="button" wire:click="archive" wire:confirm="Archive this article? It will no longer be public." class="btn btn-secondary">Archive</button>
                            @endif
                        @endif
                    @endcan
                </div>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header"><h2 class="card-title">Cover</h2></div>
                <form wire:submit="saveCover">
                    <div class="card-body">
                        @if ($target->cover_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('shop.uploads.disk'))->url($target->cover_path) }}" alt="{{ $target->title }}" class="img-fluid mb-3 img-preview">
                        @endif
                        <input aria-label="Cover" type="file" wire:model="cover" accept="image/*" class="form-control-file @error('cover') is-invalid @enderror">
                        @error('cover') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="card-footer"><button type="submit" wire:loading.attr="disabled" class="btn btn-info">Upload cover</button></div>
                </form>
            </div>

            <div class="card card-outline card-danger">
                <div class="card-header"><h2 class="card-title">Danger zone</h2></div>
                <div class="card-body">
                    @if ($target->trashed())
                        @can('articles.restore')
                            <button type="button" wire:click="restore" class="btn btn-success">Restore</button>
                        @endcan
                    @else
                        @can('articles.delete')
                            <button type="button" wire:click="delete" wire:confirm="Delete this article?" class="btn btn-danger">Delete</button>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
</div>
