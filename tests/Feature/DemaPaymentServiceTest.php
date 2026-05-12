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

class DemaPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_deema_purchase_and_persists_order_reference_and_redirect_link(): void
    {
        Role::create(['name' => 'user', 'guard_name' => 'web']);

        config()->set('services.dema.base_url', 'https://sandbox-api.deema.me');
        config()->set('services.dema.api_prefix', '/api/merchant/v1');
        config()->set('services.dema.api_key', 'test-key');
        config()->set('services.dema.merchant_urls.success', 'https://example.test/dema/success');
        config()->set('services.dema.merchant_urls.failure', 'https://example.test/dema/failure');

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
            'total_amount' => 150.000,
            'provider' => Invoice::PROVIDER_DEMA,
            'status' => Invoice::STATUS_PENDING,
            'provider_payload' => ['order_id' => 1],
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name' => 'Item',
            'quantity' => 1,
            'price' => 150.000,
        ]);

        Http::fake([
            'https://sandbox-api.deema.me/api/merchant/v1/purchase' => Http::response([
                'message' => 'Purchase created successfully',
                'data' => [
                    'order_reference' => 'ref_123',
                    'purchase_id' => 99,
                    'order_status' => 'pending',
                    'redirect_link' => 'https://staging-pay.deema.me/?order_reference=ref_123',
                ],
            ], 200),
        ]);

        $service = $this->app->make(PaymentService::class);

        $url = $service->generatePaymentLink($invoice->fresh('items'));

        $this->assertSame('https://staging-pay.deema.me/?order_reference=ref_123', $url);

        $invoice->refresh();
        $this->assertSame('ref_123', $invoice->provider_invoice_id);
        $this->assertSame('99', $invoice->provider_key);
        $this->assertSame('https://staging-pay.deema.me/?order_reference=ref_123', $invoice->payment_url);
        $this->assertNotNull(data_get($invoice->provider_payload, 'dema_request'));
        $this->assertNotNull(data_get($invoice->provider_response, 'purchase'));
    }

    public function test_dema_purchase_payload_contains_amount_currency_and_merchant_urls(): void
    {
        Role::create(['name' => 'user', 'guard_name' => 'web']);

        config()->set('services.dema.base_url', 'https://sandbox-api.deema.me');
        config()->set('services.dema.api_prefix', '/api/merchant/v1');
        config()->set('services.dema.api_key', 'test-key');
        config()->set('services.dema.merchant_urls.success', 'https://example.test/dema/success');
        config()->set('services.dema.merchant_urls.failure', 'https://example.test/dema/failure');

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
            'total_amount' => 150.000,
            'provider' => Invoice::PROVIDER_DEMA,
            'status' => Invoice::STATUS_PENDING,
            'provider_payload' => ['order_id' => 1],
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name' => 'Item',
            'quantity' => 1,
            'price' => 150.000,
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $this->assertSame('https://sandbox-api.deema.me/api/merchant/v1/purchase', (string) $request->url());
            $data = $request->data();
            $this->assertSame(150.0, (float) data_get($data, 'amount'));
            $this->assertSame('KWD', data_get($data, 'currency_code'));
            $this->assertSame('1', (string) data_get($data, 'merchant_order_id'));
            $this->assertSame('https://example.test/dema/success', data_get($data, 'merchant_urls.success'));
            $this->assertSame('https://example.test/dema/failure', data_get($data, 'merchant_urls.failure'));

            return Http::response([
                'message' => 'Purchase created successfully',
                'data' => [
                    'order_reference' => 'ref_abc',
                    'purchase_id' => 1,
                    'order_status' => 'pending',
                    'redirect_link' => 'https://staging-pay.deema.me/?order_reference=ref_abc',
                ],
            ], 200);
        });

        $this->app->make(PaymentService::class)->generatePaymentLink($invoice->fresh('items'));
    }

    public function test_dema_webhook_marks_invoice_paid_on_captured(): void
    {
        Role::create(['name' => 'user', 'guard_name' => 'web']);

        config()->set('services.dema.webhook.header_name', '');
        config()->set('services.dema.webhook.header_value', '');

        $user = User::factory()->create();

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'currency_code' => 'KWD',
            'shipping_cost' => 0,
            'coupon_amount' => 0,
            'wallet_amount' => 0,
            'total_amount' => 50,
            'provider' => Invoice::PROVIDER_DEMA,
            'status' => Invoice::STATUS_PENDING,
            'provider_invoice_id' => 'ref_webhook_1',
            'provider_payload' => ['order_id' => 9],
        ]);

        $this->postJson('/api/dema/webhook', [
            'order_reference' => 'ref_webhook_1',
            'status' => 'captured',
        ])->assertOk();

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);
    }

    public function test_failed_deema_purchase_throws_with_gateway_message(): void
    {
        Role::create(['name' => 'user', 'guard_name' => 'web']);

        config()->set('services.dema.base_url', 'https://sandbox-api.deema.me');
        config()->set('services.dema.api_prefix', '/api/merchant/v1');
        config()->set('services.dema.api_key', 'test-key');
        config()->set('services.dema.merchant_urls.success', 'https://example.test/dema/success');
        config()->set('services.dema.merchant_urls.failure', 'https://example.test/dema/failure');

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
            'total_amount' => 7.5,
            'provider' => Invoice::PROVIDER_DEMA,
            'status' => Invoice::STATUS_PENDING,
            'provider_payload' => ['order_id' => 1],
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name' => 'Item',
            'quantity' => 1,
            'price' => 7.5,
        ]);

        Http::fake([
            'https://sandbox-api.deema.me/api/merchant/v1/purchase' => Http::response([
                'message' => 'The purchase amount is not within the merchant\'s credit limit range.',
                'error_code' => 115,
            ], 400),
        ]);

        $service = $this->app->make(PaymentService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/credit limit/i');

        $service->generatePaymentLink($invoice->fresh('items'));
    }
}
