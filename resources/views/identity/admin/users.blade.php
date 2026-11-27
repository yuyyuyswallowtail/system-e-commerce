<div>
    <x-page-header title="Users" />

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-3"><input aria-label="Search name or email" type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search name or email"></div>
            <div class="col-md-2">
                <select aria-label="Status" wire:model.live="filters.status" class="form-control">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select aria-label="Role" wire:model.live="role" class="form-control">
                    <option value="">All roles</option>
                    @foreach ($roleNames as $roleName)
                        <option value="{{ $roleName }}">{{ $roleName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select aria-label="Deleted" wire:model.live="deleted" class="form-control">
                    @foreach ($modes as $mode)
                        <option value="{{ $mode }}">Deleted: {{ $mode }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 text-right">
                @can('users.create')
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">New user</a>
                @endcan
            </div>
        </div>
    </div>
    <div class="card-body table-responsive table-sticky p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th><a href="#" wire:click.prevent="sortBy('name')">Name</a></th>
                    <th><a href="#" wire:click.prevent="sortBy('email')">Email</a></th>
                    <th><a href="#" wire:click.prevent="sortBy('status')">Status</a></th>
                    <th>Roles</th>
                    <th><a href="#" wire:click.prevent="sortBy('created_at')">Created</a></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td>{{ $user->name }} @if ($user->trashed()) <span class="badge badge-secondary">deleted</span> @endif</td>
                        <td>{{ $user->email }}</td>
                        <td><x-status-badge group="user_statuses" :status="$user->status" /></td>
                        <td>{{ $user->roles->pluck('name')->implode(', ') }}</td>
                        <td>{{ $user->created_at?->format('Y-m-d') }}</td>
                        <td class="text-right">
                            @can('users.update')
                                <a href="{{ route('admin.users.edit', ['user' => $user->uuid]) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $users->links() }}</div>
</div>
</div>
