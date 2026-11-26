<?php

declare(strict_types=1);

namespace App\Modules\Order\Livewire\Forms;

use Livewire\Form;

class CheckoutForm extends Form
{
    public string $name = '';

    public string $phone = '';

    public string $address_line1 = '';

    public string $address_line2 = '';

    public string $city = '';

    public string $postal_code = '';

    public string $country = '';

    public string $note = '';

    /**
     * @return array<string, string>
     */
    public function shipping(): array
    {
        $shipping = [];

        foreach (array_keys((array) config('shop.order.shipping_rules')) as $field) {
            $shipping[(string) $field] = (string) $this->{(string) $field};
        }

        return $shipping;
    }
}
