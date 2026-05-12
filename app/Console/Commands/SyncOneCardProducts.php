<?php

namespace App\Console\Commands;

use App\Models\DigitalMerchant;
use App\Models\DigitalProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncOneCardProducts extends Command
{
    protected $signature = 'products:sync-onecard {--merchant-id= : Sync products for a single external merchant id} {--dry-run : Fetch and validate without writing to database}';

    protected $description = 'Sync digital products from OneCard API for all synced merchants';

    public function handle(): int
    {
        $config = config('services.onecard', []);
        $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $endpoint = (string) ($config['products_endpoint'] ?? '/integration/detailed-products-list');
        $companyName = (string) ($config['company_name'] ?? 'one_card');
        $timeout = (int) ($config['timeout'] ?? 30);
        $resellerUsername = (string) ($config['reseller_username'] ?? '');
        $secretKey = (string) ($config['secret_key'] ?? '');
        $syncUserId = (int) ($config['sync_user_id'] ?? 1);
        $onlyMerchantExternalId = $this->option('merchant-id');

        if ($baseUrl === '') {
            $this->error('OneCard base URL is missing. Please set ONECARD_BASE_URL.');

            return self::FAILURE;
        }

        if ($resellerUsername === '' || $secretKey === '') {
            $this->error('OneCard credentials are missing. Please set ONECARD_RESELLER_USERNAME and ONECARD_SECRET_KEY.');

            return self::FAILURE;
        }

        if ($syncUserId <= 0) {
            $this->error('Invalid ONECARD_SYNC_USER_ID value.');

            return self::FAILURE;
        }

        $query = DigitalMerchant::query()->where('company_name', $companyName);
        if (! empty($onlyMerchantExternalId)) {
            $query->where('merchant_id', (string) $onlyMerchantExternalId);
        }
        $merchants = $query->get(['id', 'merchant_id', 'redeem_steps']);

        if ($merchants->isEmpty()) {
            $this->warn('No OneCard merchants found to sync products for.');

            return self::SUCCESS;
        }

        $url = $baseUrl.'/'.ltrim($endpoint, '/');
        $now = Carbon::now();
        $rows = [];
        $failedMerchants = 0;

        foreach ($merchants as $merchant) {
            $externalMerchantId = (string) $merchant->merchant_id;
            $hashedPassword = md5($resellerUsername.$externalMerchantId.$secretKey);

            $requestBody = json_encode([
                'resellerUsername' => $resellerUsername,
                'password' => $hashedPassword,
                'merchantId' => $externalMerchantId,
            ], JSON_THROW_ON_ERROR);

            try {
                $rawResponse = $this->callOneCard($url, $requestBody, $timeout);
                $payload = json_decode($rawResponse, true, 512, JSON_THROW_ON_ERROR);
                $products = $this->extractProducts($payload);
            } catch (\Throwable $e) {
                $failedMerchants++;
                Log::error('OneCard product sync failed for merchant', [
                    'merchant_id' => $externalMerchantId,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if (empty($products)) {
                continue;
            }

            $merchantRedeem = $this->jsonToArray($merchant->redeem_steps);
            $merchantHowTo = [
                'en' => (string) ($merchantRedeem['en'] ?? ''),
                'ar' => (string) ($merchantRedeem['ar'] ?? ''),
            ];

            foreach ($products as $product) {
                $productId = (string) ($product['productID'] ?? '');
                if ($productId === '') {
                    continue;
                }

                $nameEn = (string) ($product['nameEn'] ?? $product['nameAr'] ?? $productId);
                $nameAr = (string) ($product['nameAr'] ?? $nameEn);
                $howEn = (string) ($product['howToUseEn'] ?? $merchantHowTo['en']);
                $howAr = (string) ($product['howToUseAr'] ?? $merchantHowTo['ar']);
                $costAfterVat = $this->toFloat($product['costPriceAfterVat'] ?? $product['productPrice'] ?? 0);
                $sellPrice = $this->toFloat($product['sellPrice'] ?? $product['productPrice'] ?? $costAfterVat);
                $available = $this->toBool($product['available'] ?? true);
                $optionalFields = $this->toBool($product['optionalFieldsExist'] ?? false);
                $currency = (string) ($product['currency'] ?? '');
                $image = (string) ($product['image'] ?? '');

                // Store image locally if remote URL
                $storedImagePath = null;
                if ($image !== '' && Str::startsWith(strtolower($image), ['http://', 'https://'])) {
                    try {
                        $storedImagePath = $this->storeImageFromUrl($image, $productId);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to download product image', [
                            'product_id' => $productId,
                            'image_url' => $image,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $rows[] = [
                    'product_id' => $productId,
                    'company_name' => $companyName,
                    'merchant_id' => $merchant->id,
                    'category_id' => null,
                    'sub_category_id' => null,
                    'name' => json_encode(['en' => $nameEn, 'ar' => $nameAr], JSON_UNESCAPED_UNICODE),
                    'slug' => 'onecard-'.$companyName.'-'.$productId,
                    'description' => null,
                    'how_to_use' => json_encode(['en' => $howEn, 'ar' => $howAr], JSON_UNESCAPED_UNICODE),
                    'image' => $storedImagePath,
                    'cost_after_vat' => $costAfterVat,
                    'price' => $sellPrice,
                    'currency' => $currency !== '' ? $currency : null,
                    'is_active' => true,
                    'is_available' => $available,
                    'visits' => 0,
                    'optional_fields_exists' => $optionalFields,
                    'last_update_by' => $syncUserId,
                    'updated_at' => $now,
                    'created_at' => $now,
                ];
            }
        }

        if (empty($rows)) {
            $this->warn('No OneCard products found to sync.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run mode: no database changes were made.');
            $this->line('Validated products count: '.count($rows));
            $this->line('Failed merchants: '.$failedMerchants);

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($rows) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    DigitalProduct::upsert(
                        $chunk,
                        ['product_id', 'company_name'],
                        [
                            'merchant_id',
                            'name',
                            'slug',
                            'how_to_use',
                            'image',
                            'cost_after_vat',
                            'price',
                            'currency',
                            'is_available',
                            'optional_fields_exists',
                            'last_update_by',
                            'updated_at',
                        ]
                    );
                }
            });
        } catch (\Throwable $e) {
            $this->error('Failed to sync products into database: '.$e->getMessage());
            Log::error('OneCard product sync failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $this->info('OneCard products synced successfully.');
        $this->line('Total processed: '.count($rows));
        $this->line('Failed merchants: '.$failedMerchants);

        return self::SUCCESS;
    }

    protected function callOneCard(string $url, string $requestBody, int $timeout): string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: '.strlen($requestBody),
        ]);

        $rawResponse = curl_exec($ch);
        if ($rawResponse === false) {
            throw new \RuntimeException('cURL error: '.curl_error($ch));
        }

        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode >= 400) {
            throw new \RuntimeException("OneCard request failed with status {$statusCode}");
        }

        return $rawResponse;
    }

    protected function extractProducts(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['products']) && is_array($payload['products'])) {
            return $payload['products'];
        }

        if (isset($payload['data']['products']) && is_array($payload['data']['products'])) {
            return $payload['data']['products'];
        }

        if (isset($payload['data']) && is_array($payload['data']) && array_is_list($payload['data'])) {
            return $payload['data'];
        }

        return [];
    }

    /**
     * Download an image from a URL and store it on the public disk.
     * Returns relative storage path (e.g., digital-products/onecard-123-abcdef.jpg) or null on failure.
     */
    protected function storeImageFromUrl(string $url, string $productId): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119 Safari/537.36',
        ]);
        $binary = curl_exec($ch);
        if ($binary === false) {
            curl_close($ch);

            return null;
        }
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if ($status >= 400 || $binary === '') {
            return null;
        }

        $extension = match (true) {
            str_contains($contentType, 'image/jpeg') => 'jpg',
            str_contains($contentType, 'image/png') => 'png',
            str_contains($contentType, 'image/webp') => 'webp',
            str_contains($contentType, 'image/gif') => 'gif',
            default => pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg',
        };

        $fileName = 'onecard-'.$productId.'-'.Str::random(8).'.'.$extension;
        $path = 'digital-products/'.$fileName;

        $ok = Storage::disk('public')->put($path, $binary);

        return $ok ? $path : null;
    }

    protected function toFloat(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    protected function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'available'], true);
    }

    protected function jsonToArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
