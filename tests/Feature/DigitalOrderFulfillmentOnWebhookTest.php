<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\DigitalCategory;
use App\Models\DigitalMerchant;
use App\Models\DigitalOrder;
use App\Models\DigitalOrderItem;
use App\Models\DigitalProduct;
use App\Models\DigitalSubCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\DigitalOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalOrderFulfillmentOnWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_sadad_webhook_paid_fulfills_digital_order_and_persists_delivered_data(): void
    {
        $country = Country::factory()->create(['code' => 'KW', 'sort_order' => 1, 'name' => ['en' => 'Kuwait', 'ar' => 'KW']]);
        $user = User::factory()->create(['country_id' => $country->id]);

        $product = $this->makeDigitalCatalogWithProduct($user);
        $order = $this->persistDigitalOrder($user, $product, quantity: 1, unitPrice: 10.0);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone,
            'customer_email' => $user->email,
            'currency_code' => 'KWD',
            'provider' => Invoice::PROVIDER_SADAD,
            'status' => Invoice::STATUS_PENDING,
            'provider_invoice_id' => 'SADAD-INV-1',
            'provider_payload' => [
                'digital_order_id' => $order->id,
            ],
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name' => 'Digital',
            'quantity' => 1,
            'price' => 10.000,
        ]);

        $this->mock(DigitalOrderService::class, function ($mock) use ($order, $invoice): void {
            $mock->shouldReceive('fulfillAfterPayment')
                ->once()
                ->withArgs(function (DigitalOrder $passedOrder, string $paymentRef) use ($order, $invoice): bool {
                    return (int) $passedOrder->id === (int) $order->id && $paymentRef === 'INV-'.$invoice->id.'-DO-'.$order->id;
                })
                ->andReturn([
                    'delivered' => true,
                ]);
        });

        $resp = $this->postJson('/sadad/webhook', [
            'invoiceId' => 'SADAD-INV-1',
            'status' => 'Paid',
        ]);

        $resp->assertOk();

        $invoice->refresh();
        $this->assertSame(Invoice::STATUS_PAID, $invoice->status);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
    }

    private function makeDigitalCatalogWithProduct(User $user): DigitalProduct
    {
        $slugSuffix = uniqid('', true);

        $merchant = DigitalMerchant::create([
            'merchant_id' => 'M-WH-'.$slugSuffix,
            'company_name' => 'TestCo',
            'name' => ['en' => 'Merchant', 'ar' => 'Merchant'],
        ]);
        $category = DigitalCategory::create([
            'name' => ['en' => 'Cat', 'ar' => 'Cat'],
            'slug' => 'cat-wh-'.$slugSuffix,
            'visits' => 0,
            'is_active' => true,
            'last_update_by' => $user->id,
        ]);
        $subCategory = DigitalSubCategory::create([
            'name' => ['en' => 'Sub', 'ar' => 'Sub'],
            'slug' => 'sub-wh-'.$slugSuffix,
            'visits' => 0,
            'is_active' => true,
            'digital_category_id' => $category->id,
            'last_update_by' => $user->id,
        ]);

        return DigitalProduct::create([
            'product_id' => 'DP-WH-'.$slugSuffix,
            'company_name' => 'TestCo',
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => ['en' => 'DP', 'ar' => 'DP'],
            'slug' => 'dp-wh-'.$slugSuffix,
            'description' => ['en' => '', 'ar' => ''],
            'how_to_use' => ['en' => '', 'ar' => ''],
            'cost_after_vat' => 5,
            'price' => 10,
            'currency' => 'KWD',
            'is_active' => true,
            'is_available' => true,
            'visits' => 0,
            'optional_fields_exists' => false,
            'last_update_by' => $user->id,
        ]);
    }

    private function persistDigitalOrder(User $user, DigitalProduct $digitalProduct, int $quantity, float $unitPrice): DigitalOrder
    {
        $countryCode = Country::query()->find($user->country_id)?->code ?? '';

        $order = DigitalOrder::query()->create([
            'user_id' => $user->id,
            'user_name' => (string) $user->name,
            'user_email' => (string) $user->email,
            'user_phone' => (string) ($user->phone ?? '000'),
            'user_gender' => (string) ($user->gender ?? 'male'),
            'user_birth_date' => $user->birth_date ?? now()->subYears(20)->toDateString(),
            'user_national_number' => (string) ($user->national_number ?? '123'),
            'user_national_cart_front_image' => (string) ($user->national_cart_front_image ?? 'front.png'),
            'user_national_cart_back_image' => (string) ($user->national_cart_back_image ?? 'back.png'),
            'user_national_id_expire_date' => $user->national_id_expire_date ?? now()->addYear()->toDateString(),
            'user_home_address' => (string) ($user->home_address ?? 'Somewhere'),
            'user_ip_address' => '127.0.0.1',
            'user_country' => $countryCode !== '' ? $countryCode : (string) $user->country_id,
            'payment_status' => 'pending',
            'status' => 'pending',
            'notes' => 'test',
            'total' => $unitPrice * $quantity,
            'discount' => 0,
            'shipping_cost' => 0,
            'total_cost' => $unitPrice * $quantity,
        ]);

        DigitalOrderItem::query()->create([
            'digital_order_id' => $order->id,
            'digital_product_id' => $digitalProduct->id,
            'price' => $unitPrice,
            'quantity' => $quantity,
            'total' => $unitPrice * $quantity,
            'notes' => null,
        ]);

        return $order->fresh(['items.digitalProduct']);
    }
}
