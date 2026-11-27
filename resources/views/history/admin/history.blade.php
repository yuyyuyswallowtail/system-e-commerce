<div>
    <x-page-header title="History" />

<div>
    <div class="card">
        <div class="card-header">
            <div class="form-row">
                <div class="col-md-3 mb-2">
                    <input aria-label="Search by subject uuid" type="search" class="form-control" placeholder="Search by subject uuid" wire:model.live.debounce.300ms="search">
                </div>
                <div class="col-md-2 mb-2">
                    <select class="form-control" wire:model.live="filters.subject_type" aria-label="Subject type">
                        <option value="">All subjects</option>
                        @foreach ($subjectTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select class="form-control" wire:model.live="filters.action" aria-label="Action">
                        <option value="">All actions</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}">{{ $action }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="text" class="form-control" placeholder="User email" wire:model.live.debounce.300ms="userEmail" aria-label="User email">
                </div>
                <div class="col-md-1 mb-2">
                    <input type="date" class="form-control" wire:model.live="dateFrom" aria-label="From date">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" class="form-control" wire:model.live="dateTo" aria-label="To date">
                </div>
            </div>
        </div>
        <div class="card-body table-responsive table-sticky p-0">
            <table class="table text-nowrap">
                <thead>
                    <tr><th>When</th><th>User</th><th>Actor</th><th>Subject</th><th>Reference</th><th>Action</th><th>Details</th></tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr wire:key="history-{{ $event->id }}">
                            <td>{{ $event->occurred_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $event->user?->email }}</td>
                            <td>{{ $event->actor?->email }}</td>
                            <td><span class="badge badge-secondary">{{ $event->subject_type }}</span></td>
                            <td>{{ $event->subject_uuid }}</td>
                            <td>{{ $event->action }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) json_encode($event->details), 160) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-muted">No history yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $events->links() }}</div>
    </div>
</div>
</div>
