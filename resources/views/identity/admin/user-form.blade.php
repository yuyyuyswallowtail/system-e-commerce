<div class="row">
    <div class="col-md-6">
        @if ($notice)
            <div class="alert alert-success">{{ $notice }}</div>
        @endif
        @error('user') <div class="alert alert-danger">{{ $message }}</div> @enderror

        <div class="card card-primary card-outline">
            <div class="card-header"><h3 class="card-title">{{ $target ? 'Edit user' : 'New user' }}</h3></div>
            <form wire:submit="save">
                <div class="card-body">
                    <div class="form-group">
                        <label for="user-name">Name</label>
                        <input id="user-name" type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror">
                        @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="user-email">Email</label>
                        <input id="user-email" type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror">
                        @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    @unless ($target)
                        <div class="form-group">
                            <label for="user-password">Password</label>
                            <input id="user-password" type="password" wire:model="password" class="form-control @error('password') is-invalid @enderror">
                            @error('password') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    @endunless
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>

    @if ($target)
        <div class="col-md-6">
            @can('users.status')
                <div class="card card-outline card-warning">
                    <div class="card-header"><h3 class="card-title">Status</h3></div>
                    <form wire:submit="changeStatus">
                        <div class="card-body">
                            <select wire:model="status" class="form-control @error('status') is-invalid @enderror">
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            @error('status') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                        <div class="card-footer"><button type="submit" class="btn btn-warning">Change status</button></div>
                    </form>
                </div>
            @endcan

            @can('users.roles')
                <div class="card card-outline card-info">
                    <div class="card-header"><h3 class="card-title">Roles</h3></div>
                    <form wire:submit="saveRoles">
                        <div class="card-body">
                            @foreach ($roleNames as $roleName)
                                <div class="form-check">
                                    <input id="role-{{ $roleName }}" type="checkbox" wire:model="roles" value="{{ $roleName }}" class="form-check-input">
                                    <label for="role-{{ $roleName }}" class="form-check-label">{{ $roleName }}</label>
                                </div>
                            @endforeach
                            @error('roles') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="card-footer"><button type="submit" class="btn btn-info">Save roles</button></div>
                    </form>
                </div>
            @endcan

            <div class="card card-outline card-danger">
                <div class="card-header"><h3 class="card-title">Danger zone</h3></div>
                <div class="card-body">
                    @if ($target->trashed())
                        @can('users.restore')
                            <button type="button" wire:click="restore" class="btn btn-success">Restore</button>
                        @endcan
                        @can('users.purge')
                            <button type="button" wire:click="purge" wire:confirm="Permanently delete this user?" class="btn btn-danger">Purge</button>
                        @endcan
                    @else
                        @can('users.delete')
                            <button type="button" wire:click="delete" wire:confirm="Delete this user?" class="btn btn-danger">Delete</button>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
