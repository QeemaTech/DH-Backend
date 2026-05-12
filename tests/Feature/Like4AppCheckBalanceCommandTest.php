<?php

namespace Tests\Feature;

use App\Services\Like4AppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Like4AppCheckBalanceCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_checks_like4app_balance(): void
    {
        config()->set('services.like4app.base_url', 'https://taxes.like4app.com');
        config()->set('services.like4app.device_id', 'device');
        config()->set('services.like4app.email', 'test@example.com');
        config()->set('services.like4app.security_code', 'sec');
        config()->set('services.like4app.lang_id', 1);

        Http::fake(function ($request) {
            $this->assertSame('https://taxes.like4app.com/online/check_balance', (string) $request->url());
            $data = $request->data();
            $this->assertSame('device', $data['deviceId'] ?? null);
            $this->assertSame('test@example.com', $data['email'] ?? null);
            $this->assertSame('sec', $data['securityCode'] ?? null);
            $this->assertSame('1', (string) ($data['langId'] ?? ''));

            return Http::response([
                'response' => 1,
                'userId' => 99,
                'balance' => 123.45,
                'currency' => 'KWD',
            ], 200);
        });

        $resp = $this->app->make(Like4AppService::class)->checkBalance();
        $this->assertSame(99, data_get($resp, 'userId'));

        $this->artisan('like4app:check-balance')
            ->assertExitCode(0);
    }
}
