<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TabbyService
{
    public function createCheckoutSession(array $payload): array
    {
        $baseUrl = rtrim((string) config('services.tabby.base_url', 'https://api.tabby.ai'), '/');
        $secretKey = (string) config('services.tabby.secret_key');
        $timeout = (int) config('services.tabby.timeout', 30);

        if ($secretKey === '') {
            throw new Exception('Tabby secret key is missing (TABBY_SECRET_KEY).');
        }

        $url = $baseUrl.'/api/v2/checkout';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::acceptJson()
            ->withToken($secretKey)
            ->timeout($timeout)
            ->retry(2, 300, throw: false)
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error('Tabby create checkout session failed', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            throw new Exception('Failed to create Tabby checkout session');
        }

        return (array) $response->json();
    }

    public function retrievePayment(string $paymentId): array
    {
        $baseUrl = rtrim((string) config('services.tabby.base_url', 'https://api.tabby.ai'), '/');
        $secretKey = (string) config('services.tabby.secret_key');
        $timeout = (int) config('services.tabby.timeout', 30);

        if ($secretKey === '') {
            throw new Exception('Tabby secret key is missing (TABBY_SECRET_KEY).');
        }

        $url = $baseUrl.'/api/v2/payments/'.urlencode($paymentId);

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::acceptJson()
            ->withToken($secretKey)
            ->timeout($timeout)
            ->retry(2, 300, throw: false)
            ->get($url);

        if ($response->failed()) {
            Log::error('Tabby retrieve payment failed', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            throw new Exception('Failed to retrieve Tabby payment');
        }

        return (array) $response->json();
    }
}
