<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Database\Factories;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => 'SKU-'.strtoupper(fake()->unique()->bothify('????-####')),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price_amount' => fake()->numberBetween(100, 100000),
            'currency' => (string) config('shop.default_currency'),
            'category_id' => null,
            'status' => 'draft',
            'image_path' => null,
        ];
    }
}
