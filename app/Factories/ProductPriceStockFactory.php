<?php

namespace App\Factories;

use App\Contracts\ProductPriceStockInterface;
use App\Models\Product;
use App\Services\ProductTypes\SimpleProduct;
use App\Services\ProductTypes\VariableProduct;

class ProductPriceStockFactory
{
    public static function make(Product $product): ProductPriceStockInterface
    {
        if ($product->type === 'variable') {
            return new VariableProduct($product);
        }
        if ($product->type === 'simple') {
            return new SimpleProduct($product);
        }
        throw new \InvalidArgumentException('Invalid product type');
    }
}
