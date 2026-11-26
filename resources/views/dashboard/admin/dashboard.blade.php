<div>
    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-outline-secondary" wire:click="refresh">
            <i class="fas fa-sync"></i> Refresh
        </button>
    </div>

    @if ($warnings !== [])
        <div class="alert alert-warning">
            Unavailable data sources: {{ implode(', ', $warnings) }}
        </div>
    @endif

    @if ($unavailable)
        <div class="alert alert-danger">Dashboard data is unavailable. Try again later.</div>
    @else
        <div class="row">
            @foreach ($boxes as $box)
                <div class="col-md-3 col-sm-6">
                    <div class="small-box bg-{{ $box['color'] }}">
                        <div class="inner">
                            <h3>{{ $box['value'] }}</h3>
                            <p>{{ $box['label'] }}@isset($box['note']) <small>({{ $box['note'] }})</small>@endisset</p>
                        </div>
                        <div class="icon"><i class="{{ $box['icon'] }}"></i></div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($byStatus !== [])
            <div class="card">
                <div class="card-header"><h3 class="card-title">Orders by status</h3></div>
                <ul class="list-group list-group-flush">
                    @foreach ($byStatus as $status => $count)
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ $status }}</span>
                            <span class="badge badge-primary">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</div>
