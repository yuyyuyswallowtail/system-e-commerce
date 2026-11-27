<div>
    <x-page-header title="Notifications" subtitle="Updates about your orders and payments." />

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="unread-only" wire:model.live="unreadOnly">
            <label class="custom-control-label" for="unread-only">Unread only</label>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="markAllRead" wire:loading.attr="disabled" wire:target="markAllRead">Mark all as read</button>
    </div>

    @if ($notifications->isEmpty())
        @if ($unreadOnly)
            <x-empty-state title="No unread notifications" text="Turn off the filter to see earlier ones." />
        @else
            <x-empty-state title="No notifications" text="You are all caught up. New order and payment updates will appear here." />
        @endif
    @else
        <div class="card">
            <ul class="list-group list-group-flush">
                @foreach ($notifications as $note)
                    @php($orderUuid = data_get($note->data, 'data.order_uuid'))
                    <li class="list-group-item d-flex justify-content-between align-items-start flex-wrap" wire:key="note-{{ $note->id }}">
                        <div class="measure">
                            <div class="{{ $note->read_at === null ? 'font-weight-bold' : 'text-muted' }}">
                                @if ($note->read_at === null)<span class="sr-only">Unread: </span>@endif{{ data_get($note->data, 'title') }}
                            </div>
                            <div>{{ data_get($note->data, 'body') }}</div>
                            <small class="text-muted">{{ $note->created_at?->format('Y-m-d H:i') }}</small>
                            @if (is_string($orderUuid) && $orderUuid !== '')
                                <a href="{{ route('orders.show', ['order' => $orderUuid]) }}" class="ml-2 small">View order</a>
                            @endif
                        </div>
                        <div class="mt-2 mt-md-0">
                            @if ($note->read_at === null)
                                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="markRead('{{ $note->id }}')">Mark as read</button>
                            @endif
                            <button type="button" class="btn btn-outline-danger btn-sm" wire:click="delete('{{ $note->id }}')" wire:confirm="Delete this notification? This cannot be undone.">Delete</button>
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="card-footer">{{ $notifications->links() }}</div>
        </div>
    @endif
</div>
