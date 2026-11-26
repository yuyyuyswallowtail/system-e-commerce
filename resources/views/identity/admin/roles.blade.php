<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-4"><input type="search" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Search roles"></div>
            @can('roles.manage')
                <div class="col-md-8">
                    <form wire:submit="createRole" class="form-inline justify-content-end">
                        <input type="text" wire:model="newName" class="form-control mr-2 @error('name') is-invalid @enderror" placeholder="New role name">
                        <button type="submit" class="btn btn-primary">Create</button>
                    </form>
                </div>
            @endcan
        </div>
        @error('name') <div class="text-danger mt-2">{{ $message }}</div> @enderror
        @error('role') <div class="text-danger mt-2">{{ $message }}</div> @enderror
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead><tr><th>Name</th><th>Permissions</th><th></th></tr></thead>
            <tbody>
                @foreach ($rows as $role)
                    @php($system = in_array($role->name, $systemRoles, true))
                    <tr wire:key="role-{{ $role->id }}">
                        <td>
                            @if ($editId === $role->id)
                                <form wire:submit="updateRole" class="form-inline">
                                    <input type="text" wire:model="editName" class="form-control mr-2">
                                    <button type="submit" class="btn btn-sm btn-primary mr-1">Save</button>
                                    <button type="button" wire:click="cancelEdit" class="btn btn-sm btn-secondary">Cancel</button>
                                </form>
                            @else
                                {{ $role->name }} @if ($system) <span class="badge badge-info">system</span> @endif
                            @endif
                        </td>
                        <td>{{ $role->permissions_count }}</td>
                        <td class="text-right">
                            @can('roles.manage')
                                @unless ($system)
                                    <button type="button" wire:click="startEdit({{ $role->id }})" class="btn btn-sm btn-outline-primary">Rename</button>
                                    <button type="button" wire:click="deleteRole({{ $role->id }})" wire:confirm="Delete this role?" class="btn btn-sm btn-outline-danger">Delete</button>
                                @endunless
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $rows->links() }}</div>
</div>
