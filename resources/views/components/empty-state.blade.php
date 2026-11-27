@props(['title', 'text' => null, 'actionUrl' => null, 'actionLabel' => null])

<div {{ $attributes->class(['empty-state']) }} role="status">
    <i class="{{ config('ui.icons.empty') }} empty-state__icon" aria-hidden="true"></i>
    <p class="empty-state__title">{{ $title }}</p>
    @if ($text)
        <p class="empty-state__text">{{ $text }}</p>
    @endif
    @if ($actionUrl && $actionLabel)
        <a class="btn btn-outline-primary" href="{{ $actionUrl }}">{{ $actionLabel }}</a>
    @endif
</div>
