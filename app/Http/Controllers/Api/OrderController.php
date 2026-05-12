<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(protected OrderService $service) {}

    /**
     * Display a listing of the authenticated user's orders.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $perPage = (int) $request->get('per_page', 15);
        $filters = [
            'search' => (string) $request->get('search', ''),
            'status' => (string) $request->get('status', ''),
            'payment_status' => (string) $request->get('payment_status', ''),
            'payment_method' => (string) $request->get('payment_method', ''),
            'refund_status' => (string) $request->get('refund_status', ''),
            'vendor_id' => $request->get('vendor_id', ''),
            'branch_id' => $request->get('branch_id', ''),
            'from_date' => (string) $request->get('from_date', ''),
            'to_date' => (string) $request->get('to_date', ''),
            'min_total' => $request->get('min_total', ''),
            'max_total' => $request->get('max_total', ''),
            'sort' => (string) $request->get('sort', ''),
        ];

        $orders = $this->service->getPaginatedOrdersForUser($user->id, $perPage, $filters);

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Display the specified order for the authenticated user.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        $order = $this->service->getOrderByIdForUser($id, $user->id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => __('Order not found.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Calculate shipping costs for the authenticated user's cart.
     *
     * This endpoint calculates shipping costs for all items in the cart without creating an order.
     * It returns detailed shipping information per vendor and total shipping cost.
     */
    public function calculateShipping(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
        ]);

        try {
            $shippingData = $this->service->calculateShippingCost($user->id, $validated['address_id']);

            return response()->json([
                'success' => true,
                'data' => $shippingData,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Create a new order for the authenticated user.
     *
     * This will create the order from the user's cart using the internal order cycle.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            // Inputs that fit the new server-side order cycle
            // Note: order_discount is now calculated automatically from product discounts
            'coupon_code' => ['nullable', 'string', 'exists:coupons,code'],
            // Boolean flags: if true, service will automatically calculate and use all available wallet/points
            'use_wallet' => ['nullable', 'boolean'],
            'use_points' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
        ]);
        if (! empty($validated['coupon_code'])) {
            $coupon = Coupon::where('code', '=', $validated['coupon_code'], 'and')->first();

            if (! $coupon) {
                return response()->json([
                    'success' => false,
                    'message' => __('Coupon not found.'),
                ], 404);
            }

            $validated['coupon_id'] = $coupon->id;
        }

        // Default status to pending if not provided
        if (! isset($validated['status']) || $validated['status'] == '') {
            $validated['status'] = 'pending';
        }

        $order = $this->service->createOrder($user->id, $validated);

        return response()->json([
            'success' => true,
            'message' => __('Order created successfully.'),
            'data' => new OrderResource($order),
        ], 201);
    }

    /**
     * Cancel an order for the authenticated user (if possible).
     */
    public function cancel(int $id): JsonResponse
    {
        $user = Auth::user();

        $order = $this->service->cancelOrderForUser($id, $user->id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => __('Order not found.'),
            ], 404);
        }

        if ($order->status != 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => __('Order cannot be cancelled at this stage.'),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => __('Order cancelled successfully.'),
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Reorder items from a previous order by adding them to the user's cart.
     */
    public function reorder(int $id): JsonResponse
    {
        $user = Auth::user();

        try {
            $result = $this->service->reorder($id, $user->id);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'added' => $result['added'],
                    'skipped' => $result['skipped'],
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Mark an order as paid immediately (used after successful payment).
     */
    public function pay(int $id, Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'max:50'],
        ]);

        $paymentMethod = (string) $validated['payment_method'];

        // Sadad: return payment link instead of marking paid
        if (strtolower($paymentMethod) == 'sadad') {
            $order = $this->service->getOrderByIdForUser($id, $user->id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => __('Order not found.'),
                ], 404);
            }

            // Reuse an existing pending invoice for this order if present
            $existing = Invoice::query()
                ->with('items')
                ->where('provider', Invoice::PROVIDER_SADAD)
                ->where('status', Invoice::STATUS_PENDING)
                ->whereJsonContains('provider_payload->order_id', $order->id)
                ->latest('id')
                ->first();

            if ($existing && $existing->payment_url) {
                return response()->json([
                    'success' => true,
                    'invoice_id' => $existing->id,
                    'payment_url' => $existing->payment_url,
                    'status' => $existing->status,
                ]);
            }

            /** @var PaymentService $paymentService */
            $paymentService = app(PaymentService::class);

            if ($existing) {
                $paymentUrl = $paymentService->generatePaymentLink($existing);

                return response()->json([
                    'success' => true,
                    'invoice_id' => $existing->id,
                    'payment_url' => $paymentUrl,
                    'status' => $existing->status,
                ]);
            }

            $invoice = DB::transaction(function () use ($order, $user) {
                $invoice = Invoice::create([
                    'user_id' => $user->id,
                    'customer_name' => $user->name,
                    'customer_phone' => $user->phone ?? null,
                    'customer_email' => $user->email ?? null,
                    'currency_code' => 'KWD',
                    'shipping_cost' => (float) ($order->total_shipping ?? 0),
                    'coupon_amount' => (float) ($order->coupon_discount ?? 0),
                    'wallet_amount' => (float) ($order->wallet_used ?? 0),
                    'provider' => Invoice::PROVIDER_SADAD,
                    'status' => Invoice::STATUS_PENDING,
                    'provider_payload' => [
                        'order_id' => $order->id,
                    ],
                ]);

                // Order::items() returns VendorOrderItems (hasManyThrough). Use name/qty/price.
                foreach ($order->items as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'name' => (string) ($item->product->name ?? 'Item'),
                        'quantity' => (int) ($item->quantity ?? 1),
                        'price' => (float) ($item->price ?? 0),
                    ]);
                }

                return $invoice->fresh('items');
            });

            $paymentUrl = $paymentService->generatePaymentLink($invoice);

            return response()->json([
                'success' => true,
                'invoice_id' => $invoice->id,
                'payment_url' => $paymentUrl,
                'status' => $invoice->status,
            ]);
        }

        // Tabby: return payment link instead of marking paid
        if (strtolower($paymentMethod) == 'tabby') {
            if (! $user->email) {
                return response()->json([
                    'success' => false,
                    'message' => __('Email is required for Tabby payments.'),
                ], 422);
            }
            if (! $user->phone) {
                return response()->json([
                    'success' => false,
                    'message' => __('Phone is required for Tabby payments.'),
                ], 422);
            }

            $order = $this->service->getOrderByIdForUser($id, $user->id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => __('Order not found.'),
                ], 404);
            }

            $existing = Invoice::query()
                ->with('items')
                ->where('provider', Invoice::PROVIDER_TABBY)
                ->where('status', Invoice::STATUS_PENDING)
                ->whereJsonContains('provider_payload->order_id', $order->id)
                ->latest('id')
                ->first();

            if ($existing && $existing->payment_url) {
                return response()->json([
                    'success' => true,
                    'invoice_id' => $existing->id,
                    'payment_url' => $existing->payment_url,
                    'status' => $existing->status,
                ]);
            }
            /** @var PaymentService $paymentService */
            $paymentService = app(PaymentService::class);

            if ($existing) {
                $paymentUrl = $paymentService->generatePaymentLink($existing);

                return response()->json([
                    'success' => true,
                    'invoice_id' => $existing->id,
                    'payment_url' => $paymentUrl,
                    'status' => $existing->status,
                ]);
            }

            $invoice = DB::transaction(function () use ($order, $user) {
                $invoice = Invoice::create([
                    'user_id' => $user->id,
                    'customer_name' => $user->name,
                    'customer_phone' => $user->phone ?? null,
                    'customer_email' => $user->email ?? null,
                    'currency_code' => 'KWD',
                    'shipping_cost' => (float) ($order->total_shipping ?? 0),
                    'coupon_amount' => (float) ($order->coupon_discount ?? 0),
                    'wallet_amount' => (float) ($order->wallet_used ?? 0),
                    'subtotal' => (float) ($order->sub_total ?? 0),
                    'total_amount' => (float) ($order->total ?? 0),
                    'provider' => Invoice::PROVIDER_TABBY,
                    'status' => Invoice::STATUS_PENDING,
                    'provider_payload' => [
                        'order_id' => $order->id,
                    ],
                ]);

                foreach ($order->items as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'name' => (string) ($item->product->name ?? 'Item'),
                        'quantity' => (int) ($item->quantity ?? 1),
                        'price' => (float) ($item->price ?? 0),
                    ]);
                }

                return $invoice->fresh('items');
            });

            $paymentUrl = $paymentService->generatePaymentLink($invoice);

            return response()->json([
                'success' => true,
                'invoice_id' => $invoice->id,
                'payment_url' => $paymentUrl,
                'status' => $invoice->status,
            ]);
        }

        $paymentMethodLower = strtolower($paymentMethod);
        if ($paymentMethodLower == 'dema' || $paymentMethodLower == 'deema') {
            if (! $user->email) {
                return response()->json([
                    'success' => false,
                    'message' => __('Email is required for Deema payments.'),
                ], 422);
            }
            if (! $user->phone) {
                return response()->json([
                    'success' => false,
                    'message' => __('Phone is required for Deema payments.'),
                ], 422);
            }

            $order = $this->service->getOrderByIdForUser($id, $user->id);

            if (! $order) {
                return response()->json([
                    'success' => false,
                    'message' => __('Order not found.'),
                ], 404);
            }

            $existing = Invoice::query()
                ->with('items')
                ->where('provider', Invoice::PROVIDER_DEMA)
                ->where('status', Invoice::STATUS_PENDING)
                ->whereJsonContains('provider_payload->order_id', $order->id)
                ->latest('id')
                ->first();

            if ($existing && $existing->payment_url) {
                return response()->json([
                    'success' => true,
                    'invoice_id' => $existing->id,
                    'payment_url' => $existing->payment_url,
                    'status' => $existing->status,
                ]);
            }

            /** @var PaymentService $paymentService */
            $paymentService = app(PaymentService::class);

            if ($existing) {
                try {
                    $paymentUrl = $paymentService->generatePaymentLink($existing);
                } catch (\Throwable $e) {
                    return $this->demaPaymentLinkErrorResponse($e);
                }

                return response()->json([
                    'success' => true,
                    'invoice_id' => $existing->id,
                    'payment_url' => $paymentUrl,
                    'status' => $existing->status,
                ]);
            }

            $invoice = DB::transaction(function () use ($order, $user) {
                $invoice = Invoice::create([
                    'user_id' => $user->id,
                    'customer_name' => $user->name,
                    'customer_phone' => $user->phone ?? null,
                    'customer_email' => $user->email ?? null,
                    'currency_code' => 'KWD',
                    'shipping_cost' => (float) ($order->total_shipping ?? 0),
                    'coupon_amount' => (float) ($order->coupon_discount ?? 0),
                    'wallet_amount' => (float) ($order->wallet_used ?? 0),
                    'subtotal' => (float) ($order->sub_total ?? 0),
                    'total_amount' => (float) ($order->total ?? 0),
                    'provider' => Invoice::PROVIDER_DEMA,
                    'status' => Invoice::STATUS_PENDING,
                    'provider_payload' => [
                        'order_id' => $order->id,
                    ],
                ]);

                foreach ($order->items as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'name' => (string) ($item->product->name ?? 'Item'),
                        'quantity' => (int) ($item->quantity ?? 1),
                        'price' => (float) ($item->price ?? 0),
                    ]);
                }

                return $invoice->fresh('items');
            });

            try {
                $paymentUrl = $paymentService->generatePaymentLink($invoice);
            } catch (\Throwable $e) {
                return $this->demaPaymentLinkErrorResponse($e);
            }

            return response()->json([
                'success' => true,
                'invoice_id' => $invoice->id,
                'payment_url' => $paymentUrl,
                'status' => $invoice->status,
            ]);
        }

        // Other methods: keep old behavior
        $order = $this->service->payOrderImmediatelyForUser($id, $user->id, $paymentMethod);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => __('Order not found.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => __('Order paid successfully.'),
            'data' => new OrderResource($order),
        ]);
    }

    private function demaPaymentLinkErrorResponse(\Throwable $exception): JsonResponse
    {
        $message = $exception->getMessage();
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if ($this->demaErrorLooksLikeTapAmountOrCreditLimit($message)) {
            $body['hint'] = __('Deema Tap sandbox amount hint');
        }

        return response()->json($body, 422);
    }

    private function demaErrorLooksLikeTapAmountOrCreditLimit(string $message): bool
    {
        $lower = strtolower($message);

        return str_contains($lower, 'credit limit')
            || str_contains($lower, 'not within the merchant')
            || str_contains($lower, 'sandbox');
    }
}
