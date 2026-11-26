<?php

declare(strict_types=1);

use App\Modules\Cart\Services\CartService;
use App\Modules\Catalog\Livewire\Admin\CategoryIndex;
use App\Modules\Catalog\Livewire\Admin\ProductForm;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Dashboard\Livewire\Admin\Dashboard;
use App\Modules\Dashboard\Services\DashboardService;
use App\Modules\Inventory\Livewire\Admin\StockShow;
use App\Modules\Order\Livewire\OrderShow;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Livewire\Admin\PaymentIndex;
use App\Modules\Payment\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\Support\IdentityKit;
use Tests\Support\OrderKit;

it('lets an admin manage the catalog, stock, orders and refunds and see the numbers', function () {
    config(['queue.default' => 'sync']);
    Cache::flush();
    $admin = IdentityKit::user('super_admin');

    Livewire::actingAs($admin)->test(CategoryIndex::class)
        ->set('name', 'Journey Category')
        ->call('save')
        ->assertHasNoErrors();
    $category = Category::query()->where('name', 'Journey Category')->firstOrFail();

    Livewire::actingAs($admin)->test(ProductForm::class)
        ->set('sku', 'JRN-0001')
        ->set('name', 'Journey Product')
        ->set('description', 'Made in the admin journey')
        ->set('priceAmount', '3000')
        ->set('currency', 'USD')
        ->set('categoryUuid', $category->uuid)
        ->call('save')
        ->assertHasNoErrors();
    $product = Product::query()->where('sku', 'JRN-0001')->firstOrFail();

    Livewire::actingAs($admin)->test(ProductForm::class, ['product' => $product->uuid])
        ->set('status', 'active')
        ->call('changeStatus')
        ->assertHasNoErrors();
    expect($product->fresh()->status)->toBe('active');

    Livewire::actingAs($admin)->test(StockShow::class, ['productUuid' => $product->uuid])
        ->set('delta', '10')
        ->set('reason', 'restock')
        ->call('adjust')
        ->assertHasNoErrors();
    expect(OrderKit::onHand($product->uuid))->toBe(10);

    $user = IdentityKit::user();
    app(CartService::class)->add($user->id, $product->uuid, 2);
    $order = OrderKit::place($user);
    Livewire::actingAs($user)->test(OrderShow::class, ['order' => $order])->call('pay');

    $this->actingAs($admin)->get('/admin/orders/'.$order->uuid)->assertOk();
    expect(OrderKit::onHand($product->uuid))->toBe(8);

    Cache::flush();
    $numbers = json_encode(app(DashboardService::class)->forBlock('sales'));
    expect($numbers)->toContain('6000');
    Livewire::actingAs($admin)->test(Dashboard::class)->assertOk()->assertDontSee('Unavailable data sources');

    $payment = Payment::query()->firstOrFail();
    Livewire::actingAs($admin)->test(PaymentIndex::class)
        ->set('reasons.'.$payment->uuid, 'Journey refund')
        ->call('refund', $payment->uuid)
        ->assertHasNoErrors();

    expect(Order::query()->firstOrFail()->status)->toBe('cancelled')
        ->and(OrderKit::onHand($product->uuid))->toBe(8)
        ->and(OrderKit::reserved($product->uuid))->toBe(0);

    Cache::flush();
    Livewire::actingAs($admin)->test(Dashboard::class)->assertOk();
});
