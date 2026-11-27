<div>
    <x-page-header title="Your history" subtitle="A record of what happened on your orders and account." />

    <div class="filter-bar">
        <div class="filter-bar__field form-group">
            <label for="history-subject">Subject</label>
            <select id="history-subject" class="form-control" wire:model.live="filters.subject_type">
                <option value="">All subjects</option>
                @foreach ($subjectTypes as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-bar__field form-group">
            <label for="history-action">Action</label>
            <select id="history-action" class="form-control" wire:model.live="filters.action">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}">{{ $action }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($events->isEmpty())
        <x-empty-state title="No history yet" text="Activity from your orders and account will show up here." />
    @else
        <div class="card">
            <div class="card-body p-0">
                <table class="table table-stack">
                    <thead>
                        <tr><th>When</th><th>Subject</th><th>Reference</th><th>Action</th><th>Details</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr wire:key="history-{{ $event->id }}">
                                <td data-label="When">{{ $event->occurred_at?->format('Y-m-d H:i') }}</td>
                                <td data-label="Subject"><span class="badge badge-neutral">{{ $event->subject_type }}</span></td>
                                <td data-label="Reference" class="text-break">{{ $event->subject_uuid }}</td>
                                <td data-label="Action">{{ $event->action }}</td>
                                <td data-label="Details" class="text-break">{{ \Illuminate\Support\Str::limit((string) json_encode($event->details), 160) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $events->links() }}</div>
        </div>
    @endif
</div>
