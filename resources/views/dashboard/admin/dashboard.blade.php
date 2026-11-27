<div>
    <x-page-header title="Dashboard" subtitle="Sales, stock and content at a glance.">
        <x-slot:actions>
            <button type="button" class="btn btn-outline-secondary" wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh">
                <i class="{{ config('ui.icons.refresh') }}" aria-hidden="true"></i> Refresh
            </button>
        </x-slot:actions>
    </x-page-header>

    @if ($warnings !== [])
        <div class="alert alert-warning" role="alert">
            Unavailable data sources: {{ implode(', ', $warnings) }}. The other figures are still current.
        </div>
    @endif

    @if ($unavailable)
        <div class="alert alert-danger" role="alert">Dashboard data is unavailable. Try again in a moment.</div>
    @else
        <div class="row">
            @foreach ($boxes as $box)
                <div class="col-xl-3 col-md-4 col-sm-6">
                    <x-stat-card
                        :label="$box['label']"
                        :value="$box['value']"
                        :note="$box['note'] ?? null"
                        :icon="$box['icon']"
                        :tone="in_array($box['color'], ['warning', 'danger'], true) ? $box['color'] : null" />
                </div>
            @endforeach
        </div>

        @if ($byStatus !== [])
            <div class="card">
                <div class="card-header"><h2 class="card-title">Orders by status</h2></div>
                <ul class="list-group list-group-flush">
                    @foreach ($byStatus as $status => $count)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <x-status-badge group="order_statuses" :status="$status" />
                            <span class="num">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @else
            <x-empty-state title="No orders yet" text="Orders will be counted by status here once customers check out." />
        @endif
    @endif
</div>
