<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'sku' => 'SKU-'.fake()->unique()->numerify('########'),
            'short_description' => fake()->sentence(8),
            'description' => fake()->paragraph(),
            'image' => fake()->imageUrl(640, 480, 'product', true),
            'price' => fake()->randomFloat(2, 10, 2000),
            'qty' => fake()->numberBetween(1, 200),
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'is_stock' => true,
        ];
    }
}
