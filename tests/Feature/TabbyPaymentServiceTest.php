<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TabbyPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_tabby_checkout_and_persists_payment_id_and_url(): void
    {
        Role::create(['name' => 'user', 'guard_name' => 'web']);

        config()->set('services.tabby.base_url', 'https://api.tabby.ai');
        config()->set('services.tabby.secret_key', 'test-secret');
        config()->set('services.tabby.merchant_code', 'test-merchant');
        config()->set('services.tabby.lang', 'en');
        config()->set('services.tabby.merchant_urls.success', 'https://example.test/tabby/success');
        config()->set('services.tabby.merchant_urls.cancel', 'https://example.test/tabby/cancel');
        config()->set('services.tabby.merchant_urls.failure', 'https://example.test/tabby/failure');

        $user = User::factory()->create([
            'email' => 'buyer@example.com',
            'phone' => '+96550000001',
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'currency_code' => 'KWD',
            'shipping_cost' => 0,
            'coupon_amount' => 0,
            'wallet_amount' => 0,
            'total_amount' => 10.000,
            'provider' => Invoice::PROVIDER_TABBY,
            'status' => Invoice::STATUS_PENDING,
            'provider_payload' => ['order_id' => 1],
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name' => 'Item',
            'quantity' => 1,
            'price' => 10.000,
        ]);

        Http::fake([
            'https://api.tabby.ai/api/v2/checkout' => Http::response([
                'status' => 'created',
                'web_url' => 'https://checkout.tabby.ai/session/xyz',
                'payment' => ['id' => 'pay_123'],
            ], 200),
        ]);

        $service = $this->app->make(PaymentService::class);

        $url = $service->generatePaymentLink($invoice->fresh('items'));

        $this->assertSame('https://checkout.tabby.ai/session/xyz', $url);

        $invoice->refresh();
        $this->assertSame('pay_123', $invoice->provider_invoice_id);
        $this->assertSame('https://checkout.tabby.ai/session/xyz', $invoice->payment_url);
        $this->assertNotNull(data_get($invoice->provider_payload, 'tabby_request'));
        $this->assertNotNull(data_get($invoice->provider_response, 'checkout'));
    }

    public function test_it_accepts_nested_web_url_from_available_products(): void
    {
        Role::create(['name' => 'user', 'guard_name' => 'web']);

        config()->set('services.tabby.base_url', 'https://api.tabby.ai');
        config()->set('services.tabby.secret_key', 'test-secret');
        config()->set('services.tabby.merchant_code', 'test-merchant');
        config()->set('services.tabby.lang', 'en');

        $user = User::factory()->create([
            'email' => 'buyer@example.com',
            'phone' => '+96550000001',
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_code' => 'KWD',
            'shipping_cost' => 0,
            'coupon_amount' => 0,
            'wallet_amount' => 0,
            'total_amount' => 10.000,
            'provider' => Invoice::PROVIDER_TABBY,
            'status' => Invoice::STATUS_PENDING,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name' => 'Item',
            'quantity' => 1,
            'price' => 10.000,
        ]);

        Http::fake([
            'https://api.tabby.ai/api/v2/checkout' => Http::response([
                'status' => 'created',
                'configuration' => [
                    'available_products' => [
                        'installments' => [[
                            'web_url' => 'https://checkout.tabby.ai/?session=nested',
                        ]],
                    ],
                ],
                'payment' => ['id' => 'pay_nested'],
            ], 200),
        ]);

        $service = $this->app->make(PaymentService::class);
        $url = $service->generatePaymentLink($invoice->fresh('items'));

        $this->assertSame('https://checkout.tabby.ai/?session=nested', $url);
    }

    public function test_it_computes_positive_amount_when_total_amount_missing(): void
    {
        Role::create(['name' => 'user', 'guard_name' => 'web']);

        config()->set('services.tabby.base_url', 'https://api.tabby.ai');
        config()->set('services.tabby.secret_key', 'test-secret');
        config()->set('services.tabby.merchant_code', 'test-merchant');
        config()->set('services.tabby.lang', 'en');

        $user = User::factory()->create([
            'email' => 'buyer@example.com',
            'phone' => '+96550000001',
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_code' => 'KWD',
            'shipping_cost' => 0,
            'coupon_amount' => 0,
            'wallet_amount' => 0,
            'total_amount' => 0,
            'provider' => Invoice::PROVIDER_TABBY,
            'status' => Invoice::STATUS_PENDING,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name' => 'Item',
            'quantity' => 1,
            'price' => 5.000,
        ]);

        Http::fake(function ($request) {
            $body = $request->data();
            $this->assertSame('5.000', data_get($body, 'payment.amount'));

            return Http::response([
                'status' => 'created',
                'web_url' => 'https://checkout.tabby.ai/session/xyz',
                'payment' => ['id' => 'pay_123'],
            ], 200);
        });

        $service = $this->app->make(PaymentService::class);
        $url = $service->generatePaymentLink($invoice->fresh('items'));

        $this->assertSame('https://checkout.tabby.ai/session/xyz', $url);
    }
}
