@props(['product'])

@php
    $categories = (array) config('ui.visuals.categories');
    $slug = $product->relationLoaded('category') ? $product->category?->slug : null;
    $entry = ($slug !== null && isset($categories[$slug])) ? $categories[$slug] : (array) config('ui.visuals.default');
    $imageUrl = $product->image_path ? \Illuminate\Support\Facades\Storage::disk(config('shop.uploads.disk'))->url($product->image_path) : null;
@endphp

@if ($imageUrl)
    <div {{ $attributes->class(['visual']) }}>
        <img src="{{ $imageUrl }}" alt="{{ $product->name }}" loading="lazy">
    </div>
@else
    <div {{ $attributes->class(['visual', 'visual__placeholder', 'visual--'.$entry['tint']]) }} role="img" aria-label="{{ $product->name }}">
        <i class="{{ $entry['icon'] }} visual__icon" aria-hidden="true"></i>
    </div>
@endif
