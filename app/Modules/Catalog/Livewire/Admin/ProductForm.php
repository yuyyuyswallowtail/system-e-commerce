<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Livewire\Admin;

use App\Modules\Catalog\Livewire\Concerns\InteractsWithCatalogActor;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\ProductService;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
#[Title('Product')]
class ProductForm extends Component
{
    use InteractsWithCatalogActor, WithFileUploads;

    #[Locked]
    public ?string $uuid = null;

    public string $sku = '';

    public string $name = '';

    public string $description = '';

    public string $priceAmount = '';

    public string $currency = '';

    public string $categoryUuid = '';

    public string $status = '';

    public $image = null;

    public ?string $notice = null;

    public function mount(mixed $product = null): void
    {
        $this->currency = (string) config('shop.default_currency');
        $this->status = (string) config('shop.product_statuses.0');

        if (is_string($product)) {
            $product = Product::withTrashed()->where('uuid', $product)->firstOrFail();
        }

        if ($product instanceof Product) {
            $this->uuid = (string) $product->uuid;
            $this->sku = (string) $product->sku;
            $this->name = (string) $product->name;
            $this->description = (string) $product->description;
            $this->priceAmount = (string) $product->price_amount;
            $this->currency = (string) $product->currency;
            $this->categoryUuid = (string) ($product->category?->uuid ?? '');
            $this->status = (string) $product->status;
        }
    }

    public function save(ProductService $products): void
    {
        $this->resetErrorBag();
        $this->notice = null;

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'price_amount' => $this->priceAmount,
            'currency' => $this->currency,
            'category_uuid' => $this->categoryUuid !== '' ? $this->categoryUuid : null,
        ];

        try {
            if ($this->uuid === null) {
                $actor = $this->allow('products.create');
                $created = $products->create($actor, ['sku' => $this->sku] + $data);
                $this->redirectRoute('admin.products.edit', ['product' => $created->uuid]);

                return;
            }

            $actor = $this->allow('products.update');
            $products->update($actor, $this->target(), $data);
            $this->notice = 'Product updated.';
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        } catch (ConflictException $exception) {
            $this->addError('product', $exception->getMessage());
        }
    }

    public function changeStatus(ProductService $products): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $actor = $this->allow('products.update');

        try {
            $products->changeStatus($actor, $this->target(), $this->status);
            $this->notice = 'Status changed.';
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        } catch (ConflictException $exception) {
            $this->addError('product', $exception->getMessage());
        }
    }

    public function saveImage(ProductService $products): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $actor = $this->allow('products.update');

        if ($this->image === null) {
            $this->addError('image', 'Choose an image first.');

            return;
        }

        try {
            $products->uploadImage($actor, $this->target(), $this->image);
            $this->image = null;
            $this->notice = 'Image saved.';
        } catch (DomainValidationException $exception) {
            $this->reportDomainErrors($exception);
        } catch (ConflictException $exception) {
            $this->addError('product', $exception->getMessage());
        }
    }

    public function delete(ProductService $products): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $actor = $this->allow('products.delete');

        try {
            $products->delete($actor, $this->target());
            $this->notice = 'Product deleted.';
        } catch (ConflictException $exception) {
            $this->addError('product', $exception->getMessage());
        }
    }

    public function restore(ProductService $products): void
    {
        $this->resetErrorBag();
        $this->notice = null;
        $actor = $this->allow('products.restore');

        try {
            $products->restore($actor, $this->target());
            $this->notice = 'Product restored.';
        } catch (ConflictException $exception) {
            $this->addError('product', $exception->getMessage());
        }
    }

    public function render(): View
    {
        return view('catalog.admin.product-form', [
            'target' => $this->uuid === null ? null : $this->target(),
            'statuses' => config('shop.product_statuses'),
            'currencies' => array_keys((array) config('shop.currencies')),
            'categories' => Category::query()->orderBy('name')->get(['uuid', 'name']),
        ]);
    }

    private function target(): Product
    {
        return Product::withTrashed()->where('uuid', $this->uuid)->firstOrFail();
    }
}
