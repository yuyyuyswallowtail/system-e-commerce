<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Livewire;

use App\Modules\Catalog\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::storefront')]
#[Title('Product')]
class ProductShow extends Component
{
    #[Locked]
    public string $uuid = '';

    public function mount(Product $product): void
    {
        $visible = $product->status === config('shop.product_public_status') && ! $product->trashed();

        if (! $visible) {
            abort_unless(Auth::user()?->can('products.read') === true, 404);
        }

        $this->uuid = (string) $product->uuid;
    }

    public function render(): View
    {
        $product = Product::withTrashed()->with('category')->where('uuid', $this->uuid)->firstOrFail();

        return view('catalog.product-show', ['product' => $product]);
    }
}
