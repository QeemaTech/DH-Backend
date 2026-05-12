<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCountryFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_products_respect_country_restrictions(): void
    {
        $vendor = Vendor::factory()->create();
        $kw = Country::factory()->create(['code' => 'KW', 'sort_order' => 1]);
        $eg = Country::factory()->create(['code' => 'EG', 'sort_order' => 2]);

        $onlyKw = Product::create([
            'vendor_id' => $vendor->id,
            'type' => 'simple',
            'name' => ['en' => 'KW Only', 'ar' => 'KW'],
            'description' => ['en' => '', 'ar' => ''],
            'sku' => 'SKU-KW-1',
            'slug' => 'kw-only',
            'price' => 10,
            'is_active' => true,
            'is_approved' => true,
        ]);
        $onlyKw->countries()->attach($kw->id);

        $global = Product::create([
            'vendor_id' => $vendor->id,
            'type' => 'simple',
            'name' => ['en' => 'Global', 'ar' => 'G'],
            'description' => ['en' => '', 'ar' => ''],
            'sku' => 'SKU-GLOBAL',
            'slug' => 'global-p',
            'price' => 20,
            'is_active' => true,
            'is_approved' => true,
        ]);

        $egResp = $this->getJson('/api/products?country_id='.$eg->id);
        $egResp->assertOk();
        $ids = collect($egResp->json('data'))->pluck('id')->all();
        $this->assertContains($global->id, $ids);
        $this->assertNotContains($onlyKw->id, $ids);

        $kwResp = $this->getJson('/api/products?country_id='.$kw->id);
        $kwResp->assertOk();
        $kwIds = collect($kwResp->json('data'))->pluck('id')->all();
        $this->assertContains($onlyKw->id, $kwIds);
        $this->assertContains($global->id, $kwIds);
    }

    public function test_repository_filters_by_authenticated_user_country_when_no_filter_provided(): void
    {
        $vendor = Vendor::factory()->create();
        $kw = Country::factory()->create(['code' => 'KW', 'sort_order' => 1]);
        $eg = Country::factory()->create(['code' => 'EG', 'sort_order' => 2]);

        $onlyKw = Product::create([
            'vendor_id' => $vendor->id,
            'type' => 'simple',
            'name' => ['en' => 'KW Only', 'ar' => 'KW'],
            'description' => ['en' => '', 'ar' => ''],
            'sku' => 'SKU-KW-2',
            'slug' => 'kw-only-2',
            'price' => 10,
            'is_active' => true,
            'is_approved' => true,
        ]);
        $onlyKw->countries()->attach($kw->id);

        $global = Product::create([
            'vendor_id' => $vendor->id,
            'type' => 'simple',
            'name' => ['en' => 'Global 2', 'ar' => 'G2'],
            'description' => ['en' => '', 'ar' => ''],
            'sku' => 'SKU-GLOBAL-2',
            'slug' => 'global-p-2',
            'price' => 20,
            'is_active' => true,
            'is_approved' => true,
        ]);

        $user = User::factory()->create(['country_id' => $eg->id]);
        $this->actingAs($user);

        $repo = app(ProductRepository::class);
        $paginator = $repo->getPaginatedProducts(perPage: 100, filters: [
            'approved' => 1,
            'status' => 'active',
        ]);

        $ids = $paginator->getCollection()->pluck('id')->all();
        $this->assertContains($global->id, $ids);
        $this->assertNotContains($onlyKw->id, $ids);
    }
}
