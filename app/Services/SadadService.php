<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Sadad\Library\SadadLibrary;

class SadadService
{
    protected string $baseUrl;

    protected string $refreshTokenEndpoint;

    protected string $accessTokenEndpoint;

    protected string $createInvoiceEndpoint;

    protected string $getInvoiceEndpoint;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.sadad.base_url', 'https://apisandbox.sadadpay.net/api'), '/');
        $this->refreshTokenEndpoint = (string) config('services.sadad.refresh_token_endpoint', env('SADAD_REFRESH_TOKEN_ENDPOINT', '/User/GenerateRefreshToken'));
        $this->accessTokenEndpoint = (string) config('services.sadad.access_token_endpoint', env('SADAD_ACCESS_TOKEN_ENDPOINT', '/User/GenerateAccessToken'));
        $this->createInvoiceEndpoint = (string) config('services.sadad.create_invoice_endpoint', '/Invoice/insert');
        $this->getInvoiceEndpoint = (string) config('services.sadad.get_invoice_endpoint', '/Invoice/getbyid');
    }

    public function getAccessToken(): string
    {
        $configuredAccessToken = trim((string) config('services.sadad.access_token'));
        if ($configuredAccessToken !== '') {
            // Useful for sandbox/testing where token endpoints are unstable.
            return $configuredAccessToken;
        }

        // Sadad auth (per legacy integration): Basic token -> refreshToken -> accessToken
        $basicToken = $this->getBasicToken();

        $cacheKey = 'sadad_access_token:'.md5($this->baseUrl.'|'.$basicToken);

        return Cache::remember($cacheKey, 3500, function () {
            $basicToken = $this->getBasicToken();

            // 1) Refresh token
            $refreshUrl = $this->baseUrl.'/'.ltrim($this->refreshTokenEndpoint, '/');

            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::acceptJson()
                ->timeout(10)
                ->retry(2, 200, throw: false)
                ->withHeaders([
                    'Authorization' => 'Basic '.$basicToken,
                ])
                ->post($refreshUrl, []);

            if ($response->failed()) {
                Log::error('Sadad refresh token error', [
                    'url' => $refreshUrl,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new Exception('Failed to get refresh token');
            }

            $refreshToken = data_get($response->json(), 'response.refreshToken');

            if (! $refreshToken) {
                Log::error('Sadad refresh token missing in response', [
                    'url' => $refreshUrl,
                    'response' => $response->json(),
                ]);
                throw new Exception('Invalid refresh token response from Sadad');
            }

            // 2) Access token
            $accessUrl = $this->baseUrl.'/'.ltrim($this->accessTokenEndpoint, '/');

            /** @var \Illuminate\Http\Client\Response $accessResponse */
            $accessResponse = Http::acceptJson()
                ->timeout(10)
                ->retry(2, 200, throw: false)
                ->withToken((string) $refreshToken)
                ->post($accessUrl, []);

            if ($accessResponse->failed()) {
                Log::error('Sadad access token error', [
                    'url' => $accessUrl,
                    'status' => $accessResponse->status(),
                    'response' => $accessResponse->body(),
                ]);
                throw new Exception('Failed to get access token');
            }

            $accessToken = data_get($accessResponse->json(), 'response.accessToken');
            if (! $accessToken) {
                Log::error('Sadad access token missing in response', [
                    'url' => $accessUrl,
                    'response' => $accessResponse->json(),
                ]);
                throw new Exception('Invalid access token response from Sadad');
            }

            return (string) $accessToken;
        });
    }

    protected function getBasicToken(): string
    {
        $token = trim((string) config('services.sadad.token'));

        // Some dashboards/docs include the "Basic " prefix; normalize to raw token.
        if ($token !== '' && str_starts_with(strtolower($token), 'basic ')) {
            $token = trim(substr($token, 6));
        }

        if ($token !== '') {
            return $token;
        }

        // Fallback: derive Basic token from client credentials.
        $clientKey = trim((string) config('services.sadad.client_key'));
        $clientSecret = trim((string) config('services.sadad.client_secret'));

        if ($clientKey === '' || $clientSecret === '') {
            throw new Exception('Sadad credentials are missing. Set SADAD_TOKEN or SADAD_CLIENT_KEY / SADAD_CLIENT_SECRET.');
        }

        return base64_encode($clientKey.':'.$clientSecret);
    }

    public function createInvoice(array $data): array
    {
        if ($this->useSdk()) {
            return $this->createInvoiceWithSdk($data);
        }

        return $this->createInvoiceWithHttp($data);
    }

    protected function createInvoiceWithHttp(array $data): array
    {
        $token = $this->getAccessToken();

        // Sadad API expects "Invoices" (capital I). Accept both inputs.
        if (isset($data['invoices']) && ! isset($data['Invoices'])) {
            $data = ['Invoices' => $data['invoices']];
        }

        if ((bool) config('services.sadad.log_requests', false)) {
            Log::info('Sadad create invoice request', [
                'url' => $this->baseUrl.'/'.ltrim($this->createInvoiceEndpoint, '/'),
                'payload' => $this->sanitizeForLog($data),
            ]);
        }

        $url = $this->baseUrl.'/'.ltrim($this->createInvoiceEndpoint, '/');
        $jsonBody = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        if ($jsonBody === false) {
            throw new Exception('Failed to encode Sadad invoice payload to JSON');
        }

        if ((bool) config('services.sadad.log_requests', false)) {
            $pretty = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_PRESERVE_ZERO_FRACTION);
            Log::info('Sadad exact JSON', ['body' => $pretty === false ? $jsonBody : $pretty]);
        }

        $timeout = (int) config('services.sadad.timeout', 30);

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withToken($token)
            ->timeout($timeout)
            ->retry(2, 300, throw: false)
            // Match legacy integration: send raw JSON with application/*+json
            ->withHeaders(['Content-Type' => 'application/*+json'])
            ->withBody($jsonBody, 'application/*+json')
            ->post($url);

        if ($response->failed()) {

            Log::error('Sadad create invoice failed', [
                'url' => $url,
                'status' => $response->status(),
                'payload' => (bool) config('services.sadad.log_requests', false) ? $this->sanitizeForLog($data) : null,
                'response' => $response->body(),
            ]);

            throw new Exception('Failed to create invoice');
        }

        $json = $response->json();
        Log::info('Sadad create invoice response', [
            'status' => $response->status(),
            'json' => $json,
        ]);

        return $json;
    }

    protected function useSdk(): bool
    {
        return strtolower((string) config('services.sadad.driver', 'http')) === 'sdk';
    }

    /**
     * SDK path (sadad-payment/library) – preferred in test phase.
     *
     * The SDK expects request key "Invoices" (capital I) not "invoices".
     */
    protected function createInvoiceWithSdk(array $data): array
    {
        $sadad = $this->makeSdk();
        $refreshToken = $this->getSdkRefreshToken($sadad);

        // Convert our lower-case payload to SDK shape if needed.
        if (isset($data['invoices']) && ! isset($data['Invoices'])) {
            $data = ['Invoices' => $data['invoices']];
        }

        if ((bool) config('services.sadad.log_requests', false)) {
            Log::info('Sadad SDK createInvoice request', [
                'payload' => $this->sanitizeForLog($data),
            ]);
        }

        try {
            $sdkResp = $sadad->createInvoice($data, $refreshToken);
        } catch (Exception $e) {
            Log::error('Sadad SDK createInvoice failed', [
                'error' => $e->getMessage(),
                'payload' => (bool) config('services.sadad.log_requests', false) ? $this->sanitizeForLog($data) : null,
            ]);

            if ((bool) config('services.sadad.sdk_fallback_http', true)) {
                Log::warning('Sadad SDK failed, falling back to HTTP implementation (test phase)', [
                    'error' => $e->getMessage(),
                ]);

                // Convert back to HTTP shape (lower-case invoices key)
                $httpPayload = $data;
                if (isset($httpPayload['Invoices']) && ! isset($httpPayload['invoices'])) {
                    $httpPayload = ['invoices' => $httpPayload['Invoices']];
                }

                return $this->createInvoiceWithHttp($httpPayload);
            }

            throw $e;
        }

        // Normalize response to match our existing consumers.
        return [
            'isValid' => true,
            'response' => [
                'invoiceId' => $sdkResp['InvoiceId'] ?? null,
                'invoiceURL' => $sdkResp['InvoiceURL'] ?? null,
            ],
            'sdk' => $sdkResp,
        ];
    }

    protected function makeSdk(): SadadLibrary
    {
        $clientId = (string) config('services.sadad.client_key');
        $clientSecret = (string) config('services.sadad.client_secret');

        if ($clientId === '' || $clientSecret === '') {
            throw new Exception('Sadad client credentials are missing (SADAD_CLIENT_KEY / SADAD_CLIENT_SECRET).');
        }

        $isTest = (bool) config('services.sadad.is_test', true);

        return new SadadLibrary([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'isTest' => $isTest,
            // You can set a log file path if desired:
            // 'log' => storage_path('logs/sadad-sdk.log'),
        ]);
    }

    protected function getSdkRefreshToken(SadadLibrary $sadad): string
    {
        $cacheKey = 'sadad_refresh_token:sdk:'.md5((string) config('services.sadad.client_key').'|'.((bool) config('services.sadad.is_test', true) ? 'test' : 'live'));

        return Cache::remember($cacheKey, 60 * 60 * 24 * 20, function () use ($sadad) {
            $sadad->generateRefreshToken();
            if (empty($sadad->refreshToken)) {
                throw new Exception('Failed to generate Sadad refresh token (SDK).');
            }

            return (string) $sadad->refreshToken;
        });
    }

    protected function sanitizeForLog(array $data): array
    {
        // Mask sensitive fields in payload
        $key = isset($data['invoices']) ? 'invoices' : (isset($data['Invoices']) ? 'Invoices' : null);

        if ($key && isset($data[$key][0]) && is_array($data[$key][0])) {
            $inv = $data[$key][0];
            foreach (['customer_Email', 'customer_Mobile'] as $k) {
                if (! empty($inv[$k]) && is_string($inv[$k])) {
                    $inv[$k] = substr($inv[$k], 0, 3).'***';
                }
            }
            if (! empty($inv['items']) && is_array($inv['items'])) {
                // keep items, but ensure prices/qty are visible
            }
            $data[$key][0] = $inv;
        }

        return $data;
    }

    public function getInvoice($id): array
    {
        $token = $this->getAccessToken();

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::withToken($token)
            ->timeout(10)
            ->retry(2, 200, throw: false)
            ->get($this->baseUrl.'/'.ltrim($this->getInvoiceEndpoint, '/'), [
                'id' => $id,
            ]);

        if ($response->failed()) {

            Log::error('Sadad get invoice failed', [
                'url' => $this->baseUrl.'/'.ltrim($this->getInvoiceEndpoint, '/'),
                'status' => $response->status(),
                'invoice_id' => $id,
                'response' => $response->body(),
            ]);

            throw new Exception('Failed to get invoice');
        }

        return $response->json();
    }
}
