<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardEezeePayBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_displays_eezeepay_balance(): void
    {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);

        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        config()->set('services.eezee_pay.base_url', 'https://sandbox.eezee-pay.example/api/v1');
        config()->set('services.eezee_pay.token', 'token');

        Http::fake([
            'https://sandbox.eezee-pay.example/api/v1/me/balance' => Http::response([
                'balance' => 456.78,
                'currency' => 'KWD',
            ], 200),
        ]);

        $resp = $this->actingAs($admin)->get('/admin/dashboard');
        $resp->assertOk();
        $resp->assertSee('EezeePay Balance');
        $resp->assertSee('456.78');
        $resp->assertSee('KWD');
    }
}
