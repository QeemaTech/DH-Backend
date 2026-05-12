<?php

namespace App\Console\Commands;

use App\Models\DigitalMerchant;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncLike4AppMerchants extends Command
{
    protected $signature = 'merchants:sync-like4app {--dry-run : Fetch and validate without writing to database}';

    protected $description = 'Sync digital merchants from Like4App categories API';

    public function handle(): int
    {
        $config = config('services.like4app', []);
        $url = (string) ($config['categories_url'] ?? 'https://taxes.like4app.com/online/categories');
        $deviceId = (string) ($config['device_id'] ?? '');
        $email = (string) ($config['email'] ?? '');
        $securityCode = (string) ($config['security_code'] ?? '');
        $langId = (string) ($config['lang_id'] ?? '1');
        $companyName = (string) ($config['company_name'] ?? 'like card');
        $connectTimeout = (int) ($config['connect_timeout'] ?? 15);
        $timeout = (int) ($config['timeout'] ?? 30);

        if ($deviceId === '' || $email === '' || $securityCode === '') {
            $this->error('Like4App credentials are missing. Please set LIKE4APP_DEVICE_ID, LIKE4APP_EMAIL and LIKE4APP_SECURITY_CODE.');
            Log::error('Like4App sync failed', ['error' => 'Missing required Like4App credentials']);

            return self::FAILURE;
        }

        $postFields = [
            'deviceId' => $deviceId,
            'email' => $email,
            'securityCode' => $securityCode,
            'langId' => $langId,
        ];

        $cookieFile = sys_get_temp_dir().DIRECTORY_SEPARATOR.'like4app_cookie.txt';

        try {
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
                CURLOPT_CONNECTTIMEOUT => $connectTimeout,
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
                throw new \RuntimeException('cURL error: '.curl_error($ch));
            }

            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseHeaders = substr($rawResponse, 0, $headerSize);
            $responseBody = substr($rawResponse, $headerSize);
            curl_close($ch);

            if ($statusCode >= 400) {
                Log::error('Like4App categories request failed', [
                    'status' => $statusCode,
                    'response_headers' => $responseHeaders,
                    'response_body' => $responseBody,
                ]);
                throw new \RuntimeException("Like4App request failed with status {$statusCode}. Body: {$responseBody}");
            }

            $payload = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
            Log::info('Like4App categories response', ['payload' => $payload]);
        } catch (\Throwable $e) {
            $this->error('Failed to fetch Like4App categories: '.$e->getMessage());
            Log::error('Like4App sync failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $roots = $payload['data'] ?? [];
        if (! is_array($roots) || empty($roots)) {
            $this->warn('No categories found in Like4App response.');

            return self::SUCCESS;
        }

        $allChilds = [];
        foreach ($roots as $root) {
            if (is_array($root)) {
                $allChilds = array_merge($allChilds, $this->collectDescendants($root));
            }
        }

        if (empty($allChilds)) {
            $this->warn('No descendant categories found to sync.');

            return self::SUCCESS;
        }

        $now = Carbon::now();
        $rows = [];
        $parentLinks = [];

        foreach ($allChilds as $category) {
            $id = (string) ($category['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $parentExternal = (string) ($category['categoryParentId'] ?? '');
            $name = (string) ($category['categoryName'] ?? 'like_card');
            $description = (string) ($category['metaData']['description'] ?? '');
            $redeemSteps = (string) ($category['metaData']['redeemSteps'] ?? '');
            $terms = (string) ($category['metaData']['TANDC'] ?? '');

            $rows[] = [
                'merchant_id' => $id,
                'company_name' => $companyName,
                'name' => json_encode(['en' => $name, 'ar' => $name], JSON_UNESCAPED_UNICODE),
                'description' => $description !== '' ? json_encode(['en' => $description, 'ar' => $description], JSON_UNESCAPED_UNICODE) : null,
                'redeem_steps' => $redeemSteps !== '' ? json_encode(['en' => $redeemSteps, 'ar' => $redeemSteps], JSON_UNESCAPED_UNICODE) : null,
                'terms' => $terms !== '' ? json_encode(['en' => $terms, 'ar' => $terms], JSON_UNESCAPED_UNICODE) : null,
                'parent_id' => null,
                'last_synced_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ];

            if ($parentExternal !== '') {
                $parentLinks[$id] = $parentExternal;
            }
        }

        if (empty($rows)) {
            $this->warn('No valid Like4App merchants to sync.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run mode: no database changes were made.');
            $this->line('Validated merchants count: '.count($rows));

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

                        DB::update(
                            'UPDATE digital_merchants SET parent_id = CASE id '.implode(' ', $cases).' END WHERE id IN ('.implode(',', $ids).')'
                        );
                    }
                }
            });
        } catch (\Throwable $e) {
            $this->error('Failed to sync Like4App merchants into database: '.$e->getMessage());
            Log::error('Like4App sync failed', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        $this->info('Like4App merchants synced successfully.');
        $this->line('Total processed: '.count($rows));
        Log::info('Like4App merchants synced successfully', ['total_processed' => count($rows)]);

        return self::SUCCESS;
    }

    /**
     * Collect all descendants from a root node recursively.
     */
    protected function collectDescendants(array $node): array
    {
        $out = [];

        if (! empty($node['childs']) && is_array($node['childs'])) {
            foreach ($node['childs'] as $child) {
                if (! is_array($child)) {
                    continue;
                }

                $out[] = $child;
                $out = array_merge($out, $this->collectDescendants($child));
            }
        }

        return $out;
    }
}
