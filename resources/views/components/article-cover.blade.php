@props(['article'])

@php
    $tint = (string) config('ui.visuals.article.tint');
    $imageUrl = $article->cover_path ? \Illuminate\Support\Facades\Storage::disk(config('shop.uploads.disk'))->url($article->cover_path) : null;
@endphp

@if ($imageUrl)
    <div {{ $attributes->class(['visual']) }}>
        <img src="{{ $imageUrl }}" alt="{{ $article->title }}" loading="lazy">
    </div>
@else
    <div {{ $attributes->class(['visual', 'visual__placeholder', 'visual--'.$tint]) }} role="img" aria-label="{{ $article->title }}">
        <i class="{{ config('ui.icons.articles') }} visual__icon" aria-hidden="true"></i>
    </div>
@endif
