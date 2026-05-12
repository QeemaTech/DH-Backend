<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\PaymentService;
use App\Services\SadadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SadadPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_preserves_order_id_in_provider_payload_and_does_not_require_get_invoice_when_invoice_url_is_returned(): void
    {
        $invoice = Invoice::create([
            'customer_name' => 'Test Customer',
            'customer_phone' => '00000000',
            'customer_email' => 'test@example.com',
            'currency_code' => 'KWD',
            'shipping_cost' => 0.250,
            'coupon_amount' => 0.111,
            'wallet_amount' => 0.100,
            'provider' => Invoice::PROVIDER_SADAD,
            'status' => Invoice::STATUS_PENDING,
            'provider_payload' => [
                'order_id' => 123,
            ],
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name' => 'Item',
            'quantity' => 3,
            'price' => 3.333,
        ]);

        $this->mock(SadadService::class, function ($mock): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->andReturn([
                    'isValid' => true,
                    'response' => [
                        'invoiceId' => 'SADAD-INV-1',
                        'invoiceURL' => 'https://example.test/sadad/pay/abc',
                    ],
                ]);

            $mock->shouldNotReceive('getInvoice');
        });

        $paymentService = $this->app->make(PaymentService::class);

        $url = $paymentService->generatePaymentLink($invoice->fresh('items'));

        $this->assertSame('https://example.test/sadad/pay/abc', $url);

        $invoice->refresh();
        $this->assertSame(123, data_get($invoice->provider_payload, 'order_id'));
        $this->assertNotNull(data_get($invoice->provider_payload, 'sadad_request'));
    }
}
