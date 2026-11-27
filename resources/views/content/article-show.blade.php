<div>
    <nav aria-label="Breadcrumb" class="mt-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('articles.index') }}">Articles</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $article->title }}</li>
        </ol>
    </nav>

    <article class="mt-3">
        @if (! $article->isPublished() || $article->trashed())
            <div class="alert alert-warning">Preview: this article is {{ $article->trashed() ? 'deleted' : $article->status }} and hidden from the public.</div>
        @endif
        @if ($article->cover_path)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('shop.uploads.disk'))->url($article->cover_path) }}" alt="{{ $article->title }}" class="img-fluid mb-4">
        @endif
        <h1>{{ $article->title }}</h1>
        <p class="text-muted">{{ $article->author?->name }} @if ($article->published_at) &middot; {{ $article->published_at->format('Y-m-d') }} @endif</p>
        <p class="lead measure">{{ $article->summary }}</p>
        <div class="article-body prose">{{ $article->bodyHtml() }}</div>
    </article>

    <div class="mt-5">
        <livewire:comment::thread entity-type="article" :entity-uuid="$article->uuid" :key="'comments-'.$article->uuid" />
    </div>

    <a href="{{ route('articles.index') }}" class="btn btn-outline-secondary mt-4">Back to articles</a>
</div>
