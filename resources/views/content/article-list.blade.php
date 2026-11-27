@php
    $showFeatured = $search === '' && $articles->currentPage() === 1 && $articles->count() > 0;
    $featured = $showFeatured ? $articles->first() : null;
    $rest = $showFeatured ? $articles->slice(1) : $articles;
@endphp

<div>
    <x-page-header :title="__('storefront.articles.title')" :subtitle="__('storefront.articles.subtitle')" />

    <div class="filter-bar" role="search">
        <div class="filter-bar__field filter-bar__field--grow form-group">
            <label for="article-search">{{ __('storefront.ui.article_search_label') }}</label>
            <input id="article-search" type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="{{ __('storefront.ui.article_search_placeholder') }}" autocomplete="off">
        </div>
    </div>

    @if ($featured)
        <article class="card featured-card" wire:key="featured-{{ $featured->id }}">
            <x-article-cover :article="$featured" />
            <div class="featured-card__body">
                <p class="article-card__meta">{{ __('storefront.articles.featured') }}@if ($featured->published_at) &middot; <time datetime="{{ $featured->published_at->toDateString() }}">{{ $featured->published_at->format('j M Y') }}</time>@endif</p>
                <h2 class="featured-card__title"><a href="{{ route('articles.show', ['article' => $featured->slug]) }}">{{ $featured->title }}</a></h2>
                <p class="mb-0">{{ $featured->summary }}</p>
                <a class="article-card__link" href="{{ route('articles.show', ['article' => $featured->slug]) }}">{{ __('storefront.articles.read') }}<span class="sr-only">: {{ $featured->title }}</span></a>
            </div>
        </article>
    @endif

    <div class="row">
        @forelse ($rest as $article)
            <div class="col-12 col-sm-6 col-lg-4 mb-4" wire:key="article-{{ $article->id }}">
                <article class="card product-card article-card">
                    <x-article-cover :article="$article" />
                    <div class="product-card__body">
                        @if ($article->published_at)
                            <p class="article-card__meta"><time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->format('j M Y') }}</time></p>
                        @endif
                        <h2 class="product-card__name"><a href="{{ route('articles.show', ['article' => $article->slug]) }}">{{ $article->title }}</a></h2>
                        <p class="mb-0">{{ $article->summary }}</p>
                    </div>
                </article>
            </div>
        @empty
            @if (! $featured)
                <div class="col-12">
                    @if ($search !== '')
                        <x-empty-state :title="__('storefront.ui.articles_no_match_title')" :text="__('storefront.ui.articles_no_match_text')" :action-url="route('articles.index')" :action-label="__('storefront.ui.articles_no_match_action')" />
                    @else
                        <x-empty-state :title="__('storefront.ui.articles_none_title')" :text="__('storefront.ui.articles_none_text')" :action-url="route('products.index')" :action-label="__('storefront.ui.articles_none_action')" />
                    @endif
                </div>
            @endif
        @endforelse
    </div>

    <div>{{ $articles->links() }}</div>
</div>
