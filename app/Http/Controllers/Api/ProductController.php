<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductService $service;

    public function __construct(ProductService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $countryId = $request->get('country_id');
        if (($countryId === null || $countryId === '') && $request->user()) {
            $countryId = $request->user()->country_id;
        }
        $filters = [
            'search' => $request->get('search', ''),
            'featured' => $request->get('featured', ''),
            'vendor_id' => $request->get('vendor_id', ''),
            'category_id' => $request->get('category_id', ''),
            'min_price' => $request->get('min_price', ''),
            'max_price' => $request->get('max_price', ''),
            'stock' => $request->get('stock', ''),
            'sort' => $request->get('sort', ''),
            'approved' => 1,
            'status' => 'active',
            'country_id' => $countryId,
        ];
        $products = $this->service->getPaginatedProducts($perPage, $filters);

        return ProductResource::collection($products);
    }

    public function show(Request $request, $id)
    {
        $product = $this->service->getProductById((int) $id);
        if (! $product) {
            abort(404);
        }

        $countryId = $request->get('country_id');
        if (($countryId === null || $countryId === '') && $request->user()) {
            $countryId = $request->user()->country_id;
        }
        if (! $product->isVisibleInCountry($countryId !== null && $countryId !== '' ? (int) $countryId : null)) {
            abort(404);
        }

        return new ProductResource($product);
    }

    public function toggleFavorite(Product $product)
    {
        $productId = $product->id;
        $user = auth()->user();

        if ($user->favoriteProducts()->where('product_id', $productId)->exists()) {
            $user->favoriteProducts()->detach($productId);

            return response()->json(['message' => 'Removed from favorites']);
        } else {
            $user->favoriteProducts()->attach($productId);

            return response()->json(['message' => 'Added to favorites']);
        }
    }

    public function favoriteList()
    {
        $user = auth()->user();
        $query = $user->favoriteProducts();
        if ($user->country_id) {
            $countryId = (int) $user->country_id;
            $query->where(function ($q) use ($countryId) {
                $q->whereDoesntHave('countries')
                    ->orWhereHas('countries', function ($sub) use ($countryId) {
                        $sub->where('countries.id', $countryId);
                    });
            });
        }
        $favorites = $query->with('images')->get();

        return ProductResource::collection($favorites);
    }

    // public function search(Request $request)
    // {
    //     // search for products by name or sku
    //     $query = $request->input('query');
    //     $products = $this->service->searchApi($query);
    //     return ProductResource::collection($products);
    // }
}
