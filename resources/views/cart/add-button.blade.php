<div>
    <div class="form-inline">
        <input type="number" min="1" max="{{ $maxQuantity }}" wire:model="quantity" class="form-control form-control-sm mr-2" style="width: 80px;" aria-label="Quantity">
        <button type="button" wire:click="add" class="btn btn-sm btn-primary">Add to cart</button>
    </div>
    @if ($message !== '')
        <small class="text-success d-block mt-1">{{ $message }}</small>
    @endif
    @if ($errors->any())
        <small class="text-danger d-block mt-1">{{ $errors->first() }}</small>
    @endif
</div>
