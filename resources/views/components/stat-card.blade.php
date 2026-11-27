@props(['label', 'value', 'delta' => null, 'note' => null, 'tone' => null, 'icon' => null])

@php
    $toneClass = ($tone !== null && $tone !== 'neutral' && in_array($tone, config('ui.stat_tones'), true))
        ? 'stat-card--'.$tone
        : null;
@endphp

<div {{ $attributes->class(['stat-card', $toneClass]) }}>
    <p class="stat-card__label">
        @if ($icon)
            <i class="{{ $icon }}" aria-hidden="true"></i>
        @endif
        <span>{{ $label }}</span>
    </p>
    <p class="stat-card__value">{{ $value }}</p>
    @if ($delta !== null && $delta !== '')
        <p class="stat-card__delta">{{ $delta }}</p>
    @endif
    @if ($note !== null && $note !== '')
        <p class="stat-card__note">{{ $note }}</p>
    @endif
</div>
