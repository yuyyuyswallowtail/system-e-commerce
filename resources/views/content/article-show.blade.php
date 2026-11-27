<div>
    <nav aria-label="{{ __('storefront.detail.breadcrumb') }}" class="mt-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('articles.index') }}">{{ __('storefront.articles.title') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $article->title }}</li>
        </ol>
    </nav>

    <article class="mt-3">
        @if (! $article->isPublished() || $article->trashed())
            <div class="alert alert-warning">Preview: this article is {{ $article->trashed() ? 'deleted' : $article->status }} and hidden from the public.</div>
        @endif
        <div class="article-cover-wrap mb-4">
            <x-article-cover :article="$article" />
        </div>
        <h1 class="product-detail__name">{{ $article->title }}</h1>
        <p class="article-card__meta">
            @if ($article->author?->name){{ __('storefront.articles.by', ['name' => $article->author->name]) }}@endif
            @if ($article->published_at) &middot; <time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('j M Y') }}</time>@endif
        </p>
        <p class="lead measure">{{ $article->summary }}</p>
        <div class="article-body prose">{{ $article->bodyHtml() }}</div>
    </article>

    <div class="mt-5">
        <livewire:comment::thread entity-type="article" :entity-uuid="$article->uuid" :key="'comments-'.$article->uuid" />
    </div>

    <a href="{{ route('articles.index') }}" class="btn btn-outline-secondary mt-4">{{ __('storefront.articles.back') }}</a>
</div>
