<?php

namespace App\Console\Commands;

use App\Models\DigitalCategory;
use App\Models\DigitalMerchant;
use App\Models\DigitalProduct;
use App\Models\DigitalSubCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncLike4AppProducts extends Command
{
    protected $signature = 'products:sync-like4app {--merchant-id= : Sync products for a single external merchant/category id} {--dry-run : Fetch and validate without writing to database}';

    protected $description = 'Sync digital products from Like4App products API for all synced merchants';

    public function handle(): int
    {
        $config = config('services.like4app', []);
        $url = (string) ($config['products_url'] ?? 'https://taxes.like4app.com/online/products');
        $deviceId = (string) ($config['device_id'] ?? '');
        $email = (string) ($config['email'] ?? '');
        $securityCode = (string) ($config['security_code'] ?? '');
        $langId = (string) ($config['lang_id'] ?? '1');
        $companyName = (string) ($config['company_name'] ?? 'like card');
        $syncUserId = (int) ($config['sync_user_id'] ?? 1);
        $timeout = (int) ($config['timeout'] ?? 30);
        $onlyMerchantExternalId = (string) ($this->option('merchant-id') ?? '');

        if ($deviceId === '' || $email === '' || $securityCode === '') {
            $this->error('Like4App credentials are missing. Please set LIKE4APP_DEVICE_ID, LIKE4APP_EMAIL and LIKE4APP_SECURITY_CODE.');
            Log::error('Like4App products sync failed', ['error' => 'Missing required Like4App credentials']);

            return self::FAILURE;
        }

        if ($syncUserId <= 0) {
            $this->error('Invalid LIKE4APP_SYNC_USER_ID value.');

            return self::FAILURE;
        }

        $merchantsQuery = DigitalMerchant::query()->where('company_name', $companyName);
        if ($onlyMerchantExternalId !== '') {
            $merchantsQuery->where('merchant_id', $onlyMerchantExternalId);
        }

        $merchants = $merchantsQuery->get(['id', 'merchant_id', 'redeem_steps']);
        if ($merchants->isEmpty()) {
            $this->warn('No Like4App merchants found to sync products for.');

            return self::SUCCESS;
        }

        $defaultCategory = DigitalCategory::query()->firstOrCreate(
            ['slug' => 'like4app'],
            [
                'name' => ['en' => 'Like4App', 'ar' => 'Like4App'],
                'is_active' => true,
                'visits' => 0,
                'last_update_by' => $syncUserId,
            ]
        );

        $defaultSubCategory = DigitalSubCategory::query()->firstOrCreate(
            ['slug' => 'like4app-general'],
            [
                'name' => ['en' => 'General', 'ar' => 'General'],
                'digital_category_id' => $defaultCategory->id,
                'is_active' => true,
                'visits' => 0,
                'last_update_by' => $syncUserId,
            ]
        );

        $now = Carbon::now();
        $rows = [];
        $failedMerchants = 0;

        foreach ($merchants as $merchant) {
            $externalCategoryId = (string) $merchant->merchant_id;

            $postFields = [
                'deviceId' => $deviceId,
                'email' => $email,
                'securityCode' => $securityCode,
                'langId' => $langId,
                'categoryId' => $externalCategoryId,
            ];

            try {
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
                    ->post($url, $postFields);

                if ($response->failed()) {
                    throw new \RuntimeException("Like4App products request failed with status {$response->status()}");
                }

                $payload = $response->json();
                $products = $this->extractProducts($payload);
            } catch (\Throwable $e) {
                $failedMerchants++;
                Log::error('Like4App product sync failed for merchant', [
                    'merchant_id' => $externalCategoryId,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            if (empty($products)) {
                continue;
            }

            $merchantRedeem = is_array($merchant->redeem_steps) ? $merchant->redeem_steps : [];
            $merchantHowTo = [
                'en' => (string) ($merchantRedeem['en'] ?? ''),
                'ar' => (string) ($merchantRedeem['ar'] ?? ''),
            ];

            foreach ($products as $product) {
                if (! is_array($product)) {
                    continue;
                }

                $productId = (string) ($product['productId'] ?? $product['productID'] ?? $product['id'] ?? '');
                if ($productId === '') {
                    continue;
                }

                $name = (string) ($product['productName'] ?? $product['name'] ?? $product['title'] ?? $productId);
                $image = (string) ($product['productImage'] ?? $product['image'] ?? '');
                $available = $this->toBool($product['available'] ?? true);
                $optionalFields = $this->toBool($product['optionalFieldsExist'] ?? $product['optional_fields_exists'] ?? false);
                $currency = (string) ($product['productCurrency'] ?? $product['currency'] ?? '');
                $costAfterVat = $this->toFloat($product['productPrice'] ?? $product['costPriceAfterVat'] ?? 0);
                $sellPrice = $this->toFloat($product['sellPrice'] ?? $product['productPrice'] ?? $costAfterVat);

                $storedImagePath = null;
                if ($image !== '' && Str::startsWith(strtolower($image), ['http://', 'https://'])) {
                    try {
                        $storedImagePath = $this->storeImageFromUrl($image, $productId, $timeout);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to download Like4App product image', [
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
                    'category_id' => $defaultCategory->id,
                    'sub_category_id' => $defaultSubCategory->id,
                    'name' => json_encode(['en' => $name, 'ar' => $name], JSON_UNESCAPED_UNICODE),
                    'slug' => 'like4app-'.$companyName.'-'.$productId,
                    'description' => null,
                    'how_to_use' => json_encode($merchantHowTo, JSON_UNESCAPED_UNICODE),
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
            $this->warn('No Like4App products found to sync.');
            $this->line('Failed merchants: '.$failedMerchants);

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
            $this->error('Failed to sync Like4App products into database: '.$e->getMessage());
            Log::error('Like4App products sync failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $this->info('Like4App products synced successfully.');
        $this->line('Total processed: '.count($rows));
        $this->line('Failed merchants: '.$failedMerchants);

        return self::SUCCESS;
    }

    protected function extractProducts(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $data = $payload['data'] ?? null;
        if (is_array($data)) {
            return $data;
        }

        if (isset($payload['products']) && is_array($payload['products'])) {
            return $payload['products'];
        }

        return [];
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

    protected function storeImageFromUrl(string $url, string $productId, int $timeout): ?string
    {
        /** @var \Illuminate\Http\Client\Response $resp */
        $resp = Http::timeout($timeout)->get($url);
        if ($resp->failed()) {
            return null;
        }

        $binary = $resp->body();
        if ($binary === '') {
            return null;
        }

        $contentType = (string) ($resp->header('Content-Type') ?? '');
        $extension = match (true) {
            str_contains($contentType, 'image/jpeg') => 'jpg',
            str_contains($contentType, 'image/png') => 'png',
            str_contains($contentType, 'image/webp') => 'webp',
            str_contains($contentType, 'image/gif') => 'gif',
            default => 'jpg',
        };

        $fileName = 'like4app-'.$productId.'-'.Str::random(8).'.'.$extension;
        $path = 'digital-products/'.$fileName;
        $ok = Storage::disk('public')->put($path, $binary);

        return $ok ? $path : null;
    }
}
