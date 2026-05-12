<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardLike4AppBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_like4app_balance(): void
    {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        config()->set('services.like4app.base_url', 'https://taxes.like4app.com');
        config()->set('services.like4app.device_id', 'device');
        config()->set('services.like4app.email', 'test@example.com');
        config()->set('services.like4app.security_code', 'sec');
        config()->set('services.like4app.lang_id', 1);

        Http::fake([
            'https://taxes.like4app.com/online/check_balance' => Http::response([
                'response' => 1,
                'userId' => 99,
                'balance' => 123.45,
                'currency' => 'KWD',
            ], 200),
        ]);

        $resp = $this->actingAs($admin)->get('/admin/dashboard');
        $resp->assertOk();
        $resp->assertSee('LikeCard Balance');
        $resp->assertSee('123.45');
        $resp->assertSee('KWD');
    }
}
