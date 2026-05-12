<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchProductVariantStock extends Model
{
    protected $fillable = [
        'branch_id',
        'product_variant_id',
        'quantity',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Scope a query to only include in-stock items
     */
    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope a query to only include out-of-stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    /**
     * Scope a query by branch
     */
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope a query by product variant
     */
    public function scopeByVariant($query, $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }

    /**
     * Check if stock is available
     */
    public function isInStock(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Check if stock is low (less than threshold)
     */
    public function isLowStock(int $threshold = 10): bool
    {
        return $this->quantity > 0 && $this->quantity <= $threshold;
    }
}
