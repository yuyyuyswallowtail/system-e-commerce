<?php

declare(strict_types=1);

namespace App\Modules\Cart\Livewire;

use App\Models\User;
use App\Modules\Cart\Livewire\Concerns\InteractsWithCartActor;
use App\Modules\Cart\Services\CartService;
use App\Support\Exceptions\DomainValidationException;
use App\Support\Exceptions\NotFoundException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AddButton extends Component
{
    use InteractsWithCartActor;

    #[Locked]
    public string $productUuid = '';

    public int $quantity = 1;

    public string $message = '';

    public function mount(string $productUuid): void
    {
        $this->productUuid = $productUuid;
    }

    public function add(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            $this->redirectRoute('login');

            return;
        }

        $this->resetErrorBag();
        $this->message = '';

        try {
            app(CartService::class)->add($user->id, $this->productUuid, $this->quantity);
            $this->message = 'Added to cart.';
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        } catch (NotFoundException) {
            $this->addError('product', 'This product is not available.');
        }
    }

    public function render(): View
    {
        return view('cart.add-button', ['maxQuantity' => (int) config('shop.cart_max_quantity')]);
    }
}
