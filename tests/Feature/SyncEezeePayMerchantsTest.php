<?php

namespace Tests\Feature;

use App\Models\DigitalMerchant;
use App\Services\EezeePayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncEezeePayMerchantsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if ($this->app->bound(EezeePayService::class)) {
            $this->app->make(EezeePayService::class)->forgetRememberedToken();
        }

        parent::tearDown();
    }

    public function test_sync_persists_categories_as_digital_merchants_with_parent_links(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.token', 'tok');
        config()->set('services.eezee_pay.company_name', 'eezee_pay');

        Http::fake(function ($request) {
            $this->assertStringContainsString('/categories', (string) $request->url());

            return Http::response([
                'data' => [
                    [
                        'id' => 10,
                        'name' => 'IgnoredWhenPathPresent',
                        'path_en' => 'Root EN',
                        'path_ar' => 'Root AR',
                    ],
                    [
                        'id' => 20,
                        'name' => 'Ignored',
                        'path_en' => 'Child EN',
                        'path_ar' => 'Child AR',
                        'parent_id' => 10,
                    ],
                ],
            ], 200);
        });

        $this->artisan('merchants:sync-eezeepay')->assertExitCode(0);

        $parent = DigitalMerchant::query()
            ->where('merchant_id', '10')
            ->where('company_name', 'eezee_pay')
            ->first();
        $child = DigitalMerchant::query()
            ->where('merchant_id', '20')
            ->where('company_name', 'eezee_pay')
            ->first();

        $this->assertNotNull($parent);
        $this->assertNotNull($child);
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertSame('Root EN', $parent->getTranslation('name', 'en'));
        $this->assertSame('Root AR', $parent->getTranslation('name', 'ar'));
        $this->assertSame('Child EN', $child->getTranslation('name', 'en'));
        $this->assertSame('Child AR', $child->getTranslation('name', 'ar'));
    }

    public function test_name_falls_back_when_path_fields_missing(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.token', 'tok');
        config()->set('services.eezee_pay.company_name', 'eezee_pay');

        Http::fake([
            'https://sandbox.eezee-pay.com/api/v1/categories*' => Http::response([
                'data' => [
                    ['id' => 5, 'name' => 'Fallback Name'],
                ],
            ], 200),
        ]);

        $this->artisan('merchants:sync-eezeepay')->assertExitCode(0);

        $row = DigitalMerchant::query()
            ->where('merchant_id', '5')
            ->where('company_name', 'eezee_pay')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('Fallback Name', $row->getTranslation('name', 'en'));
        $this->assertSame('Fallback Name', $row->getTranslation('name', 'ar'));
    }

    public function test_dry_run_does_not_write_rows(): void
    {
        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.com/api/v1');
        config()->set('services.eezee_pay.token', 'tok');
        config()->set('services.eezee_pay.company_name', 'eezee_pay');

        Http::fake([
            'https://sandbox.eezee-pay.com/api/v1/categories*' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'Only'],
                ],
            ], 200),
        ]);

        $this->artisan('merchants:sync-eezeepay', ['--dry-run' => true])->assertExitCode(0);

        $this->assertSame(0, DigitalMerchant::query()->count());
    }
}
