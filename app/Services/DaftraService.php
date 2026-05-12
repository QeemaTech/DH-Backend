<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DaftraService
{
    protected $baseUrl = 'https://dh-kw.daftra.com/api2';

    protected string $apiKey;

    public function __construct()
    {
        // Use config/env at runtime (not in property initializer) to avoid "Constant expression" error
        $this->apiKey = (string) config('services.daftra.api_key', env('DAFTRA_API_KEY'));
    }

    public function getProductBySku($sku)
    {
        $response = Http::withHeaders([
            'Apikey' => $this->apiKey,
        ])->get($this->baseUrl.'/products', [
            'product_code' => $sku,
        ]);
        Log::info('Daftra response', ['response' => $response->json()]);
        /** @var \Illuminate\Http\Client\Response $response */
        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        return $data['data'][0]['Product'] ?? null;
    }
}
