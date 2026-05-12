<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Like4AppService
{
    /**
     * @return array<string, mixed>
     */
    public function checkBalance(): array
    {
        $config = config('services.like4app', []);

        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://taxes.like4app.com'), '/');
        $url = $baseUrl.'/online/check_balance';

        $deviceId = (string) ($config['device_id'] ?? '');
        $email = (string) ($config['email'] ?? '');
        $securityCode = (string) ($config['security_code'] ?? '');
        $langId = (string) ($config['lang_id'] ?? '1');
        $timeout = (int) ($config['timeout'] ?? 30);

        if ($deviceId === '' || $email === '' || $securityCode === '') {
            throw new Exception('Like4App credentials are missing (LIKE4APP_DEVICE_ID, LIKE4APP_EMAIL, LIKE4APP_SECURITY_CODE).');
        }

        /** @var \Illuminate\Http\Client\Response $response */
        $response = Http::asForm()
            ->acceptJson()
            ->timeout($timeout)
            ->retry(2, 300, throw: false)
            ->withHeaders([
                'Origin' => 'https://taxes.like4app.com',
                'Referer' => 'https://taxes.like4app.com/',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119 Safari/537.36',
            ])
            ->post($url, [
                'deviceId' => $deviceId,
                'email' => $email,
                'securityCode' => $securityCode,
                'langId' => $langId,
            ]);

        if ($response->failed()) {
            Log::error('Like4App check_balance failed', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            // Cloudflare protection sometimes blocks non-browser clients.
            // Fall back to the cURL approach used in existing Like4App sync commands.
            if ($this->looksLikeCloudflareBlock($response->body())) {
                return $this->checkBalanceViaCurl($url, [
                    'deviceId' => $deviceId,
                    'email' => $email,
                    'securityCode' => $securityCode,
                    'langId' => $langId,
                ], $timeout);
            }

            throw new Exception('Like4App check_balance request failed');
        }

        return (array) $response->json();
    }

    /**
     * Create an order for a Like4App product.
     *
     * @return array<string, mixed>
     */
    public function createOrder(int $productId, int $quantity = 1, ?string $paymentRef = null): array
    {
        $config = config('services.like4app', []);

        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://taxes.like4app.com'), '/');
        $endpoint = (string) ($config['create_order_endpoint'] ?? '/online/order');
        $url = $baseUrl.'/'.ltrim($endpoint, '/');

        $deviceId = (string) ($config['device_id'] ?? '');
        $email = (string) ($config['email'] ?? '');
        $securityCode = (string) ($config['security_code'] ?? '');
        $langId = (string) ($config['lang_id'] ?? '1');
        $timeout = (int) ($config['timeout'] ?? 30);

        if ($deviceId === '' || $email === '' || $securityCode === '') {
            throw new Exception('Like4App credentials are missing (LIKE4APP_DEVICE_ID, LIKE4APP_EMAIL, LIKE4APP_SECURITY_CODE).');
        }

        $payload = [
            'deviceId' => $deviceId,
            'email' => $email,
            'securityCode' => $securityCode,
            'langId' => $langId,
            'productId' => $productId,
            'qty' => $quantity,
        ];

        if ($paymentRef !== null) {
            $payload['payment_ref'] = $paymentRef;
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout($timeout)
            ->retry(2, 300, throw: false)
            ->withHeaders([
                'Origin' => 'https://taxes.like4app.com',
                'Referer' => 'https://taxes.like4app.com/',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119 Safari/537.36',
            ])
            ->post($url, $payload);

        /** @var \Illuminate\Http\Client\Response $response */
        if ($response->failed()) {
            Log::error('Like4App createOrder failed', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            throw new Exception('Like4App createOrder request failed');
        }

        return (array) $response->json();
    }

    protected function looksLikeCloudflareBlock(string $body): bool
    {
        $b = strtolower($body);

        return str_contains($b, 'cloudflare') || str_contains($b, 'attention required');
    }

    /**
     * @param  array{deviceId:string,email:string,securityCode:string,langId:string}  $postFields
     * @return array<string, mixed>
     */
    protected function checkBalanceViaCurl(string $url, array $postFields, int $timeout): array
    {
        $cookieFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'like4app_cookie_balance.txt';
        $ch = curl_init();
        $postBody = http_build_query($postFields);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postBody,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119 Safari/537.36',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_ENCODING => '',
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json, text/plain, */*',
                'Origin: https://taxes.like4app.com',
                'Referer: https://taxes.like4app.com/',
            ],
            CURLOPT_HEADER => true,
        ]);

        $rawResponse = curl_exec($ch);
        if ($rawResponse === false) {
            curl_close($ch);
            throw new Exception('Like4App cURL error: '.curl_error($ch));
        }

        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $responseBody = (string) substr($rawResponse, $headerSize);
        curl_close($ch);

        if ($statusCode >= 400) {
            Log::error('Like4App check_balance cURL failed', [
                'url' => $url,
                'status' => $statusCode,
                'response_body' => $responseBody,
            ]);
            throw new Exception('Like4App check_balance request failed');
        }

        try {
            $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new Exception('Like4App check_balance returned non-JSON response');
        }

        return is_array($decoded) ? $decoded : [];
    }
}
