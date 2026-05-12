<?php

namespace Tests\Feature;

use App\Models\DigitalCategory;
use App\Models\DigitalMerchant;
use App\Models\DigitalProduct;
use App\Models\DigitalSubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncLike4AppProductsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_products_for_like4app_merchants(): void
    {
        config()->set('services.like4app.products_url', 'https://taxes.like4app.com/online/products');
        config()->set('services.like4app.device_id', 'device');
        config()->set('services.like4app.email', 'test@example.com');
        config()->set('services.like4app.security_code', 'sec');
        config()->set('services.like4app.lang_id', 1);
        config()->set('services.like4app.company_name', 'like card');
        config()->set('services.like4app.sync_user_id', 1);
        config()->set('services.like4app.timeout', 30);

        User::factory()->create(['id' => 1]);

        $merchant = DigitalMerchant::create([
            'merchant_id' => '10',
            'company_name' => 'like card',
            'name' => ['en' => 'M', 'ar' => 'M'],
            'redeem_steps' => ['en' => 'use code', 'ar' => 'use code'],
        ]);

        Http::fake([
            'https://taxes.like4app.com/online/products' => Http::response([
                'data' => [[
                    'productId' => 'P1',
                    'categoryId' => '10',
                    'productName' => 'Prod 1',
                    'productImage' => '',
                    'productPrice' => 10,
                    'sellPrice' => 12,
                    'available' => true,
                    'optionalFieldsExist' => 0,
                    'productCurrency' => 'KWD',
                ]],
            ], 200),
        ]);

        $this->artisan('products:sync-like4app')
            ->assertExitCode(0);

        $category = DigitalCategory::where('slug', 'like4app')->firstOrFail();
        $subCategory = DigitalSubCategory::where('slug', 'like4app-general')->firstOrFail();

        $this->assertDatabaseHas('digital_products', [
            'product_id' => 'P1',
            'company_name' => 'like card',
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'is_available' => 1,
        ]);

        $product = DigitalProduct::where('product_id', 'P1')->firstOrFail();
        $this->assertSame('Prod 1', data_get($product->getTranslations('name'), 'en'));
    }
}
