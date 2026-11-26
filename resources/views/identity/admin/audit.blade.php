<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-3"><input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search"></div>
            <div class="col-md-2"><input type="text" wire:model.live.debounce.300ms="filters.event" class="form-control" placeholder="Event"></div>
            <div class="col-md-2"><input type="text" wire:model.live.debounce.300ms="filters.log_name" class="form-control" placeholder="Log"></div>
            <div class="col-md-2"><input type="date" wire:model.live="dateFrom" class="form-control" aria-label="From"></div>
            <div class="col-md-2"><input type="date" wire:model.live="dateTo" class="form-control" aria-label="To"></div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead><tr><th>Date</th><th>Causer</th><th>Event</th><th>Subject</th><th>Description</th></tr></thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr wire:key="activity-{{ $row->id }}">
                        <td>{{ $row->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $row->causer?->name ?? 'System' }}</td>
                        <td>{{ $row->event }}</td>
                        <td>{{ $row->subject_type ? class_basename($row->subject_type).' #'.$row->subject_id : '' }}</td>
                        <td>{{ $row->description }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No entries.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $rows->links() }}</div>
</div>
