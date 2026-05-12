<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\DigitalCategory;
use App\Models\DigitalMerchant;
use App\Models\DigitalProduct;
use App\Models\DigitalSubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_digital_products_index_respects_country_restrictions(): void
    {
        $kw = Country::factory()->create(['code' => 'KW', 'sort_order' => 1]);
        $eg = Country::factory()->create(['code' => 'EG', 'sort_order' => 2]);
        $admin = User::factory()->create();

        $merchant = DigitalMerchant::create([
            'merchant_id' => 'M-API-1',
            'company_name' => 'TestCo',
            'name' => ['en' => 'Merchant', 'ar' => 'Merchant'],
        ]);
        $category = DigitalCategory::create([
            'name' => ['en' => 'Cat', 'ar' => 'Cat'],
            'slug' => 'cat-api',
            'visits' => 0,
            'is_active' => true,
            'last_update_by' => $admin->id,
        ]);
        $subCategory = DigitalSubCategory::create([
            'name' => ['en' => 'Sub', 'ar' => 'Sub'],
            'slug' => 'sub-api',
            'visits' => 0,
            'is_active' => true,
            'digital_category_id' => $category->id,
            'last_update_by' => $admin->id,
        ]);

        $onlyKw = DigitalProduct::create([
            'product_id' => 'DP-API-KW-1',
            'company_name' => 'TestCo',
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => ['en' => 'KW Only', 'ar' => 'KW'],
            'slug' => 'dp-api-kw-only',
            'description' => ['en' => '', 'ar' => ''],
            'how_to_use' => ['en' => '', 'ar' => ''],
            'cost_after_vat' => 5,
            'price' => 10,
            'currency' => 'KWD',
            'is_active' => true,
            'is_available' => true,
            'visits' => 0,
            'optional_fields_exists' => false,
            'last_update_by' => $admin->id,
        ]);
        $onlyKw->countries()->attach($kw->id);

        $global = DigitalProduct::create([
            'product_id' => 'DP-API-GLOBAL-1',
            'company_name' => 'TestCo',
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => ['en' => 'Global', 'ar' => 'G'],
            'slug' => 'dp-api-global',
            'description' => ['en' => '', 'ar' => ''],
            'how_to_use' => ['en' => '', 'ar' => ''],
            'cost_after_vat' => 5,
            'price' => 10,
            'currency' => 'KWD',
            'is_active' => true,
            'is_available' => true,
            'visits' => 0,
            'optional_fields_exists' => false,
            'last_update_by' => $admin->id,
        ]);

        $egResp = $this->getJson('/api/digital-products?country_id='.$eg->id);
        $egResp->assertOk();
        $egIds = collect($egResp->json('data'))->pluck('id')->all();
        $this->assertContains($global->id, $egIds);
        $this->assertNotContains($onlyKw->id, $egIds);

        $kwResp = $this->getJson('/api/digital-products?country_id='.$kw->id);
        $kwResp->assertOk();
        $kwIds = collect($kwResp->json('data'))->pluck('id')->all();
        $this->assertContains($global->id, $kwIds);
        $this->assertContains($onlyKw->id, $kwIds);
    }

    public function test_api_digital_products_show_returns_404_when_not_visible_in_country(): void
    {
        $kw = Country::factory()->create(['code' => 'KW', 'sort_order' => 1]);
        $eg = Country::factory()->create(['code' => 'EG', 'sort_order' => 2]);
        $admin = User::factory()->create();

        $merchant = DigitalMerchant::create([
            'merchant_id' => 'M-API-2',
            'company_name' => 'TestCo',
            'name' => ['en' => 'Merchant', 'ar' => 'Merchant'],
        ]);
        $category = DigitalCategory::create([
            'name' => ['en' => 'Cat', 'ar' => 'Cat'],
            'slug' => 'cat-api-2',
            'visits' => 0,
            'is_active' => true,
            'last_update_by' => $admin->id,
        ]);
        $subCategory = DigitalSubCategory::create([
            'name' => ['en' => 'Sub', 'ar' => 'Sub'],
            'slug' => 'sub-api-2',
            'visits' => 0,
            'is_active' => true,
            'digital_category_id' => $category->id,
            'last_update_by' => $admin->id,
        ]);

        $onlyKw = DigitalProduct::create([
            'product_id' => 'DP-API-KW-2',
            'company_name' => 'TestCo',
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => ['en' => 'KW Only', 'ar' => 'KW'],
            'slug' => 'dp-api-kw-only-2',
            'description' => ['en' => '', 'ar' => ''],
            'how_to_use' => ['en' => '', 'ar' => ''],
            'cost_after_vat' => 5,
            'price' => 10,
            'currency' => 'KWD',
            'is_active' => true,
            'is_available' => true,
            'visits' => 0,
            'optional_fields_exists' => false,
            'last_update_by' => $admin->id,
        ]);
        $onlyKw->countries()->attach($kw->id);

        $this->getJson('/api/digital-products/'.$onlyKw->id.'?country_id='.$eg->id)->assertNotFound();
        $this->getJson('/api/digital-products/'.$onlyKw->id.'?country_id='.$kw->id)->assertOk();
    }
}
