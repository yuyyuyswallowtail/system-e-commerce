<li class="nav-item"@if ($poll > 0) wire:poll.{{ $poll }}s @endif>
    <a href="{{ route('notifications.index') }}" class="nav-link" aria-label="Notifications">
        <i class="far fa-bell"></i>
        @if ($count > 0)
            <span class="badge badge-warning navbar-badge" data-notification-badge>{{ $count }}</span>
        @endif
    </a>
</li>
