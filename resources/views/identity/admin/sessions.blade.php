<div>
    @if ($notice)
        <div class="alert alert-success">{{ $notice }}</div>
    @endif
    @error('session') <div class="alert alert-danger">{{ $message }}</div> @enderror

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a href="#" wire:click.prevent="$set('tab', 'sessions')" class="nav-link {{ $online ? '' : 'active' }}">Sessions</a></li>
        @if ($canPresence)
            <li class="nav-item"><a href="#" wire:click.prevent="$set('tab', 'online')" class="nav-link {{ $online ? 'active' : '' }}">Online users</a></li>
        @endif
    </ul>

    <div class="card">
        <div class="card-body table-responsive p-0">
            @if ($online)
                <table class="table table-hover">
                    <thead><tr><th>User</th><th>Email</th><th>Last activity</th></tr></thead>
                    <tbody>
                        @forelse ($onlineUsers as $row)
                            <tr wire:key="online-{{ $row->user_id }}">
                                <td>{{ $row->name }}</td>
                                <td>{{ $row->email }}</td>
                                <td>{{ \Illuminate\Support\Carbon::createFromTimestamp($row->last_activity)->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">Nobody is online.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @else
                <table class="table table-hover">
                    <thead><tr><th>User</th><th>IP</th><th>Agent</th><th>Last activity</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr wire:key="session-{{ $row->id }}">
                                <td>{{ $row->user_name }}<br><small class="text-muted">{{ $row->user_email }}</small></td>
                                <td>{{ $row->ip_address }}</td>
                                <td>{{ \Illuminate\Support\Str::limit((string) $row->user_agent, 50) }}</td>
                                <td>{{ \Illuminate\Support\Carbon::createFromTimestamp($row->last_activity)->diffForHumans() }}</td>
                                <td class="text-right">
                                    @can('sessions.revoke')
                                        <button type="button" wire:click="revoke(@js($row->id))" wire:confirm="Revoke this session?" class="btn btn-sm btn-outline-danger">Revoke</button>
                                        <button type="button" wire:click="revokeUser({{ (int) $row->user_id }})" wire:confirm="Revoke all sessions of this user?" class="btn btn-sm btn-outline-secondary">Revoke all</button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No sessions.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>
        @unless ($online)
            <div class="card-footer">{{ $rows->links() }}</div>
        @endunless
    </div>
</div>
