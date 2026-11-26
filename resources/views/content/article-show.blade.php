<div class="row">
    <div class="col-md-8 offset-md-2">
        @if (! $article->isPublished() || $article->trashed())
            <div class="alert alert-warning">Preview: this article is {{ $article->trashed() ? 'deleted' : $article->status }} and hidden from the public.</div>
        @endif
        @if ($article->cover_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('shop.uploads.disk'))->url($article->cover_path) }}" alt="{{ $article->title }}" class="img-fluid mb-3">
        @endif
        <h2>{{ $article->title }}</h2>
        <p class="text-muted">{{ $article->author?->name }} @if ($article->published_at) &middot; {{ $article->published_at->format('Y-m-d') }} @endif</p>
        <p class="lead">{{ $article->summary }}</p>
        <div class="article-body">{{ $article->bodyHtml() }}</div>
        <a href="{{ route('articles.index') }}" class="btn btn-outline-secondary mt-3">Back to articles</a>
    </div>
    <div class="col-md-8 offset-md-2 mt-4">
        <livewire:comment::thread entity-type="article" :entity-uuid="$article->uuid" :key="'comments-'.$article->uuid" />
    </div>
</div>
