<div>
    <x-page-header title="Permissions" />

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Roles</h2></div>
            <div class="list-group list-group-flush">
                @foreach ($roles as $role)
                    <button type="button" wire:key="role-{{ $role->id }}" wire:click="selectRole({{ $role->id }})" class="list-group-item list-group-item-action {{ $roleId === $role->id ? 'active' : '' }}">{{ $role->name }}</button>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-md-9">
        @if ($notice)
            <div class="alert alert-success" role="status">{{ $notice }}</div>
        @endif
        @error('permissions') <div class="alert alert-danger" role="alert">{{ $message }}</div> @enderror
        @error('role') <div class="alert alert-danger" role="alert">{{ $message }}</div> @enderror

        @if ($roleId !== null)
            <form wire:submit="save">
                <div class="row">
                    @foreach ($groups as $group => $permissions)
                        <div class="col-md-4">
                            <div class="card card-outline card-secondary">
                                <div class="card-header"><h2 class="card-title">{{ ucfirst($group) }}</h2></div>
                                <div class="card-body">
                                    @foreach ($permissions as $permission)
                                        <div class="form-check" wire:key="perm-{{ $permission->id }}">
                                            <input id="perm-{{ $permission->id }}" type="checkbox" wire:model="assigned" value="{{ $permission->name }}" class="form-check-input">
                                            <label for="perm-{{ $permission->id }}" class="form-check-label">{{ $permission->name }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @can('permissions.manage')
                    <button type="submit" wire:loading.attr="disabled" class="btn btn-primary">Save permissions</button>
                    <span class="text-muted ml-2" wire:dirty wire:target="assigned">You have unsaved changes.</span>
                @endcan
            </form>
        @else
            <p class="text-muted">Select a role to see its permissions.</p>
        @endif
    </div>
</div>
</div>
