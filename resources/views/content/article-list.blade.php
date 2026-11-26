<div>
    <div class="row mb-3">
        <div class="col-md-6"><input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search articles"></div>
    </div>

    <div class="row">
        @forelse ($articles as $article)
            <div class="col-md-4 mb-3" wire:key="article-{{ $article->id }}">
                <div class="card h-100">
                    @if ($article->cover_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('shop.uploads.disk'))->url($article->cover_path) }}" alt="{{ $article->title }}" class="card-img-top">
                    @endif
                    <div class="card-body">
                        <h5 class="card-title"><a href="{{ route('articles.show', ['article' => $article->slug]) }}">{{ $article->title }}</a></h5>
                        <p class="card-text text-muted mb-1">{{ $article->published_at?->format('Y-m-d') }}</p>
                        <p class="card-text">{{ $article->summary }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center text-muted">No articles found.</div>
        @endforelse
    </div>

    <div>{{ $articles->links() }}</div>
</div>
