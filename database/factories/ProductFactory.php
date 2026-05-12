<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(2, true);

        return [
            'vendor_id' => Vendor::factory(),
            'type' => 'simple',
            'name' => ['en' => $title, 'ar' => $title],
            'description' => ['en' => '', 'ar' => ''],
            'thumbnail' => null,
            'sku' => fake()->unique()->numerify('SKU-#####'),
            'slug' => fake()->unique()->slug(),
            'price' => fake()->randomFloat(2, 1, 500),
            'discount' => null,
            'discount_type' => 'percentage',
            'is_active' => true,
            'is_featured' => false,
            'is_new' => false,
            'is_approved' => true,
            'is_bookable' => false,
        ];
    }
}
