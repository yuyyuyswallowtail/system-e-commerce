<div>
    <x-page-header title="Your account" subtitle="Update your name and password." />
    <div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Profile</h2></div>
            <form wire:submit="saveProfile">
                <div class="card-body">
                    @if ($profileStatus)
                        <div class="alert alert-success">{{ $profileStatus }}</div>
                    @endif
                    <div class="form-group">
                        <label for="profile-name">Name</label>
                        <input id="profile-name" type="text" autocomplete="name" wire:model="name" class="form-control @error('name') is-invalid @enderror">
                        @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="saveProfile">Save changes</button></div>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Change password</h2></div>
            <form wire:submit="changePassword">
                <div class="card-body">
                    @if ($passwordStatus)
                        <div class="alert alert-success">{{ $passwordStatus }}</div>
                    @endif
                    <div class="form-group">
                        <label for="current-password">Current password</label>
                        <input id="current-password" type="password" autocomplete="current-password" wire:model="current_password" class="form-control @error('current_password') is-invalid @enderror">
                        @error('current_password') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="new-password">New password</label>
                        <input id="new-password" type="password" autocomplete="new-password" wire:model="password" class="form-control @error('password') is-invalid @enderror">
                        @error('password') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirm new password</label>
                        <input id="confirm-password" type="password" autocomplete="new-password" wire:model="password_confirmation" class="form-control">
                    </div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="changePassword">Change password</button></div>
            </form>
        </div>
    </div>
</div>
</div>
