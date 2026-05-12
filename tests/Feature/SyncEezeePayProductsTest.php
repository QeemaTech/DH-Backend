<?php

namespace Tests\Feature;

use App\Models\DigitalMerchant;
use App\Models\DigitalProduct;
use App\Models\User;
use App\Services\EezeePayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncEezeePayProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if ($this->app->bound(EezeePayService::class)) {
            $this->app->make(EezeePayService::class)->forgetRememberedToken();
        }

        parent::tearDown();
    }

    public function test_sync_persists_products_for_eezee_pay_merchants(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.token', 'tok');
        config()->set('services.eezee_pay.company_name', 'eezee_pay');
        config()->set('services.eezee_pay.sync_user_id', 1);

        User::factory()->create(['id' => 1]);

        DigitalMerchant::create([
            'merchant_id' => '10',
            'company_name' => 'eezee_pay',
            'name' => ['en' => 'Cat', 'ar' => 'Cat'],
            'redeem_steps' => ['en' => 'Redeem', 'ar' => 'Redeem'],
        ]);

        Http::fake(function ($request) {
            $url = (string) $request->url();
            $this->assertStringContainsString('/products', $url);
            $this->assertStringContainsString('category_id=10', $url);

            return Http::response([
                'data' => [
                    [
                        'id' => 501,
                        'path_en' => 'Prod EN',
                        'path_ar' => 'Prod AR',
                        'price' => 5.5,
                        'unit_price' => 7,
                        'currency' => 'KWD',
                        'available' => true,
                    ],
                ],
            ], 200);
        });

        $this->artisan('products:sync-eezeepay')->assertExitCode(0);

        $this->assertDatabaseHas('digital_products', [
            'product_id' => '501',
            'company_name' => 'eezee_pay',
            'cost_after_vat' => 5.5,
            'price' => 7,
            'currency' => 'KWD',
            'is_available' => 1,
        ]);

        $product = DigitalProduct::query()->where('product_id', '501')->firstOrFail();
        $this->assertSame('Prod EN', $product->getTranslation('name', 'en'));
        $this->assertSame('Prod AR', $product->getTranslation('name', 'ar'));
    }

    public function test_dry_run_does_not_insert_products(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.token', 'tok');
        config()->set('services.eezee_pay.company_name', 'eezee_pay');
        config()->set('services.eezee_pay.sync_user_id', 1);

        User::factory()->create(['id' => 1]);

        DigitalMerchant::create([
            'merchant_id' => '1',
            'company_name' => 'eezee_pay',
            'name' => ['en' => 'C', 'ar' => 'C'],
        ]);

        Http::fake([
            'https://sandbox.eezee-pay.com/api/v1/products*' => Http::response([
                'data' => [['id' => 9, 'name' => 'X', 'price' => 1]],
            ], 200),
        ]);

        $this->artisan('products:sync-eezeepay', ['--dry-run' => true])->assertExitCode(0);

        $this->assertSame(0, DigitalProduct::query()->count());
    }
}
