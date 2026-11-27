<div>
    <x-page-header title="Articles" subtitle="Guides and news from the shop." />

    <div class="filter-bar" role="search">
        <div class="filter-bar__field filter-bar__field--grow form-group">
            <label for="article-search">Search articles</label>
            <input id="article-search" type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Title or keyword" autocomplete="off">
        </div>
    </div>

    <div class="row">
        @forelse ($articles as $article)
            <div class="col-12 col-sm-6 col-lg-4 mb-4" wire:key="article-{{ $article->id }}">
                <article class="card product-card">
                    @if ($article->cover_path)
                        <div class="product-card__media">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('shop.uploads.disk'))->url($article->cover_path) }}" alt="{{ $article->title }}" loading="lazy">
                        </div>
                    @endif
                    <div class="product-card__body">
                        @if ($article->published_at)
                            <p class="product-card__category">{{ $article->published_at->format('Y-m-d') }}</p>
                        @endif
                        <h2 class="product-card__name"><a href="{{ route('articles.show', ['article' => $article->slug]) }}">{{ $article->title }}</a></h2>
                        <p class="mb-0">{{ $article->summary }}</p>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                @if ($search !== '')
                    <x-empty-state title="No articles found" text="Try a different search." :action-url="route('articles.index')" action-label="Show all articles" />
                @else
                    <x-empty-state title="No articles yet" text="Check back soon, or look around the shop in the meantime." :action-url="route('products.index')" action-label="Browse products" />
                @endif
            </div>
        @endforelse
    </div>

    <div>{{ $articles->links() }}</div>
</div>
