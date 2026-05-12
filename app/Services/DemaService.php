<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DemaService
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createPurchase(array $payload): array
    {
        $baseUrl = rtrim((string) config('services.dema.base_url', 'https://sandbox-api.deema.me'), '/');
        $prefix = rtrim((string) config('services.dema.api_prefix', '/api/merchant/v1'), '/');
        $apiKey = (string) config('services.dema.api_key', '');
        $timeout = (int) config('services.dema.timeout', 30);

        if ($apiKey === '') {
            throw new Exception('Deema API key is missing (DEMA_API_KEY).');
        }

        $url = $baseUrl.$prefix.'/purchase';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::acceptJson()
            ->withHeaders([
                'Authorization' => 'Basic '.$apiKey,
            ])
            ->timeout($timeout)
            ->retry(2, 300, throw: false)
            ->post($url, $payload);

        if ($response->failed()) {
            /** @var array<string, mixed>|null $json */
            $json = $response->json();
            $message = is_array($json) ? (string) (data_get($json, 'message') ?? data_get($json, 'error') ?? '') : '';

            Log::error('Deema create purchase failed', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            if ($message !== '') {
                throw new Exception($message);
            }

            throw new Exception('Failed to create Deema purchase.');
        }

        /** @var array<string, mixed> */
        return (array) $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function getPurchaseStatus(string $orderReference): array
    {
        $baseUrl = rtrim((string) config('services.dema.base_url', 'https://sandbox-api.deema.me'), '/');
        $prefix = rtrim((string) config('services.dema.api_prefix', '/api/merchant/v1'), '/');
        $apiKey = (string) config('services.dema.api_key', '');
        $timeout = (int) config('services.dema.timeout', 30);

        if ($apiKey === '') {
            throw new Exception('Deema API key is missing (DEMA_API_KEY).');
        }

        $url = $baseUrl.$prefix.'/purchase/status';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::acceptJson()
            ->withHeaders([
                'Authorization' => 'Basic '.$apiKey,
            ])
            ->timeout($timeout)
            ->retry(2, 300, throw: false)
            ->get($url, ['order_reference' => $orderReference]);

        if ($response->failed()) {
            Log::error('Deema get purchase status failed', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            throw new Exception('Failed to get Deema purchase status.');
        }

        /** @var array<string, mixed> */
        return (array) $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelPurchase(int $purchaseId): array
    {
        $baseUrl = rtrim((string) config('services.dema.base_url', 'https://sandbox-api.deema.me'), '/');
        $prefix = rtrim((string) config('services.dema.api_prefix', '/api/merchant/v1'), '/');
        $apiKey = (string) config('services.dema.api_key', '');
        $timeout = (int) config('services.dema.timeout', 30);

        if ($apiKey === '') {
            throw new Exception('Deema API key is missing (DEMA_API_KEY).');
        }

        $url = $baseUrl.$prefix.'/purchase/'.$purchaseId.'/cancel';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::acceptJson()
            ->withHeaders([
                'Authorization' => 'Basic '.$apiKey,
            ])
            ->timeout($timeout)
            ->retry(2, 300, throw: false)
            ->put($url, []);

        if ($response->failed()) {
            Log::error('Deema cancel purchase failed', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            throw new Exception('Failed to cancel Deema purchase.');
        }

        /** @var array<string, mixed> */
        return (array) $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function refundPurchase(int $purchaseId, float $amount): array
    {
        $baseUrl = rtrim((string) config('services.dema.base_url', 'https://sandbox-api.deema.me'), '/');
        $prefix = rtrim((string) config('services.dema.api_prefix', '/api/merchant/v1'), '/');
        $apiKey = (string) config('services.dema.api_key', '');
        $timeout = (int) config('services.dema.timeout', 30);

        if ($apiKey === '') {
            throw new Exception('Deema API key is missing (DEMA_API_KEY).');
        }

        $url = $baseUrl.$prefix.'/purchase/'.$purchaseId.'/refund';

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::acceptJson()
            ->withHeaders([
                'Authorization' => 'Basic '.$apiKey,
            ])
            ->timeout($timeout)
            ->retry(2, 300, throw: false)
            ->put($url, ['amount' => $amount]);

        if ($response->failed()) {
            Log::error('Deema refund purchase failed', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            throw new Exception('Failed to refund Deema purchase.');
        }

        /** @var array<string, mixed> */
        return (array) $response->json();
    }
}
