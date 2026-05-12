<?php

namespace Tests\Feature;

use App\Mail\DigitalOrderIpConfirmationMail;
use App\Models\Country;
use App\Models\DigitalCategory;
use App\Models\DigitalMerchant;
use App\Models\DigitalOrder;
use App\Models\DigitalProduct;
use App\Models\DigitalSubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DigitalOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_create_digital_order_when_user_profile_incomplete(): void
    {
        Mail::fake();

        $country = Country::factory()->create(['code' => 'KW', 'sort_order' => 1, 'name' => ['en' => 'Kuwait', 'ar' => 'KW']]);
        /** @var User $user */
        $user = User::factory()->create([
            'country_id' => $country->id,
            'gender' => null,
            'birth_date' => null,
            'national_number' => null,
            'national_cart_front_image' => null,
            'national_cart_back_image' => null,
            'national_id_expire_date' => null,
            'home_address' => null,
        ]);

        $merchant = DigitalMerchant::create([
            'merchant_id' => 'M-DO-1',
            'company_name' => 'TestCo',
            'name' => ['en' => 'Merchant', 'ar' => 'Merchant'],
        ]);
        $category = DigitalCategory::create([
            'name' => ['en' => 'Cat', 'ar' => 'Cat'],
            'slug' => 'cat-do',
            'visits' => 0,
            'is_active' => true,
            'last_update_by' => $user->id,
        ]);
        $subCategory = DigitalSubCategory::create([
            'name' => ['en' => 'Sub', 'ar' => 'Sub'],
            'slug' => 'sub-do',
            'visits' => 0,
            'is_active' => true,
            'digital_category_id' => $category->id,
            'last_update_by' => $user->id,
        ]);
        $digitalProduct = DigitalProduct::create([
            'product_id' => 'DP-DO-1',
            'company_name' => 'TestCo',
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => ['en' => 'DP', 'ar' => 'DP'],
            'slug' => 'dp-do',
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

        $this->actingAs($user);

        $resp = $this->postJson('/api/digital-orders', [
            'digital_product_id' => $digitalProduct->id,
        ]);

        $resp->assertStatus(422);
        $this->assertDatabaseCount('digital_orders', 0);
        Mail::assertNothingSent();
    }

    public function test_can_create_single_digital_product_order_and_sends_ip_confirmation_mail(): void
    {
        Mail::fake();

        $country = Country::factory()->create(['code' => 'KW', 'sort_order' => 1, 'name' => ['en' => 'Kuwait', 'ar' => 'KW']]);
        /** @var User $user */
        $user = User::factory()->create([
            'country_id' => $country->id,
            'phone' => '555',
            'gender' => 'male',
            'birth_date' => now()->subYears(20)->toDateString(),
            'national_number' => '123',
            'national_cart_front_image' => 'front.png',
            'national_cart_back_image' => 'back.png',
            'national_id_expire_date' => now()->addYear()->toDateString(),
            'home_address' => 'Somewhere',
        ]);

        $merchant = DigitalMerchant::create([
            'merchant_id' => 'M-DO-2',
            'company_name' => 'TestCo',
            'name' => ['en' => 'Merchant', 'ar' => 'Merchant'],
        ]);
        $category = DigitalCategory::create([
            'name' => ['en' => 'Cat', 'ar' => 'Cat'],
            'slug' => 'cat-do-2',
            'visits' => 0,
            'is_active' => true,
            'last_update_by' => $user->id,
        ]);
        $subCategory = DigitalSubCategory::create([
            'name' => ['en' => 'Sub', 'ar' => 'Sub'],
            'slug' => 'sub-do-2',
            'visits' => 0,
            'is_active' => true,
            'digital_category_id' => $category->id,
            'last_update_by' => $user->id,
        ]);
        $digitalProduct = DigitalProduct::create([
            'product_id' => 'DP-DO-2',
            'company_name' => 'TestCo',
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => ['en' => 'DP', 'ar' => 'DP'],
            'slug' => 'dp-do-2',
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

        $this->actingAs($user);

        $resp = $this->postJson('/api/digital-orders', [
            'digital_product_id' => $digitalProduct->id,
        ]);

        $resp->assertOk();
        $orderId = (int) $resp->json('order_id');
        $this->assertDatabaseHas('digital_orders', ['id' => $orderId, 'user_id' => $user->id]);

        $order = DigitalOrder::query()->with('items')->findOrFail($orderId);
        $this->assertCount(1, $order->items);
        $this->assertSame($digitalProduct->id, (int) $order->items->first()->digital_product_id);

        Mail::assertSent(DigitalOrderIpConfirmationMail::class, function (DigitalOrderIpConfirmationMail $mail) use ($orderId) {
            return (int) $mail->digitalOrder->id === $orderId && str_contains($mail->signedUrl, '/api/digital-orders/'.$orderId.'/capture-ip');
        });
    }

    public function test_can_place_provider_order_in_test_mode_for_eezee_provider(): void
    {
        if (! app()->environment('local') && ! config('app.debug')) {
            $this->markTestSkipped('Provider-order test endpoint is disabled outside local/debug.');
        }

        Mail::fake();
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.example/api/v1');

        $country = Country::factory()->create(['code' => 'KW', 'sort_order' => 1, 'name' => ['en' => 'Kuwait', 'ar' => 'KW']]);
        /** @var User $user */
        $user = User::factory()->create([
            'country_id' => $country->id,
            'phone' => '555',
            'gender' => 'male',
            'birth_date' => now()->subYears(20)->toDateString(),
            'national_number' => '123',
            'national_cart_front_image' => 'front.png',
            'national_cart_back_image' => 'back.png',
            'national_id_expire_date' => now()->addYear()->toDateString(),
            'home_address' => 'Somewhere',
        ]);

        $merchant = DigitalMerchant::create([
            'merchant_id' => 'M-DO-EEZEE',
            'company_name' => 'Eezee Pay',
            'name' => ['en' => 'Merchant', 'ar' => 'Merchant'],
        ]);
        $category = DigitalCategory::create([
            'name' => ['en' => 'Cat', 'ar' => 'Cat'],
            'slug' => 'cat-do-eezee',
            'visits' => 0,
            'is_active' => true,
            'last_update_by' => $user->id,
        ]);
        $subCategory = DigitalSubCategory::create([
            'name' => ['en' => 'Sub', 'ar' => 'Sub'],
            'slug' => 'sub-do-eezee',
            'visits' => 0,
            'is_active' => true,
            'digital_category_id' => $category->id,
            'last_update_by' => $user->id,
        ]);

        $digitalProduct = DigitalProduct::create([
            'product_id' => 123,
            'company_name' => 'Eezee Pay',
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => ['en' => 'DP', 'ar' => 'DP'],
            'slug' => 'dp-do-eezee',
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

        $this->actingAs($user);

        $create = $this->postJson('/api/digital-orders', [
            'digital_product_id' => $digitalProduct->id,
        ]);
        $create->assertOk();

        $orderId = (int) $create->json('order_id');
        $order = DigitalOrder::query()->findOrFail($orderId);

        $resp = $this->postJson('/api/digital-orders/'.$order->id.'/provider-order-test');
        $resp->assertOk();
        $resp->assertJsonPath('order_id', $order->id);
        $resp->assertJsonStructure(['provider_response']);
    }

    public function test_can_place_provider_order_in_test_mode_for_onecard_provider(): void
    {
        if (! app()->environment('local') && ! config('app.debug')) {
            $this->markTestSkipped('Provider-order test endpoint is disabled outside local/debug.');
        }

        Mail::fake();
        Http::fake([
            '*' => Http::response(['transaction' => ['status' => 'success']], 200),
        ]);

        config()->set('services.onecard.base_url', 'https://bbapi.ocstaging.net');
        config()->set('services.onecard.reseller_username', 'reseller');
        config()->set('services.onecard.secret_key', 'secret');
        config()->set('services.onecard.terminal_id', 'T-1');

        $country = Country::factory()->create(['code' => 'KW', 'sort_order' => 1, 'name' => ['en' => 'Kuwait', 'ar' => 'KW']]);
        /** @var User $user */
        $user = User::factory()->create([
            'country_id' => $country->id,
            'phone' => '555',
            'gender' => 'male',
            'birth_date' => now()->subYears(20)->toDateString(),
            'national_number' => '123',
            'national_cart_front_image' => 'front.png',
            'national_cart_back_image' => 'back.png',
            'national_id_expire_date' => now()->addYear()->toDateString(),
            'home_address' => 'Somewhere',
        ]);

        $merchant = DigitalMerchant::create([
            'merchant_id' => 'M-DO-ONECARD',
            'company_name' => 'one_card',
            'name' => ['en' => 'Merchant', 'ar' => 'Merchant'],
        ]);
        $category = DigitalCategory::create([
            'name' => ['en' => 'Cat', 'ar' => 'Cat'],
            'slug' => 'cat-do-onecard',
            'visits' => 0,
            'is_active' => true,
            'last_update_by' => $user->id,
        ]);
        $subCategory = DigitalSubCategory::create([
            'name' => ['en' => 'Sub', 'ar' => 'Sub'],
            'slug' => 'sub-do-onecard',
            'visits' => 0,
            'is_active' => true,
            'digital_category_id' => $category->id,
            'last_update_by' => $user->id,
        ]);

        $digitalProduct = DigitalProduct::create([
            'product_id' => '999',
            'company_name' => 'one_card',
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => ['en' => 'DP', 'ar' => 'DP'],
            'slug' => 'dp-do-onecard',
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

        $this->actingAs($user);

        $create = $this->postJson('/api/digital-orders', [
            'digital_product_id' => $digitalProduct->id,
        ]);
        $create->assertOk();

        $orderId = (int) $create->json('order_id');
        $order = DigitalOrder::query()->findOrFail($orderId);

        $resp = $this->postJson('/api/digital-orders/'.$order->id.'/provider-order-test');
        $resp->assertOk();

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return str_contains($request->url(), '/integration/purchase-product')
                && $request['resellerUsername'] === 'reseller'
                && isset($request['password'])
                && $request['productID'] === '999'
                && isset($request['resellerRefNumber'])
                && $request['terminalID'] === 'T-1';
        });
    }
}
