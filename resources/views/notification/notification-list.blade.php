<div>
    <h1 class="h3 mb-3">Notifications</h1>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="unread-only" wire:model.live="unreadOnly">
            <label class="custom-control-label" for="unread-only">Unread only</label>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="markAllRead">Mark all as read</button>
    </div>

    @if ($notifications->isEmpty())
        <div class="alert alert-info">No notifications.</div>
    @else
        <div class="card">
            <ul class="list-group list-group-flush">
                @foreach ($notifications as $note)
                    @php($orderUuid = data_get($note->data, 'data.order_uuid'))
                    <li class="list-group-item d-flex justify-content-between align-items-start" wire:key="note-{{ $note->id }}">
                        <div>
                            <div class="{{ $note->read_at === null ? 'font-weight-bold' : 'text-muted' }}">{{ data_get($note->data, 'title') }}</div>
                            <div>{{ data_get($note->data, 'body') }}</div>
                            <small class="text-muted">{{ $note->created_at?->format('Y-m-d H:i') }}</small>
                            @if (is_string($orderUuid) && $orderUuid !== '')
                                <a href="{{ route('orders.show', ['order' => $orderUuid]) }}" class="ml-2 small">View order</a>
                            @endif
                        </div>
                        <div class="text-nowrap">
                            @if ($note->read_at === null)
                                <button type="button" class="btn btn-link btn-sm" wire:click="markRead('{{ $note->id }}')">Mark as read</button>
                            @endif
                            <button type="button" class="btn btn-link btn-sm text-danger" wire:click="delete('{{ $note->id }}')">Delete</button>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="card-footer">{{ $notifications->links() }}</div>
        </div>
    @endif
</div>
