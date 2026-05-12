<?php

namespace App\Services\ProductTypes;

use App\Contracts\ProductPriceStockInterface;
use App\Models\Product;

class VariableProduct implements ProductPriceStockInterface
{
    public function __construct(private Product $product) {}

    public function price()
    {
        return $this->product->variants()->min('price');
    }

    public function stock()
    {
        // Load variants with branch stocks to avoid N+1 queries
        $variants = $this->product->variants()->with('branchVariantStocks')->get();

        if ($variants->isEmpty()) {
            return 0;
        }

        // Calculate stock from branch variant stocks if available, otherwise use variant stock
        $totalStock = 0;

        foreach ($variants as $variant) {
            // Check if this variant has branch stocks
            if ($variant->branchVariantStocks()->exists()) {
                // Use branch variant stock for this variant
                $totalStock += $variant->branchVariantStocks->sum('quantity');
            } else {
                // Fall back to variant stock field if no branch stocks for this variant
                $totalStock += $variant->stock ?? 0;
            }
        }

        return $totalStock;
    }

    public function finalPrice()
    {
        // Get the minimum variant price (base price for variable products)
        $minVariantPrice = $this->price();

        // If no discount, return the minimum variant price
        if (! $this->product->discount || $this->product->discount <= 0) {
            return (float) $minVariantPrice;
        }

        // Apply product-level discount to the minimum variant price
        if ($this->product->discount_type === 'percentage') {
            $discountedPrice = $minVariantPrice - (($minVariantPrice * $this->product->discount) / 100);

            return (float) max(0, $discountedPrice);
        }

        // Fixed discount
        return (float) max(0, $minVariantPrice - $this->product->discount);
    }
}
