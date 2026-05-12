<?php

namespace Tests\Unit;

use App\Services\SadadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SadadServiceHttpErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_configured_access_token_without_calling_token_endpoints(): void
    {
        config()->set('services.sadad.access_token', 'test-access-token');

        Http::fake();

        $service = new SadadService;
        $token = $service->getAccessToken();

        $this->assertSame('test-access-token', $token);
        Http::assertNothingSent();
    }

    public function test_refresh_token_http_500_does_not_throw_request_exception(): void
    {
        config()->set('services.sadad.base_url', 'https://apisandbox.sadadpay.net/api');
        config()->set('services.sadad.access_token', '');
        config()->set('services.sadad.token', 'dummy-basic-token');

        Http::fake([
            'https://apisandbox.sadadpay.net/api/User/GenerateRefreshToken' => Http::response('sadad down', 500),
        ]);

        $service = new SadadService;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to get refresh token');

        $service->getAccessToken();
    }

    public function test_it_derives_basic_token_from_client_credentials_when_token_missing(): void
    {
        config()->set('services.sadad.base_url', 'https://apisandbox.sadadpay.net/api');
        config()->set('services.sadad.access_token', '');
        config()->set('services.sadad.token', '');
        config()->set('services.sadad.client_key', 'client-key');
        config()->set('services.sadad.client_secret', 'client-secret');

        $expected = 'Basic '.base64_encode('client-key:client-secret');

        Http::fake(function ($request) use ($expected) {
            $this->assertSame('POST', $request->method());
            $this->assertSame('https://apisandbox.sadadpay.net/api/User/GenerateRefreshToken', (string) $request->url());
            $this->assertSame($expected, $request->header('Authorization')[0] ?? null);

            return Http::response('sadad down', 500);
        });

        $service = new SadadService;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to get refresh token');

        $service->getAccessToken();
    }
}
