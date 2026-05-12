<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\User;
use App\Services\SadadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderPaySadadReturnsPaymentLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_pay_order_with_sadad_returns_payment_link_using_existing_pending_invoice(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = Order::create([
            'user_id' => $user->id,
            'sub_total' => 10.00,
            'total' => 10.00,
            'status' => 'pending',
            'order_discount' => 0,
            'coupon_discount' => 0,
            'total_shipping' => 0,
            'points_discount' => 0,
            'wallet_used' => 0,
            'total_commission' => 0,
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
            'provider' => Invoice::PROVIDER_SADAD,
            'status' => Invoice::STATUS_PENDING,
            'provider_payload' => [
                'order_id' => $order->id,
            ],
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name' => 'Item',
            'quantity' => 1,
            'price' => 10.000,
        ]);

        $this->mock(SadadService::class, function ($mock): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withArgs(function (array $payload): bool {
                    $invoice = $payload['invoices'][0] ?? null;
                    if (! is_array($invoice)) {
                        return false;
                    }

                    if (($invoice['amount'] ?? null) !== '10.000') {
                        return false;
                    }

                    $items = $invoice['items'] ?? null;
                    if (! is_array($items) || count($items) !== 1) {
                        return false;
                    }

                    if (($items[0]['price'] ?? null) !== '10.000') {
                        return false;
                    }

                    if (($items[0]['amount'] ?? null) !== '10.000') {
                        return false;
                    }

                    return true;
                })
                ->andReturn([
                    'isValid' => true,
                    'response' => [
                        'invoiceId' => 'SADAD-INV-ORDER-1',
                        'invoiceURL' => 'https://example.test/sadad/pay/order',
                    ],
                ]);

            $mock->shouldNotReceive('getInvoice');
        });

        $response = $this->postJson("/api/orders/{$order->id}/pay", [
            'payment_method' => 'sadad',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'invoice_id' => $invoice->id,
            'payment_url' => 'https://example.test/sadad/pay/order',
            'status' => Invoice::STATUS_PENDING,
        ]);
    }
}
