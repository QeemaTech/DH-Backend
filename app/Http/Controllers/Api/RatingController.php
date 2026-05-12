<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderRating;
use App\Models\Product;
use App\Models\ProductRating;
use App\Models\Vendor;
use App\Models\VendorRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    public function rateProduct(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = Auth::user();

        ProductRating::updateOrCreate(
            [
                'product_id' => $product->id,
                'user_id' => $user->id,
            ],
            [
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => __('Product rated successfully.'),
        ]);
    }

    public function rateVendor(Request $request, Vendor $vendor): JsonResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = Auth::user();

        VendorRating::updateOrCreate(
            [
                'vendor_id' => $vendor->id,
                'user_id' => $user->id,
            ],
            [
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => __('Vendor rated successfully.'),
        ]);
    }

    /**
     * Rate a delivered order. Only the order owner can rate and only when order is delivered.
     */
    public function rateOrder(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = Auth::user();

        if ($order->user_id != (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => __('Order not found.'),
            ], 404);
        }

        if ($order->status != 'delivered') {
            return response()->json([
                'success' => false,
                'message' => __('Only completed (delivered) orders can be rated.'),
            ], 422);
        }

        OrderRating::updateOrCreate(
            [
                'order_id' => $order->id,
                'user_id' => $user->id,
            ],
            [
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]
        );

        $order->load('orderRating');

        return response()->json([
            'success' => true,
            'message' => __('Order rated successfully.'),
            'data' => new OrderResource($order),
        ]);
    }
}
