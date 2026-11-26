<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-4"><input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search title or summary"></div>
            <div class="col-md-2">
                <select wire:model.live="filters.status" class="form-control">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select wire:model.live="deleted" class="form-control">
                    @foreach ($modes as $mode)
                        <option value="{{ $mode }}">Deleted: {{ $mode }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 text-right">
                @can('articles.create')
                    <a href="{{ route('admin.articles.create') }}" class="btn btn-primary">New article</a>
                @endcan
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th><a href="#" wire:click.prevent="sortBy('title')">Title</a></th>
                    <th><a href="#" wire:click.prevent="sortBy('status')">Status</a></th>
                    <th><a href="#" wire:click.prevent="sortBy('published_at')">Published</a></th>
                    <th><a href="#" wire:click.prevent="sortBy('created_at')">Created</a></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($articles as $article)
                    <tr wire:key="article-{{ $article->id }}">
                        <td>{{ $article->title }} @if ($article->trashed()) <span class="badge badge-secondary">deleted</span> @endif</td>
                        <td><span class="badge badge-{{ $article->status === 'published' ? 'success' : 'secondary' }}">{{ $article->status }}</span></td>
                        <td>{{ $article->published_at?->format('Y-m-d') }}</td>
                        <td>{{ $article->created_at?->format('Y-m-d') }}</td>
                        <td class="text-right">
                            @can('articles.update')
                                <a href="{{ route('admin.articles.edit', ['article' => $article->uuid]) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No articles found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $articles->links() }}</div>
</div>
