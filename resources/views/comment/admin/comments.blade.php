<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-4"><input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search body"></div>
            <div class="col-md-2">
                <select wire:model.live="filters.status" class="form-control">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select wire:model.live="filters.entity_type" class="form-control">
                    <option value="">All entities</option>
                    @foreach ($entityTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select wire:model.live="deleted" class="form-control">
                    @foreach ($modes as $mode)
                        <option value="{{ $mode }}">Deleted: {{ $mode }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @error('action')
            <div class="alert alert-danger mt-2 mb-0">{{ $message }}</div>
        @enderror
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Comment</th>
                    <th>Author</th>
                    <th><a href="#" wire:click.prevent="sortBy('entity_type')">Entity</a></th>
                    <th><a href="#" wire:click.prevent="sortBy('status')">Status</a></th>
                    <th><a href="#" wire:click.prevent="sortBy('created_at')">Created</a></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($comments as $comment)
                    <tr wire:key="comment-{{ $comment->id }}">
                        <td>@foreach (preg_split('/\R/', \Illuminate\Support\Str::limit((string) $comment->body, 200)) as $line){{ $line }}@if (! $loop->last)<br />@endif@endforeach @if ($comment->trashed()) <span class="badge badge-secondary">deleted</span> @endif</td>
                        <td>{{ $authors[$comment->user_id] ?? 'User #'.$comment->user_id }}</td>
                        <td>{{ $comment->entity_type }}</td>
                        <td><span class="badge badge-{{ $comment->status === 'visible' ? 'success' : 'warning' }}">{{ $comment->status }}</span></td>
                        <td>{{ $comment->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-right text-nowrap">
                            @if (! $comment->trashed())
                                @if ($comment->status === 'visible')
                                    <button type="button" wire:click="hide('{{ $comment->uuid }}')" class="btn btn-sm btn-outline-warning">Hide</button>
                                @else
                                    <button type="button" wire:click="show('{{ $comment->uuid }}')" class="btn btn-sm btn-outline-success">Show</button>
                                @endif
                                <button type="button" wire:click="remove('{{ $comment->uuid }}')" wire:confirm="Delete this comment?" class="btn btn-sm btn-outline-danger">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No comments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $comments->links() }}</div>
</div>
