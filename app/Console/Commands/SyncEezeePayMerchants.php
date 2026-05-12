<?php

namespace App\Console\Commands;

use App\Models\DigitalMerchant;
use App\Services\EezeePayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncEezeePayMerchants extends Command
{
    protected $signature = 'merchants:sync-eezeepay {--dry-run : Fetch and validate without writing to database}';

    protected $description = 'Sync Eezee Pay categories into digital_merchants';

    public function handle(EezeePayService $eezeePay): int
    {
        $companyName = (string) (config('services.eezee_pay.company_name') ?? 'eezee_pay');

        try {
            $payload = $eezeePay->categories();
        } catch (Throwable $e) {
            $this->error('Failed to fetch Eezee Pay categories: '.$e->getMessage());
            Log::error('Eezee Pay merchant sync failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        Log::info('Eezee Pay merchant sync: raw categories API result.', [
            'company_name' => $companyName,
            'payload' => $payload,
        ]);

        $categories = $this->extractCategories($payload);
        if ($categories === []) {
            $this->warn('No categories found in Eezee Pay API response.');
            Log::warning('Eezee Pay merchant sync: empty categories list', ['payload_keys' => is_array($payload) ? array_keys($payload) : []]);

            return self::SUCCESS;
        }

        $now = Carbon::now();
        $rows = [];
        $parentLinks = [];

        foreach ($categories as $category) {
            if (! is_array($category)) {
                continue;
            }

            $merchantId = $this->pick($category, ['id', 'category_id', 'categoryId', 'CategoryId']);
            if ($merchantId === null || $merchantId === '') {
                continue;
            }

            $merchantId = (string) $merchantId;
            $name = $this->localizedField($category, [
                'path',
                'name',
                'title',
                'category_name',
                'categoryName',
            ]) ?? ['en' => '', 'ar' => ''];
            $description = $this->localizedField($category, ['description', 'desc']);
            $redeemSteps = $this->localizedField($category, ['redeem_steps', 'redeemSteps']);
            $terms = $this->localizedField($category, ['terms', 'conditions']);
            $parentExternal = $this->pick($category, [
                'parent_id',
                'parent_category_id',
                'category_parent_id',
                'parentId',
                'parent_categoryId',
            ]);

            if ($name['en'] === '' && $name['ar'] === '') {
                $name = ['en' => $merchantId, 'ar' => $merchantId];
            }

            $rows[] = [
                'merchant_id' => $merchantId,
                'company_name' => $companyName,
                'name' => json_encode($name, JSON_UNESCAPED_UNICODE),
                'description' => $description ? json_encode($description, JSON_UNESCAPED_UNICODE) : null,
                'redeem_steps' => $redeemSteps ? json_encode($redeemSteps, JSON_UNESCAPED_UNICODE) : null,
                'terms' => $terms ? json_encode($terms, JSON_UNESCAPED_UNICODE) : null,
                'parent_id' => null,
                'last_synced_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ];

            if ($parentExternal !== null && $parentExternal !== '') {
                $parentLinks[$merchantId] = (string) $parentExternal;
            }
        }

        if ($rows === []) {
            $this->warn('No valid categories to sync.');
            Log::warning('Eezee Pay merchant sync: no valid rows after parsing');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->line('Dry run mode: no database changes were made.');
            $this->info('Validated categories count: '.count($rows));
            $this->logSyncOutcome($companyName, $payload, $rows, $parentLinks, count($categories), dryRun: true);

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($rows, $companyName, $parentLinks) {
                DigitalMerchant::upsert(
                    $rows,
                    ['merchant_id', 'company_name'],
                    ['name', 'description', 'redeem_steps', 'terms', 'last_synced_at', 'updated_at']
                );

                if ($parentLinks !== []) {
                    $map = DigitalMerchant::query()
                        ->where('company_name', $companyName)
                        ->whereIn('merchant_id', array_unique(array_merge(array_keys($parentLinks), array_values($parentLinks))))
                        ->pluck('id', 'merchant_id');

                    $parentUpdates = [];
                    foreach ($parentLinks as $childMerchantId => $parentMerchantId) {
                        $childId = $map[$childMerchantId] ?? null;
                        $parentId = $map[$parentMerchantId] ?? null;

                        if ($childId && $parentId && $childId !== $parentId) {
                            $parentUpdates[(int) $childId] = (int) $parentId;
                        }
                    }

                    if ($parentUpdates !== []) {
                        $cases = [];
                        $ids = [];

                        foreach ($parentUpdates as $childId => $parentId) {
                            $cases[] = "WHEN {$childId} THEN {$parentId}";
                            $ids[] = $childId;
                        }

                        $idsSql = implode(',', $ids);
                        $caseSql = implode(' ', $cases);

                        DB::update("UPDATE digital_merchants SET parent_id = CASE id {$caseSql} END WHERE id IN ({$idsSql})");
                    }
                }
            });
        } catch (Throwable $e) {
            $this->error('Failed to sync categories into database: '.$e->getMessage());
            Log::error('Eezee Pay merchant sync failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $this->info('Eezee Pay categories synced successfully.');
        $this->line('Total processed: '.count($rows));
        $this->logSyncOutcome($companyName, $payload, $rows, $parentLinks, count($categories), dryRun: false);

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $parentLinks
     */
    protected function logSyncOutcome(string $companyName, array $payload, array $rows, array $parentLinks, int $extractedCategoryCount, bool $dryRun): void
    {
        $parsedRows = [];
        foreach ($rows as $row) {
            $nameDecoded = json_decode((string) ($row['name'] ?? ''), true);
            $parsedRows[] = [
                'merchant_id' => $row['merchant_id'] ?? null,
                'name' => is_array($nameDecoded) ? $nameDecoded : null,
            ];
        }

        $context = [
            'company_name' => $companyName,
            'dry_run' => $dryRun,
            'total_processed' => count($rows),
            'extracted_from_api_count' => $extractedCategoryCount,
            'parent_links' => $parentLinks,
            'parent_links_count' => count($parentLinks),
        ];

        if (count($parsedRows) <= 200) {
            $context['digital_merchant_rows'] = $parsedRows;
        } else {
            $context['digital_merchant_rows_sample'] = array_slice($parsedRows, 0, 50);
            $context['digital_merchant_rows_truncated'] = true;
        }

        Log::info(
            $dryRun
                ? 'Eezee Pay merchant sync dry-run result (no DB writes).'
                : 'Eezee Pay merchant sync completed successfully.',
            $context
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, mixed>
     */
    protected function extractCategories(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            if (isset($payload['data']['categories']) && is_array($payload['data']['categories'])) {
                return $payload['data']['categories'];
            }

            return array_is_list($payload['data']) ? $payload['data'] : [];
        }

        if (isset($payload['categories']) && is_array($payload['categories'])) {
            return $payload['categories'];
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
}
