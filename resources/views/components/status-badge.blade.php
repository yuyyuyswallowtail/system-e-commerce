@props(['group', 'status'])

@php
    $variant = config('ui.badge_variants.'.$group.'.'.$status);
    $variant = in_array($variant, config('ui.variants'), true) ? $variant : 'neutral';
    $label = \Illuminate\Support\Str::of((string) $status)->replace('_', ' ')->lower()->ucfirst();
@endphp

<span {{ $attributes->class(['badge', 'badge-'.$variant]) }}>{{ $label }}</span>
