<?php

namespace App\Console\Commands;

use App\Models\DigitalCategory;
use App\Models\DigitalMerchant;
use App\Models\DigitalProduct;
use App\Models\DigitalSubCategory;
use App\Models\User;
use App\Services\EezeePayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SyncEezeePayProducts extends Command
{
    protected $signature = 'products:sync-eezeepay {--merchant-id= : Sync products for a single external merchant/category id} {--dry-run : Fetch and validate without writing to database}';

    protected $description = 'Sync digital products from Eezee Pay API for synced merchants (categories)';

    public function handle(EezeePayService $eezeePay): int
    {
        $config = config('services.eezee_pay', []);
        $companyName = (string) ($config['company_name'] ?? 'eezee_pay');
        $syncUserId = (int) ($config['sync_user_id'] ?? 1);
        $onlyMerchantExternalId = (string) ($this->option('merchant-id') ?? '');

        if ($syncUserId <= 0) {
            $this->error('Invalid EEZEEP_SYNC_USER_ID value.');

            return self::FAILURE;
        }

        if (! User::query()->whereKey($syncUserId)->exists()) {
            $this->error("No user found with id {$syncUserId}. Set EEZEEP_SYNC_USER_ID to an existing user.");

            return self::FAILURE;
        }

        $merchantsQuery = DigitalMerchant::query()->where('company_name', $companyName);
        if ($onlyMerchantExternalId !== '') {
            $merchantsQuery->where('merchant_id', $onlyMerchantExternalId);
        }

        $merchants = $merchantsQuery->get(['id', 'merchant_id', 'redeem_steps']);
        if ($merchants->isEmpty()) {
            $this->warn('No Eezee Pay merchants found. Run merchants:sync-eezeepay first.');

            return self::SUCCESS;
        }

        $categorySlug = 'eezee-pay';
        $subCategorySlug = 'eezee-pay-general';

        $defaultCategory = DigitalCategory::query()->firstOrCreate(
            ['slug' => $categorySlug],
            [
                'name' => ['en' => 'Eezee Pay', 'ar' => 'Eezee Pay'],
                'is_active' => true,
                'visits' => 0,
                'last_update_by' => $syncUserId,
            ]
        );

        $defaultSubCategory = DigitalSubCategory::query()->firstOrCreate(
            ['slug' => $subCategorySlug],
            [
                'name' => ['en' => 'General', 'ar' => 'عام'],
                'digital_category_id' => $defaultCategory->id,
                'is_active' => true,
                'visits' => 0,
                'last_update_by' => $syncUserId,
            ]
        );

        $now = Carbon::now();
        $rows = [];
        $failedMerchants = 0;
        $timeout = (int) ($config['timeout'] ?? 30);

        foreach ($merchants as $merchant) {
            $externalCategoryId = (string) $merchant->merchant_id;

            try {
                $payload = $eezeePay->productsByCategory((int) $externalCategoryId);
            } catch (Throwable $e) {
                $failedMerchants++;
                Log::error('Eezee Pay product sync failed for merchant', [
                    'merchant_id' => $externalCategoryId,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            $products = $this->extractProducts($payload);
            Log::info('Eezee Pay products', ['products' => $products]);
            if ($products === []) {
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

                $productId = (string) $this->pick($product, ['id', 'product_id', 'productId', 'ProductId']);
                if ($productId === '') {
                    continue;
                }

                $nameStruct = $this->localizedField($product, [
                    'path',
                    'name',
                    'title',
                    'product_name',
                    'productName',
                ]) ?? ['en' => '', 'ar' => ''];

                if ($nameStruct['en'] === '' && $nameStruct['ar'] === '') {
                    $nameStruct = ['en' => $productId, 'ar' => $productId];
                }

                $description = $this->localizedField($product, ['description', 'desc', 'details']);

                $image = (string) ($this->pick($product, ['image', 'image_url', 'photo', 'thumbnail', 'icon']) ?? '');
                $storedImagePath = null;
                if ($image !== '' && Str::startsWith(strtolower($image), ['http://', 'https://'])) {
                    try {
                        $storedImagePath = $this->storeImageFromUrl($image, $productId, $timeout);
                    } catch (Throwable $e) {
                        Log::warning('Failed to download Eezee Pay product image', [
                            'product_id' => $productId,
                            'image_url' => $image,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $availablePick = $this->pick($product, ['available', 'is_available', 'isAvailable', 'in_stock']);
                $available = $this->toBool($availablePick === null ? true : $availablePick);

                $optionalPick = $this->pick($product, ['optional_fields_exists', 'optionalFieldsExist', 'has_optional']);
                $optionalFields = $this->toBool($optionalPick === null ? false : $optionalPick);

                $currency = (string) ($this->pick($product, ['currency', 'Currency']) ?? '');

                $costPick = $this->pick($product, [
                    'cost_after_vat',
                    'costPriceAfterVat',
                    'cost_price',
                    'cost',
                    'price_cost',
                ]);
                if ($costPick === null) {
                    $costPick = $this->pick($product, ['price']);
                }
                if ($costPick === null) {
                    $costPick = $this->pick($product, ['unit_price']);
                }

                $sellPick = $this->pick($product, [
                    'unit_price',
                    'sell_price',
                    'sellPrice',
                    'retail_price',
                    'amount',
                ]);
                if ($sellPick === null) {
                    $sellPick = $this->pick($product, ['price']);
                }

                $costAfterVat = $this->toFloat($costPick ?? $sellPick ?? 0);
                $sellPrice = $this->toFloat($sellPick ?? $costAfterVat);

                $slug = Str::slug('eezee-pay-'.str_replace('_', '-', $companyName).'-'.$productId, '-');

                $rows[] = [
                    'product_id' => $productId,
                    'company_name' => $companyName,
                    'merchant_id' => $merchant->id,
                    'category_id' => $defaultCategory->id,
                    'sub_category_id' => $defaultSubCategory->id,
                    'name' => json_encode($nameStruct, JSON_UNESCAPED_UNICODE),
                    'slug' => $slug !== '' ? $slug : 'eezee-pay-'.md5($companyName.'-'.$productId),
                    'description' => $description ? json_encode($description, JSON_UNESCAPED_UNICODE) : null,
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

        if ($rows === []) {
            $this->warn('No Eezee Pay products found to sync.');
            $this->line('Failed merchants: '.$failedMerchants);

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run mode: no database changes were made.');
            $this->line('Validated products count: '.count($rows));
            $this->line('Failed merchants: '.$failedMerchants);
            Log::info('Eezee Pay products sync dry-run.', [
                'company_name' => $companyName,
                'product_row_count' => count($rows),
                'failed_merchants' => $failedMerchants,
            ]);

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
                            'category_id',
                            'sub_category_id',
                            'name',
                            'slug',
                            'description',
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
        } catch (Throwable $e) {
            $this->error('Failed to sync Eezee Pay products into database: '.$e->getMessage());
            Log::error('Eezee Pay products sync failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $this->info('Eezee Pay products synced successfully.');
        $this->line('Total processed: '.count($rows));
        $this->line('Failed merchants: '.$failedMerchants);
        Log::info('Eezee Pay products synced successfully.', [
            'company_name' => $companyName,
            'total_processed' => count($rows),
            'failed_merchants' => $failedMerchants,
        ]);

        return self::SUCCESS;
    }

    protected function extractProducts(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            if (isset($payload['data']['products']) && is_array($payload['data']['products'])) {
                return $payload['data']['products'];
            }

            return array_is_list($payload['data']) ? $payload['data'] : [];
        }

        if (isset($payload['products']) && is_array($payload['products'])) {
            return $payload['products'];
        }

        return array_is_list($payload) ? $payload : [];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function pick(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return $row[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{en: string, ar: string}|null
     */
    protected function localizedField(array $row, array $baseKeys): ?array
    {
        foreach ($baseKeys as $baseKey) {
            $value = $row[$baseKey] ?? null;
            if (is_array($value)) {
                return [
                    'en' => (string) ($value['en'] ?? $value['english'] ?? $value['0'] ?? ''),
                    'ar' => (string) ($value['ar'] ?? $value['arabic'] ?? $value['1'] ?? ''),
                ];
            }
            if (is_string($value) && $value !== '') {
                return ['en' => $value, 'ar' => $value];
            }

            $en = $row[$baseKey.'_en'] ?? $row[$baseKey.'En'] ?? $row[$baseKey.'EN'] ?? null;
            $ar = $row[$baseKey.'_ar'] ?? $row[$baseKey.'Ar'] ?? $row[$baseKey.'AR'] ?? null;
            if ($en !== null || $ar !== null) {
                return [
                    'en' => (string) ($en ?? ''),
                    'ar' => (string) ($ar ?? ''),
                ];
            }
        }

        return null;
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

        $fileName = 'eezee-pay-'.$productId.'-'.Str::random(8).'.'.$extension;
        $path = 'digital-products/'.$fileName;
        $ok = Storage::disk('public')->put($path, $binary);

        return $ok ? $path : null;
    }
}
