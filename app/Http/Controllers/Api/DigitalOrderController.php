<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexDigitalOrdersRequest;
use App\Http\Requests\PayDigitalOrderRequest;
use App\Http\Requests\StoreDigitalOrderRequest;
use App\Http\Resources\DigitalOrderResource;
use App\Models\DigitalOrder;
use App\Models\DigitalProduct;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\DigitalOrderService;
use App\Services\PaymentService;
use App\Support\CountryHeaderResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DigitalOrderController extends Controller
{
    public function __construct(
        protected DigitalOrderService $service,
        protected CountryHeaderResolver $countryHeaderResolver
    ) {}

    /**
     * Listing of the authenticated user's digital orders.
     */
    public function index(IndexDigitalOrdersRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);

        $query = DigitalOrder::query()
            ->where('user_id', (int) $request->user()->id)
            ->with([
                'items.digitalProduct.merchant',
                'items.digitalProduct.category',
                'items.digitalProduct.subCategory',
            ])
            ->orderByDesc('id');

        $paymentFilter = isset($validated['payment_status']) ? (string) $validated['payment_status'] : '';
        if ($paymentFilter !== '') {
            $query->where('payment_status', $paymentFilter);
        }

        $statusFilter = isset($validated['status']) ? (string) $validated['status'] : '';
        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $orders = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => DigitalOrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Show a single digital order that belongs to the authenticated user.
     */
    public function show(Request $request, int $digital_order): JsonResponse
    {
        $order = DigitalOrder::query()
            ->where('user_id', (int) $request->user()->id)
            ->with([
                'items.digitalProduct.merchant',
                'items.digitalProduct.category',
                'items.digitalProduct.subCategory',
            ])
            ->find($digital_order);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => __('Order not found.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new DigitalOrderResource($order),
        ]);
    }

    public function store(StoreDigitalOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $digitalProduct = DigitalProduct::query()->findOrFail((int) $request->validated('digital_product_id'));

        $order = $this->service->createSingleProductOrder($user, $digitalProduct, (string) $request->ip());

        return response()->json([
            'message' => __('Order created. Please check your email to confirm your IP address.'),
            'order_id' => $order->id,
            'payment_link' => null,
        ]);
    }

    /*public function store(StoreDigitalOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $digitalProduct = DigitalProduct::query()->findOrFail((int) $request->validated('digital_product_id'));
        $country = $request->attributes->get('resolved_country');
        $countryResult = ['error' => null];
        if (! $country) {
            $countryResult = $this->countryHeaderResolver->resolve($request);
            $country = $countryResult['country'] ?? null;
        }
        if (! $country) {
            return response()->json([
                'success' => false,
                'message' => $countryResult['error'] ?? __('Country header is required.'),
                'errors' => [
                    'country_header' => [$countryResult['error'] ?? __('Country header is required.')],
                ],
            ], 422);
        }

        $order = $this->service->createSingleProductOrder(
            $user,
            $digitalProduct,
            (string) $request->ip(),
            (int) $request->validated('state_id'),
            (int) $request->validated('city_id'),
            (int) $country->id
        );

        return response()->json([
            'message' => __('Order created. Please check your email to confirm your IP address.'),
            'order_id' => $order->id,
            'payment_link' => null,
        ]);
    }*/

    /**
     * Create/reuse a pending invoice then return a payment link.
     */
       public function pay(int $digital_order, PayDigitalOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $paymentMethod = strtolower((string) $request->validated('payment_method'));

        if (! in_array($paymentMethod, [Invoice::PROVIDER_SADAD, Invoice::PROVIDER_TABBY], true)) {
            return response()->json([
                'success' => false,
                'message' => __('Unsupported payment method for digital orders.'),
            ], 422);
        }

        $order = DigitalOrder::query()
            ->where('user_id', (int) $user->id)
            ->with(['items.digitalProduct'])
            ->find($digital_order);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => __('Order not found.'),
            ], 404);
        }

        if ($paymentMethod === Invoice::PROVIDER_TABBY) {
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
        }

        $existing = Invoice::query()
            ->with('items')
            ->where('provider', $paymentMethod)
            ->where('status', Invoice::STATUS_PENDING)
            ->whereJsonContains('provider_payload->digital_order_id', $order->id)
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

        $invoice = DB::transaction(function () use ($order, $user, $paymentMethod) {
            $invoice = Invoice::create([
                'user_id' => $user->id,
                'customer_name' => $user->name,
                'customer_phone' => $user->phone ?? null,
                'customer_email' => $user->email ?? null,
                'currency_code' => 'KWD',
                'shipping_cost' => (float) ($order->shipping_cost ?? 0),
                'coupon_amount' => 0,
                'wallet_amount' => 0,
                'subtotal' => (float) ($order->total ?? 0),
                'total_amount' => (float) ($order->total_cost ?? ((float) ($order->total ?? 0) + (float) ($order->shipping_cost ?? 0))),
                'provider' => $paymentMethod,
                'status' => Invoice::STATUS_PENDING,
                'provider_payload' => [
                    'digital_order_id' => $order->id,
                ],
            ]);

            foreach ($order->items as $item) {
                $product = $item->digitalProduct;
                $name = 'Digital item';
                if ($product) {
                    $locale = app()->getLocale();
                    $translated = method_exists($product, 'getTranslation')
                        ? (string) $product->getTranslation('name', $locale)
                        : null;
                    if ((! is_string($translated) || $translated === '') && method_exists($product, 'getTranslation')) {
                        $translated = (string) $product->getTranslation('name', 'en');
                    }
                    $name = is_string($translated) && $translated !== '' ? $translated : (string) ($product->slug ?? 'Digital item');
                }

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'name' => $name,
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

    public function captureIp(Request $request, DigitalOrder $digitalOrder): JsonResponse
    {
        $digitalOrder->update([
            'user_ip_address' => (string) $request->ip(),
        ]);

        return response()->json([
            'message' => __('IP address confirmed.'),
            'order_id' => $digitalOrder->id,
        ]);
    }

    /**
     * TEST ONLY: Place the provider order immediately (skips payment).
     */
    public function providerOrderTest(Request $request, DigitalOrder $digitalOrder): JsonResponse
    {
        if (! app()->environment('local') && ! config('app.debug')) {
            abort(404);
        }

        $user = $request->user();
        if (! $user || (int) $digitalOrder->user_id !== (int) $user->id) {
            abort(403);
        }

        try {
            $result = $this->service->placeProviderOrderForTest($digitalOrder);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => __('Provider order placed (test mode).'),
            'order_id' => $digitalOrder->id,
            'provider_response' => $result,
        ]);
    }
}
