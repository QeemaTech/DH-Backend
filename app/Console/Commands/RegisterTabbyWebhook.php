<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class RegisterTabbyWebhook extends Command
{
    protected $signature = 'tabby:webhook:register {--force : Register even if already registered}';

    protected $description = 'Register Tabby webhook URL for this merchant code';

    public function handle(): int
    {
        $baseUrl = rtrim((string) config('services.tabby.base_url', 'https://api.tabby.ai'), '/');
        $secretKey = (string) config('services.tabby.secret_key');
        $merchantCode = (string) config('services.tabby.merchant_code');
        $webhookUrl = (string) config('services.tabby.webhook_url');
        $timeout = (int) config('services.tabby.timeout', 30);

        if ($secretKey === '' || $merchantCode === '' || $webhookUrl === '') {
            $this->error('Missing TABBY_SECRET_KEY / TABBY_MERCHANT_CODE / TABBY_WEBHOOK_URL.');

            return self::FAILURE;
        }

        $headerName = (string) config('services.tabby.webhook.header_name');
        $headerValue = (string) config('services.tabby.webhook.header_value');

        $payload = [
            'url' => $webhookUrl,
        ];

        if ($headerName !== '' && $headerValue !== '') {
            $payload['header'] = [
                'title' => $headerName,
                'value' => $headerValue,
            ];
        }

        $url = $baseUrl.'/api/v1/webhooks';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::acceptJson()
            ->withToken($secretKey)
            ->withHeaders(['X-Merchant-Code' => $merchantCode])
            ->timeout($timeout)
            ->retry(2, 300, throw: false)
            ->post($url, $payload);

        if ($response->failed()) {
            $this->error('Tabby webhook registration failed: '.$response->body());

            return self::FAILURE;
        }

        $json = (array) $response->json();

        $this->info('Tabby webhook registered.');
        $this->line('id: '.(string) data_get($json, 'id'));
        $this->line('url: '.(string) data_get($json, 'url'));
        $this->line('is_test: '.(string) data_get($json, 'is_test'));

        return self::SUCCESS;
    }
}
