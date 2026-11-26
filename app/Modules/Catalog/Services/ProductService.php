<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Models\User;
use App\Modules\Catalog\Events\ProductChanged;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\Concerns\ValidatesInput;
use App\Support\Exceptions\ConflictException;
use App\Support\Exceptions\DomainValidationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

final class ProductService
{
    use ValidatesInput;

    /**
     * @param  array<string, mixed>  $data  sku, name, description, price_amount, currency, status, category_uuid
     */
    public function create(User $actor, array $data): Product
    {
        $input = Arr::only($data, ['sku', 'name', 'description', 'price_amount', 'currency', 'status', 'category_uuid']);
        $input['currency'] ??= (string) config('shop.default_currency');
        $input['status'] ??= (string) config('shop.product_default_status');

        $valid = $this->validate($input, [
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price_amount' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', Rule::in(array_keys((array) config('shop.currencies')))],
            'status' => ['required', Rule::in((array) config('shop.product_statuses'))],
            'category_uuid' => ['nullable', 'uuid', Rule::exists('product_categories', 'uuid')->whereNull('deleted_at')],
        ]);

        $product = DB::transaction(function () use ($actor, $valid): Product {
            return Product::create([
                'sku' => $valid['sku'],
                'name' => $valid['name'],
                'description' => $valid['description'] ?? null,
                'price_amount' => $valid['price_amount'],
                'currency' => $valid['currency'],
                'status' => $valid['status'],
                'category_id' => $this->categoryId($valid['category_uuid'] ?? null),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        });

        ProductChanged::dispatch($product, 'created', $actor->id);

        return $product;
    }

    /**
     * The sku, slug and status are never changed here.
     *
     * @param  array<string, mixed>  $data  name, description, price_amount, currency, category_uuid
     */
    public function update(User $actor, Product $product, array $data): Product
    {
        $this->assertLive($product);

        $valid = $this->validate(Arr::only($data, ['name', 'description', 'price_amount', 'currency', 'category_uuid']), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price_amount' => ['required', 'integer', 'min:0'],
            'currency' => ['sometimes', 'required', 'string', Rule::in(array_keys((array) config('shop.currencies')))],
            'category_uuid' => ['sometimes', 'nullable', 'uuid', Rule::exists('product_categories', 'uuid')->whereNull('deleted_at')],
        ]);

        $attributes = Arr::only($valid, ['name', 'description', 'price_amount', 'currency']);

        if (array_key_exists('category_uuid', $valid)) {
            $attributes['category_id'] = $this->categoryId($valid['category_uuid']);
        }

        $attributes['updated_by'] = $actor->id;

        DB::transaction(function () use ($product, $attributes): void {
            $product->fill($attributes)->save();
        });

        $changed = array_values(array_diff(array_keys($product->getChanges()), ['updated_at', 'updated_by']));

        ProductChanged::dispatch($product, 'updated', $actor->id, ['changed' => $changed]);

        return $product;
    }

    public function changeStatus(User $actor, Product $product, string $status): Product
    {
        $this->assertLive($product);

        $this->validate(['status' => $status], ['status' => ['required', Rule::in((array) config('shop.product_statuses'))]]);

        $from = $product->status;

        DB::transaction(function () use ($actor, $product, $status): void {
            $product->fill(['status' => $status, 'updated_by' => $actor->id])->save();
        });

        ProductChanged::dispatch($product, 'updated', $actor->id, ['from' => $from, 'to' => $status]);

        return $product;
    }

    public function delete(User $actor, Product $product): void
    {
        $this->assertLive($product);

        DB::transaction(function () use ($product): void {
            $product->delete();
        });

        ProductChanged::dispatch($product, 'deleted', $actor->id);
    }

    public function restore(User $actor, Product $product): Product
    {
        if (! $product->trashed()) {
            throw new ConflictException('The product is not deleted.');
        }

        DB::transaction(function () use ($product): void {
            $product->restore();
        });

        ProductChanged::dispatch($product, 'restored', $actor->id);

        return $product;
    }

    public function uploadImage(User $actor, Product $product, UploadedFile $file): Product
    {
        $this->assertLive($product);

        $mimes = array_map('strtolower', (array) config('shop.uploads.mimes'));

        $this->validate(['image' => $file], [
            'image' => ['required', 'file', 'mimes:'.implode(',', $mimes), 'max:'.(int) config('shop.uploads.max_kb')],
        ]);

        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, $mimes, true)) {
            throw new DomainValidationException(['image' => 'The image type is not allowed.']);
        }

        $disk = Storage::disk((string) config('shop.uploads.disk'));
        $path = $disk->putFileAs((string) config('shop.uploads.directory'), $file, Str::random(40).'.'.$extension);

        if (! is_string($path)) {
            throw new ConflictException('The image could not be stored.');
        }

        $old = $product->image_path;

        try {
            DB::transaction(function () use ($actor, $product, $path): void {
                $product->fill(['image_path' => $path, 'updated_by' => $actor->id])->save();
            });
        } catch (Throwable $e) {
            $disk->delete($path);

            throw $e;
        }

        if ($old !== null && $old !== $path) {
            $disk->delete($old);
        }

        ProductChanged::dispatch($product, 'updated', $actor->id, ['changed' => ['image_path']]);

        return $product;
    }

    private function assertLive(Product $product): void
    {
        if ($product->trashed()) {
            throw new ConflictException('The product is deleted.');
        }
    }

    private function categoryId(?string $uuid): ?int
    {
        if ($uuid === null) {
            return null;
        }

        $id = Category::query()->where('uuid', $uuid)->value('id');

        return $id === null ? null : (int) $id;
    }
}
