<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\DigitalCategory;
use App\Models\DigitalMerchant;
use App\Models\DigitalOrder;
use App\Models\DigitalOrderItem;
use App\Models\DigitalProduct;
use App\Models\DigitalSubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalOrderModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_digital_order_relations_and_casts_work(): void
    {
        $country = Country::factory()->create(['code' => 'KW', 'sort_order' => 1, 'name' => ['en' => 'Kuwait', 'ar' => 'KW']]);
        $user = User::factory()->create([
            'country_id' => $country->id,
            'gender' => 'male',
            'birth_date' => now()->subYears(20)->toDateString(),
            'national_number' => '123',
            'national_cart_front_image' => 'front.png',
            'national_cart_back_image' => 'back.png',
            'national_id_expire_date' => now()->addYear()->toDateString(),
            'home_address' => 'Somewhere',
        ]);

        $merchant = DigitalMerchant::create([
            'merchant_id' => 'M-ORDER-1',
            'company_name' => 'TestCo',
            'name' => ['en' => 'Merchant', 'ar' => 'Merchant'],
        ]);
        $category = DigitalCategory::create([
            'name' => ['en' => 'Cat', 'ar' => 'Cat'],
            'slug' => 'cat-order',
            'visits' => 0,
            'is_active' => true,
            'last_update_by' => $user->id,
        ]);
        $subCategory = DigitalSubCategory::create([
            'name' => ['en' => 'Sub', 'ar' => 'Sub'],
            'slug' => 'sub-order',
            'visits' => 0,
            'is_active' => true,
            'digital_category_id' => $category->id,
            'last_update_by' => $user->id,
        ]);
        $digitalProduct = DigitalProduct::create([
            'product_id' => 'DP-ORDER-1',
            'company_name' => 'TestCo',
            'merchant_id' => $merchant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => ['en' => 'DP', 'ar' => 'DP'],
            'slug' => 'dp-order',
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

        $order = DigitalOrder::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_phone' => $user->phone ?? '000',
            'user_gender' => $user->gender ?? 'male',
            'user_birth_date' => $user->birth_date,
            'user_national_number' => $user->national_number ?? '123',
            'user_national_cart_front_image' => $user->national_cart_front_image ?? 'front.png',
            'user_national_cart_back_image' => $user->national_cart_back_image ?? 'back.png',
            'user_national_id_expire_date' => $user->national_id_expire_date,
            'user_home_address' => $user->home_address ?? 'Somewhere',
            'user_ip_address' => '127.0.0.1',
            'user_country' => $country->code,
            'payment_status' => 'pending',
            'status' => 'pending',
            'notes' => 'note',
            'total' => 10,
            'discount' => 0,
            'shipping_cost' => 0,
            'total_cost' => 10,
        ]);

        $item = DigitalOrderItem::create([
            'digital_order_id' => $order->id,
            'digital_product_id' => $digitalProduct->id,
            'price' => 10,
            'quantity' => 2,
            'total' => 20,
            'notes' => null,
        ]);

        $this->assertTrue($order->relationLoaded('items') === false);
        $this->assertSame($user->id, $order->user->id);
        $this->assertCount(1, $order->items);
        $this->assertSame($item->id, $order->items->first()->id);
        $this->assertSame($digitalProduct->id, $order->items->first()->digitalProduct->id);
        $this->assertSame('20.00', (string) $item->total);
    }
}
