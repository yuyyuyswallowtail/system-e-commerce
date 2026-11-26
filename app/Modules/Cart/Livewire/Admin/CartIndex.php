<?php

declare(strict_types=1);

namespace App\Modules\Cart\Livewire\Admin;

use App\Models\User;
use App\Modules\Cart\Livewire\Concerns\InteractsWithCartActor;
use App\Modules\Cart\Models\Cart;
use App\Modules\Cart\Models\CartItem;
use App\Support\Livewire\HasTableQuery;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Carts')]
class CartIndex extends Component
{
    use HasTableQuery, InteractsWithCartActor, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    protected function tableKey(): string
    {
        return 'carts';
    }

    public function mount(): void
    {
        $this->allow('carts.read');
    }

    public function sortBy(string $field): void
    {
        $this->allow('carts.read');

        if (! in_array($field, (array) config('tables.carts.sortable'), true)) {
            return;
        }

        $this->sortDirection = ($this->sortField === $field && $this->sortDirection === 'asc') ? 'desc' : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function render(): View
    {
        $this->allow('carts.read');

        // users is the shared kernel: only id, name and email are exposed to the list.
        $query = Cart::query()
            ->joinSub(User::query()->select('id', 'name', 'email'), 'users', 'users.id', '=', 'carts.user_id')
            ->select('carts.*', 'users.name as user_name', 'users.email as user_email')
            ->withCount('items')
            ->with('items');

        $carts = $this->applyTableQuery($query)->paginate($this->resolvedPerPage());

        $totals = [];

        foreach ($carts->items() as $cart) {
            $amount = 0;
            $currency = (string) config('shop.default_currency');

            foreach ($cart->items as $item) {
                /** @var CartItem $item */
                $amount += $item->unit_price_amount * $item->quantity;
                $currency = (string) $item->currency;
            }

            $totals[$cart->id] = ['amount' => $amount, 'currency' => $currency];
        }

        return view('cart.admin.carts', [
            'carts' => $carts,
            'totals' => $totals,
            'statuses' => (array) config('shop.cart_statuses'),
        ]);
    }
}
