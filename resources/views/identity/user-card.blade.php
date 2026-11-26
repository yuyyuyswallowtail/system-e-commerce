<div class="row">
    <div class="col-md-6">
        <div class="card card-primary card-outline">
            <div class="card-body text-center">
                <h3 class="profile-username">{{ $target->name }}</h3>
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item"><b>Followers</b> <span class="float-right">{{ $counts['followers'] }}</span></li>
                    <li class="list-group-item"><b>Following</b> <span class="float-right">{{ $counts['following'] }}</span></li>
                </ul>
                @error('user') <div class="alert alert-danger">{{ $message }}</div> @enderror
                @unless ($isSelf)
                    @if ($following)
                        <button type="button" wire:click="unfollow" class="btn btn-outline-primary btn-block">Unfollow</button>
                    @else
                        <button type="button" wire:click="follow" class="btn btn-primary btn-block">Follow</button>
                    @endif
                @endunless
            </div>
        </div>
    </div>
</div>
