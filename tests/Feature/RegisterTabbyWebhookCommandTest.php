<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegisterTabbyWebhookCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_tabby_webhook(): void
    {
        config()->set('services.tabby.base_url', 'https://api.tabby.ai');
        config()->set('services.tabby.secret_key', 'sk_test');
        config()->set('services.tabby.merchant_code', 'DHAE');
        config()->set('services.tabby.webhook_url', 'https://example.ngrok-free.app/api/tabby/webhook');
        config()->set('services.tabby.webhook.header_name', 'X-Tabby-Signature');
        config()->set('services.tabby.webhook.header_value', 'secret');

        Http::fake(function ($request) {
            $this->assertSame('https://api.tabby.ai/api/v1/webhooks', (string) $request->url());
            $this->assertSame('Bearer sk_test', $request->header('Authorization')[0] ?? null);
            $this->assertSame('DHAE', $request->header('X-Merchant-Code')[0] ?? null);

            $data = $request->data();
            $this->assertSame('https://example.ngrok-free.app/api/tabby/webhook', $data['url'] ?? null);
            $this->assertSame('X-Tabby-Signature', data_get($data, 'header.title'));
            $this->assertSame('secret', data_get($data, 'header.value'));

            return Http::response([
                'id' => 'wh_123',
                'url' => 'https://example.ngrok-free.app/api/tabby/webhook',
                'is_test' => true,
            ], 200);
        });

        $this->artisan('tabby:webhook:register')
            ->assertExitCode(0);
    }
}
