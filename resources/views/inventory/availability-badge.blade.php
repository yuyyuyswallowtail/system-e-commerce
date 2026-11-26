<div>
    @if ($state === 'in')
        <span class="badge badge-success">In stock</span>
    @elseif ($state === 'low')
        <span class="badge badge-warning">Only {{ $available }} left</span>
    @elseif ($state === 'out')
        <span class="badge badge-secondary">Out of stock</span>
    @endif
</div>
