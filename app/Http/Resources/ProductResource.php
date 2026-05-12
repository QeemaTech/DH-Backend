<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'thumb_image' => $this->main_image,
            'description' => $this->description,
            'discount' => $this->discount,
            'discount_type' => $this->discount_type,
            'is_active' => $this->is_active,
            'is_favorite' => $this->when(auth()->check(), function () {
                // Use exists query to avoid N+1 - this is more efficient than loading the relationship
                return $this->favoritedBy()->where('user_id', auth()->id())->exists();
            }, false),
            'is_featured' => $this->is_featured,
            'is_approved' => $this->is_approved,
            'is_bookable' => $this->is_bookable,
            'is_new' => $this->is_new,
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'related_products' => RelatedProductResource::collection($this->getEffectiveRelatedProducts()),
            'price' => $this->manager()->price(),
            'stock' => $this->manager()->stock(),
            'type' => $this->type ?? 'simple',
            'rating' => $this->when($this->relationLoaded('ratings'), function () {
                $visibleRatings = $this->ratings->where('is_visible', true);

                return [
                    'average' => (float) ($visibleRatings->avg('rating') ?? 0),
                    'count' => $visibleRatings->count(),
                ];
            }, function () {
                // Fallback if ratings not loaded - use cached value if available
                return [
                    'average' => (float) ($this->ratings()->where('is_visible', true)->avg('rating') ?? 0),
                    'count' => (int) $this->ratings()->where('is_visible', true)->count(),
                ];
            }),
            'ratings' => ProductRatingResource::collection(
                $this->whenLoaded('ratings', function () {
                    return $this->ratings->where('is_visible', true)->values();
                })
            ),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),

            // Variant options grouped by variant type
            'variant_options' => $this->when($this->type === 'variable', function () {
                return $this->getVariantOptions();
            }),

            // Variants summary
            'variants_summary' => $this->when($this->type === 'variable', function () {
                $variants = $this->relationLoaded('variants') ? $this->variants : $this->variants()->get();

                if ($variants->isEmpty()) {
                    return [
                        'total_variants' => 0,
                        'active_variants' => 0,
                        'total_stock' => 0,
                        'price_range' => [
                            'min' => null,
                            'max' => null,
                        ],
                    ];
                }

                return [
                    'total_variants' => $variants->count(),
                    'active_variants' => $variants->where('is_active', true)->count(),
                    'total_stock' => $variants->sum(function ($variant) {
                        return $variant->branchVariantStocks->sum('quantity');
                    }),
                    'price_range' => [
                        'min' => $variants->min('price'),
                        'max' => $variants->max('price'),
                    ],
                ];
            }),
        ];
    }

    /**
     * Get variant options grouped by variant type
     */
    protected function getVariantOptions(): array
    {
        if (! $this->relationLoaded('variants')) {
            $this->load('variants.values.variantOption.variant');
        }

        $groupedOptions = [];

        foreach ($this->variants as $productVariant) {
            foreach ($productVariant->values as $value) {
                if ($value->variantOption && $value->variantOption->variant) {
                    $variantId = $value->variantOption->variant->id;
                    $variantName = $value->variantOption->variant->name;
                    $optionId = $value->variantOption->id;
                    $optionName = $value->variantOption->name;
                    $optionCode = $value->variantOption->code;

                    // Initialize variant group if not exists
                    if (! isset($groupedOptions[$variantId])) {
                        $groupedOptions[$variantId] = [
                            'variant_id' => $variantId,
                            'variant_name' => $variantName,
                            'options' => [],
                        ];
                    }

                    // Add option if not already added (avoid duplicates)
                    $optionExists = false;
                    foreach ($groupedOptions[$variantId]['options'] as $existingOption) {
                        if ($existingOption['id'] === $optionId) {
                            $optionExists = true;
                            break;
                        }
                    }

                    if (! $optionExists) {
                        $groupedOptions[$variantId]['options'][] = [
                            'id' => $optionId,
                            'name' => $optionName,
                            'code' => $optionCode,
                        ];
                    }
                }
            }
        }

        // Convert to indexed array
        return array_values($groupedOptions);
    }

    /**
     * Get manual related products if defined, otherwise generate automatic related products.
     */
    protected function getEffectiveRelatedProducts()
    {
        if ($this->relationLoaded('relatedProducts') && $this->relatedProducts->isNotEmpty()) {
            return $this->relatedProducts
                ->map->relatedProduct
                ->filter()
                ->take(4)
                ->values();
        }

        return $this->getAutoRelatedProducts();
    }

    /**
     * Generate automatic related products for this product.
     */
    protected function getAutoRelatedProducts()
    {
        // Try to use already loaded categories; fall back to querying if not loaded
        $categoryIds = $this->relationLoaded('categories')
            ? $this->categories->pluck('id')
            : $this->categories()->pluck('categories.id');

        $query = Product::active()
            ->approved()
            ->with(['vendor', 'categories', 'images'])
            ->where('id', '!=', $this->id);

        if ($categoryIds->isNotEmpty()) {
            $query->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        }

        return $query->inRandomOrder()->limit(4)->get();
    }
}
