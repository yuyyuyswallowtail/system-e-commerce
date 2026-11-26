<div>
    <h1 class="h3 mb-3">Your history</h1>

    <div class="form-row mb-3">
        <div class="col-md-3 mb-2">
            <select class="form-control" wire:model.live="filters.subject_type" aria-label="Subject type">
                <option value="">All subjects</option>
                @foreach ($subjectTypes as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 mb-2">
            <select class="form-control" wire:model.live="filters.action" aria-label="Action">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}">{{ $action }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($events->isEmpty())
        <div class="alert alert-info">No history yet.</div>
    @else
        <div class="card">
            <div class="card-body table-responsive p-0">
                <table class="table text-nowrap">
                    <thead>
                        <tr><th>When</th><th>Subject</th><th>Reference</th><th>Action</th><th>Details</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($events as $event)
                            <tr wire:key="history-{{ $event->id }}">
                                <td>{{ $event->occurred_at?->format('Y-m-d H:i') }}</td>
                                <td><span class="badge badge-secondary">{{ $event->subject_type }}</span></td>
                                <td>{{ $event->subject_uuid }}</td>
                                <td>{{ $event->action }}</td>
                                <td>{{ \Illuminate\Support\Str::limit((string) json_encode($event->details), 160) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $events->links() }}</div>
        </div>
    @endif
</div>
