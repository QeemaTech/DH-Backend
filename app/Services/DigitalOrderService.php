<?php

namespace App\Services;

use App\Mail\DigitalOrderIpConfirmationMail;
use App\Models\DigitalOrder;
use App\Models\DigitalOrderItem;
use App\Models\DigitalProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class DigitalOrderService
{
    public function __construct(
        protected EezeePayService $eezeePayService,
        protected OneCardService $oneCardService,
        protected Like4AppService $like4AppService,
    ) {}

    public function createSingleProductOrder(User $user, DigitalProduct $digitalProduct, string $ipAddress): DigitalOrder
    {
        if (! $digitalProduct->is_active || ! $digitalProduct->is_available) {
            throw ValidationException::withMessages([
                'digital_product_id' => [__('Digital product is not available.')],
            ]);
        }

        if (! $digitalProduct->isVisibleInCountry($user->country_id !== null ? (int) $user->country_id : null)) {
            throw ValidationException::withMessages([
                'digital_product_id' => [__('Digital product is not available in your country.')],
            ]);
        }

        $this->assertUserProfileComplete($user);
        $this->assertProviderAvailability($digitalProduct);

        return DB::transaction(function () use ($user, $digitalProduct, $ipAddress) {
            $countryCode = $user->country?->code ?? '';

            $order = DigitalOrder::create([
                'user_id' => $user->id,
                'user_name' => (string) $user->name,
                'user_email' => (string) $user->email,
                'user_phone' => (string) $user->phone,
                'user_gender' => (string) ($user->gender ?? ''),
                'user_birth_date' => $user->birth_date,
                'user_national_number' => (string) ($user->national_number ?? ''),
                'user_national_cart_front_image' => (string) ($user->getRawOriginal('national_cart_front_image') ?? ''),
                'user_national_cart_back_image' => (string) ($user->getRawOriginal('national_cart_back_image') ?? ''),
                'user_national_id_expire_date' => $user->national_id_expire_date,
                'user_home_address' => (string) ($user->home_address ?? ''),
                'user_ip_address' => $ipAddress,
                'user_country' => $countryCode !== '' ? $countryCode : (string) ($user->country_id ?? ''),
                'payment_status' => 'pending',
                'status' => 'pending',
                'notes' => '',
                'total' => $digitalProduct->price,
                'discount' => 0,
                'shipping_cost' => 0,
                'total_cost' => $digitalProduct->price,
            ]);

            DigitalOrderItem::create([
                'digital_order_id' => $order->id,
                'digital_product_id' => $digitalProduct->id,
                'price' => $digitalProduct->price,
                'quantity' => 1,
                'total' => $digitalProduct->price,
                'notes' => null,
            ]);

            DB::afterCommit(function () use ($order) {
                $this->sendIpConfirmationMail($order);
            });

            return $order->loadMissing(['items.digitalProduct']);
        });
    }

    /**
     * Test helper: place the provider order immediately (skips payment).
     *
     * @return array<string, mixed>
     */
    public function placeProviderOrderForTest(DigitalOrder $order): array
    {
        $order->loadMissing(['items.digitalProduct']);

        $item = $order->items->first();
        if (! $item || ! $item->digitalProduct) {
            throw new RuntimeException('Digital order has no digital product item.');
        }

        $digitalProduct = $item->digitalProduct;
        $provider = strtolower(trim((string) $digitalProduct->company_name));

        $providerProductId = (int) $digitalProduct->product_id;

        if (str_contains($provider, 'eezee')) {
            if ($providerProductId <= 0) {
                throw new RuntimeException('Provider product id is invalid.');
            }

            return $this->eezeePayService->createOrder(
                paymentRef: 'TEST-DIGITAL-ORDER-'.$order->id,
                cart: [
                    [
                        'qty' => 1,
                        'product_id' => $providerProductId,
                    ],
                ],
            );
        }

        if (str_contains($provider, 'onecard') || str_contains($provider, 'one_card')) {
            if ($providerProductId <= 0) {
                throw new RuntimeException('Provider product id is invalid.');
            }

            return $this->oneCardService->purchaseProduct(
                productId: (string) $digitalProduct->product_id,
                resellerRefNumber: 'TEST-DIGITAL-ORDER-'.$order->id.'-'.rand(1000, 9999),
                terminalId: 'T-'.$order->id.'-'.rand(1000, 9999),
            );
        }

        if (str_contains($provider, 'like')) {
            if ($providerProductId <= 0) {
                throw new RuntimeException('Provider product id is invalid.');
            }

            return $this->like4AppService->createOrder(
                productId: $providerProductId,
                quantity: 1,
                paymentRef: 'TEST-DIGITAL-ORDER-'.$order->id,
            );
        }

        throw new RuntimeException('Unsupported provider for test ordering: '.$digitalProduct->company_name);
    }

    /**
     * Place the provider order after successful payment and persist delivered card/data.
     *
     * @return array<string, mixed>
     */
    public function fulfillAfterPayment(DigitalOrder $order, string $paymentRef): array
    {
        $order->loadMissing(['items.digitalProduct']);

        $item = $order->items->first();
        if (! $item || ! $item->digitalProduct) {
            throw new RuntimeException('Digital order has no digital product item.');
        }

        if (! empty($item->delivered_data)) {
            return [
                'already_delivered' => true,
                'digital_order_item_id' => $item->id,
                'delivered_at' => optional($item->delivered_at)?->toIso8601String(),
            ];
        }

        $digitalProduct = $item->digitalProduct;
        $provider = strtolower(trim((string) $digitalProduct->company_name));

        $providerProductId = (int) $digitalProduct->product_id;
        $quantity = max(1, (int) $item->quantity);

        $providerResponses = [];
        $delivered = [];

        if (str_contains($provider, 'eezee')) {
            if ($providerProductId <= 0) {
                throw new RuntimeException('Provider product id is invalid.');
            }

            $resolvedPaymentRef = $paymentRef;

            try {
                $providerResponses[] = $this->eezeePayService->createOrder(
                    paymentRef: $resolvedPaymentRef,
                    cart: [
                        [
                            'qty' => $quantity,
                            'product_id' => $providerProductId,
                        ],
                    ],
                );
            } catch (Throwable $e) {
                /**
                 * Eezee Pay enforces unique payment_ref. Webhooks/retries can re-hit fulfillment with the same ref.
                 * If we hit a 422, retry once with a more unique reference.
                 */
                if (str_contains($e->getMessage(), 'HTTP 422')) {
                    $resolvedPaymentRef = $paymentRef.'-DO-'.$order->id.'-I-'.$item->id.'-'.substr(uniqid('', true), -8);
                    $providerResponses[] = $this->eezeePayService->createOrder(
                        paymentRef: $resolvedPaymentRef,
                        cart: [
                            [
                                'qty' => $quantity,
                                'product_id' => $providerProductId,
                            ],
                        ],
                    );
                } else {
                    throw $e;
                }
            }

            // Keep the raw response; provider-specific parsing can be expanded later.
            $delivered = [
                'type' => 'eezee',
            ];

            $paymentRef = $resolvedPaymentRef;
        } elseif (str_contains($provider, 'onecard') || str_contains($provider, 'one_card')) {
            if ($providerProductId <= 0) {
                throw new RuntimeException('Provider product id is invalid.');
            }

            for ($i = 0; $i < $quantity; $i++) {
                $resp = $this->oneCardService->purchaseProduct(
                    productId: (string) $digitalProduct->product_id,
                    resellerRefNumber: $paymentRef.'-'.($i + 1),
                );
                $providerResponses[] = $resp;

                $delivered[] = [
                    'serial' => data_get($resp, 'serial'),
                    'pin' => data_get($resp, 'pin'),
                    'image' => data_get($resp, 'image'),
                    'provider_ref' => data_get($resp, 'bbTrxRefNumber') ?? data_get($resp, 'resellerRefNumber'),
                ];
            }
        } elseif (str_contains($provider, 'like')) {
            if ($providerProductId <= 0) {
                throw new RuntimeException('Provider product id is invalid.');
            }

            $resp = $this->like4AppService->createOrder(
                productId: $providerProductId,
                quantity: $quantity,
                paymentRef: $paymentRef,
            );
            $providerResponses[] = $resp;
            $delivered = [
                'type' => 'like4app',
            ];
        } else {
            throw new RuntimeException('Unsupported provider: '.$digitalProduct->company_name);
        }

        $item->update([
            'provider_reference' => $paymentRef,
            'provider_response' => $providerResponses,
            'delivered_data' => $delivered,
            'delivered_at' => now(),
        ]);

        $order->update([
            'payment_status' => 'paid',
            'status' => 'delivered',
        ]);

        return [
            'delivered' => true,
            'digital_order_item_id' => $item->id,
            'provider' => $digitalProduct->company_name,
            'quantity' => $quantity,
        ];
    }

    protected function assertUserProfileComplete(User $user): void
    {
        $missing = [];

        foreach ([
            'name',
            'email',
            'phone',
            'gender',
            'birth_date',
            'national_number',
            'national_cart_front_image',
            'national_cart_back_image',
            'national_id_expire_date',
            'home_address',
        ] as $field) {
            if (! filled($user->{$field} ?? null)) {
                $missing[] = $field;
            }
        }

        if (! empty($missing)) {
            throw ValidationException::withMessages([
                'user' => [__('Please complete your profile data before ordering.')],
                'missing_fields' => $missing,
            ]);
        }
    }

    protected function assertProviderAvailability(DigitalProduct $digitalProduct): void
    {
        $provider = strtolower(trim((string) $digitalProduct->company_name));

        if (str_contains($provider, 'eezee')) {
            $providerProductId = (int) $digitalProduct->product_id;
            if ($providerProductId <= 0) {
                return;
            }

            $result = $this->eezeePayService->checkProductAvailability($providerProductId, 1);
            $available = (bool) (data_get($result, 'available')
                ?? data_get($result, 'data.available')
                ?? data_get($result, 'success')
                ?? true);

            if (! $available) {
                throw ValidationException::withMessages([
                    'digital_product_id' => [__('Digital product is currently unavailable.')],
                ]);
            }
        }
    }

    protected function sendIpConfirmationMail(DigitalOrder $order): void
    {
        $url = URL::temporarySignedRoute(
            'api.digital-orders.capture-ip',
            now()->addHours(12),
            ['digitalOrder' => $order->id]
        );

        Mail::to($order->user_email)->send(new DigitalOrderIpConfirmationMail($order, $url));
    }
}

