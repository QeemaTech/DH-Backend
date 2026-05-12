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

class DigitalProductCountryFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_digital_products_respect_country_restrictions(): void
    {
        $kw = Country::factory()->create(['code' => 'KW', 'sort_order' => 1]);
        $eg = Country::factory()->create(['code' => 'EG', 'sort_order' => 2]);
        $admin = User::factory()->create();
        $merchant = DigitalMerchant::create([
            'merchant_id' => 'M-1',
            'company_name' => 'TestCo',
            'name' => ['en' => 'Merchant', 'ar' => 'Merchant'],
        ]);
        $category = DigitalCategory::create([
            'name' => ['en' => 'Cat', 'ar' => 'Cat'],
            'slug' => 'cat',
            'visits' => 0,
            'is_active' => true,
            'last_update_by' => $admin->id,
        ]);
        $subCategory = DigitalSubCategory::create([
            'name' => ['en' => 'Sub', 'ar' => 'Sub'],
            'slug' => 'sub',
            'visits' => 0,
            'is_active' => true,
            'digital_category_id' => $category->id,
            'last_update_by' => $admin->id,
        ]);

        $onlyKw = DigitalProduct::create([
            'product_id' => 'DP-KW-1',
            'company_name' => 'TestCo',
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => ['en' => 'KW Only', 'ar' => 'KW'],
            'slug' => 'dp-kw-only',
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
            'product_id' => 'DP-GLOBAL-1',
            'company_name' => 'TestCo',
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => ['en' => 'Global', 'ar' => 'G'],
            'slug' => 'dp-global',
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

        $egIds = DigitalProduct::query()->forCountry($eg->id)->pluck('id')->all();
        $this->assertContains($global->id, $egIds);
        $this->assertNotContains($onlyKw->id, $egIds);

        $kwIds = DigitalProduct::query()->forCountry($kw->id)->pluck('id')->all();
        $this->assertContains($global->id, $kwIds);
        $this->assertContains($onlyKw->id, $kwIds);
    }
}
