<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OneCardService
{
    /**
     * Purchase a digital product from OneCard and receive transaction details.
     *
     * Endpoint: POST /integration/purchase-product
     *
     * Required params:
     * - resellerUsername
     * - password (MD5 hash)
     * - productID
     * - resellerRefNumber
     * - terminalID
     *
     * @return array<string, mixed>
     */
    public function purchaseProduct(string $productId, string $resellerRefNumber, ?string $terminalId = null): array
    {
        $config = (array) config('services.onecard', []);
        $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $endpoint = (string) ($config['purchase_product_endpoint'] ?? '/integration/purchase-product');
        $timeout = (int) ($config['timeout'] ?? 30);
        $resellerUsername = (string) ($config['reseller_username'] ?? '');
        $secretKey = (string) ($config['secret_key'] ?? '');
        $resolvedTerminalId = $terminalId ?? (string) ($config['terminal_id'] ?? '');
        $logRequests = (bool) ($config['log_requests'] ?? false);

        if ($baseUrl === '') {
            throw new RuntimeException('OneCard base URL is missing (ONECARD_BASE_URL).');
        }
        if ($resellerUsername === '' || $secretKey === '') {
            throw new RuntimeException('OneCard credentials are missing (ONECARD_RESELLER_USERNAME, ONECARD_SECRET_KEY).');
        }
        if (trim($productId) === '') {
            throw new RuntimeException('OneCard productID is missing.');
        }
        if (trim($resellerRefNumber) === '') {
            throw new RuntimeException('OneCard resellerRefNumber is missing.');
        }
        if (trim($resolvedTerminalId) === '') {
            throw new RuntimeException('OneCard terminalID is missing (ONECARD_TERMINAL_ID).');
        }

        // Purchase-product password hash used by legacy integration.
        $hashedPassword = md5($resellerUsername . $productId . $resellerRefNumber . $secretKey);

        $url = $baseUrl.'/'.ltrim($endpoint, '/');

        $body = [
            'resellerUsername' => $resellerUsername,
            'password' => $hashedPassword,
            'productID' => $productId,
            'resellerRefNumber' => $resellerRefNumber,
            'terminalID' => $resolvedTerminalId,
        ];

        if ($logRequests) {
            Log::info('OneCard purchase-product request', [
                'url' => $url,
                'body' => array_merge($body, ['password' => '[hidden]']),
            ]);
        }

        $response = Http::acceptJson()
            ->asJson()
            ->timeout($timeout)
            ->retry(2, 300, throw: false)
            ->post($url, $body);

        /** @var \Illuminate\Http\Client\Response $response */
        if ($response->failed()) {
            Log::warning('OneCard purchase-product failed', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('OneCard purchase-product request failed.');
        }

        $json = $response->json();
        $normalized = is_array($json) ? $json : [];

        // OneCard sometimes returns HTTP 200 with status=false in payload (or a single-item list).
        $payload = $normalized;
        if (array_is_list($normalized) && isset($normalized[0]) && is_array($normalized[0])) {
            $payload = $normalized[0];
        }

        $status = data_get($payload, 'status');
        if ($status === false || $status === 'false' || $status === 0 || $status === '0') {
            $msg = (string) (data_get($payload, 'errorMessage')
                ?? data_get($payload, 'errorDesc')
                ?? 'OneCard purchase-product returned status=false.');

            Log::warning('OneCard purchase-product returned status=false', [
                'url' => $url,
                'payload' => $payload,
            ]);

            throw new RuntimeException($msg);
        }

        return $normalized;
    }
}

// {
//     "message": "Provider order placed (test mode).",
//     "order_id": 1,
//     "provider_response": {
//         "requestSrvTime": "2026-04-15 11:27:46",
//         "responseSrvTime": "2026-04-15 11:27:46",
//         "status": true,
//         "purchasingDate": "2026-04-15 11:27:46",
//         "bbTrxRefNumber": "1776263266778316578",
//         "resellerRefNumber": "TEST-DIGITAL-ORDER-1-5555",
//         "costPriceBeforeVat": 10,
//         "costPriceVatAmount": 0,
//         "costPriceAfterVat": 10,
//         "recommendedRetailPriceBeforeVat": 9,
//         "recommendedRetailPriceVatAmount": 0,
//         "recommendedRetailPriceAfterVat": 9,
//         "balance": 99858.96671999992,
//         "currency": "USD",
//         "productType": 4,
//         "serial": "2503-1776263266761",
//         "pin": "WP-SH-9USD-xafmnJHgqH",
//         "image": "https://src.ocstaging.net/opt/tmp/onecard/images/product/bitaqty_image/2503",
//         "isQrCode": false
//     }
// }
