<?php

namespace App\Console\Commands;

use App\Models\DigitalMerchant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncOneCardMerchants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'merchants:sync-onecard {--dry-run : Fetch and validate without writing to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync digital merchants from OneCard API';

    public function handle(): int
    {
        $config = config('services.onecard', []);
        $baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $endpoint = (string) ($config['merchants_endpoint'] ?? '/integration/get-merchant-list');
        $companyName = (string) ($config['company_name'] ?? 'one_card');
        $timeout = (int) ($config['timeout'] ?? 30);
        $resellerUsername = (string) ($config['reseller_username'] ?? '');
        $requestPassword = (string) ($config['merchants_password'] ?? '');

        if ($baseUrl === '') {
            $this->error('OneCard base URL is missing. Please set ONECARD_BASE_URL.');
            Log::error('OneCard sync failed', [
                'error' => 'OneCard base URL is missing. Please set ONECARD_BASE_URL.',
            ]);

            return self::FAILURE;
        }

        if ($resellerUsername === '' || $requestPassword === '') {
            $this->error('OneCard credentials are missing. Please set ONECARD_RESELLER_USERNAME and ONECARD_MERCHANTS_PASSWORD.');
            Log::error('OneCard sync failed', [
                'error' => 'OneCard credentials are missing. Please set ONECARD_RESELLER_USERNAME and ONECARD_MERCHANTS_PASSWORD.',
            ]);

            return self::FAILURE;
        }

        $url = $baseUrl.'/'.ltrim($endpoint, '/');
        $this->info("Fetching merchants from: {$url}");

        try {
            $requestBody = json_encode([
                'resellerUsername' => $resellerUsername,
                'password' => $requestPassword,
            ], JSON_THROW_ON_ERROR);

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
                Log::error('OneCard sync failed', [
                    'error' => 'cURL error: '.curl_error($ch),
                ]);
                throw new \RuntimeException('cURL error: '.curl_error($ch));
            }

            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($statusCode >= 400) {
                Log::error('OneCard sync failed', [
                    'error' => "OneCard request failed with status {$statusCode}",
                ]);
                throw new \RuntimeException("OneCard request failed with status {$statusCode}");
            }

            $payload = json_decode($rawResponse, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            $this->error('Failed to fetch merchants: '.$e->getMessage());
            Log::error('OneCard sync failed', [
                'error' => 'Failed to fetch merchants: '.$e->getMessage(),
            ]);

            return self::FAILURE;
        }

        $merchants = $this->extractMerchants($payload);
        if (empty($merchants)) {
            $this->warn('No merchants found in API response.');
            Log::error('OneCard sync failed', [
                'error' => 'No merchants found in API response.',
            ]);

            return self::SUCCESS;
        }

        $now = Carbon::now();
        $rows = [];
        $parentLinks = [];

        foreach ($merchants as $merchant) {
            $merchantId = $this->pick($merchant, ['merchant_id', 'id', 'merchantId', 'MerchantId']);
            if ($merchantId === null || $merchantId === '') {
                continue;
            }

            $merchantId = (string) $merchantId;
            $name = $this->localizedField($merchant, ['name', 'merchant_name', 'merchantName']);
            $description = $this->localizedField($merchant, ['description', 'desc']);
            $redeemSteps = $this->localizedField($merchant, ['redeem_steps', 'redeemSteps']);
            $terms = $this->localizedField($merchant, ['terms', 'conditions']);
            $parentExternal = $this->pick($merchant, ['parent_merchant_id', 'parent_id', 'parentMerchantId']);

            if (empty($name['en']) && empty($name['ar'])) {
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

        if (empty($rows)) {
            $this->warn('No valid merchants to sync.');
            Log::error('OneCard sync failed', [
                'error' => 'No valid merchants to sync.',
            ]);

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->line('Dry run mode: no database changes were made.');
            $this->info('Validated merchants count: '.count($rows));
            Log::error('OneCard sync failed', [
                'error' => 'Dry run mode: no database changes were made.',
            ]);

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($rows, $companyName, $parentLinks) {
                DigitalMerchant::upsert(
                    $rows,
                    ['merchant_id', 'company_name'],
                    ['name', 'description', 'redeem_steps', 'terms', 'last_synced_at', 'updated_at']
                );

                if (! empty($parentLinks)) {
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

                    if (! empty($parentUpdates)) {
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
        } catch (\Throwable $e) {
            $this->error('Failed to sync merchants into database: '.$e->getMessage());
            Log::error('OneCard sync failed', [
                'error' => 'Failed to sync merchants into database: '.$e->getMessage(),
            ]);

            return self::FAILURE;
        }

        $this->info('OneCard merchants synced successfully.');
        $this->line('Total processed: '.count($rows));
        Log::info('OneCard merchants synced successfully.', [
            'total_processed' => count($rows),
        ]);

        return self::SUCCESS;
    }

    /**
     * Extract merchants array from different response shapes.
     */
    protected function extractMerchants(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            if (isset($payload['data']['merchants']) && is_array($payload['data']['merchants'])) {
                return $payload['data']['merchants'];
            }

            return array_is_list($payload['data']) ? $payload['data'] : [];
        }

        if (isset($payload['merchants']) && is_array($payload['merchants'])) {
            return $payload['merchants'];
        }
        if (isset($payload['merchantList']) && is_array($payload['merchantList'])) {
            return $payload['merchantList'];
        }

        return array_is_list($payload) ? $payload : [];
    }

    /**
     * Pick the first existing key from the input array.
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
     * Normalize localized field to ['en' => ?, 'ar' => ?] format.
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
