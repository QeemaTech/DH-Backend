<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDaftraProductsStock extends Command
{
    protected $signature = 'products:sync-daftra-stock
                            {--chunk=200 : Chunk size for processing}
                            {--vendor-id= : Sync only products for a specific vendor id}
                            {--only-active : Sync only active products}
                            {--dry-run : Do not write to database, just count}';

    protected $description = 'Sync all products stock/quantities from Daftra (by SKU)';

    public function handle(ProductService $productService): int
    {
        $chunk = max(1, (int) $this->option('chunk'));
        $vendorId = $this->option('vendor-id');
        $onlyActive = (bool) $this->option('only-active');
        $dryRun = (bool) $this->option('dry-run');

        $query = Product::query()->select(['id', 'sku', 'vendor_id', 'is_active']);

        if (! empty($vendorId)) {
            $query->where('vendor_id', (int) $vendorId);
        }

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->warn('No products found to sync.');

            return self::SUCCESS;
        }

        $this->info("Syncing Daftra stock for {$total} product(s)...");
        if ($dryRun) {
            $this->warn('Dry run: no database changes will be made.');
        }

        $success = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->orderBy('id')->chunkById($chunk, function ($products) use ($productService, $dryRun, &$success, &$failed, $bar) {
            foreach ($products as $product) {
                try {
                    if (! $dryRun) {
                        // Ensure we pass a full Product model instance
                        $productService->syncProduct(Product::findOrFail($product->id));
                    }
                    $success++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::error('Daftra stock sync failed for product', [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'error' => $e->getMessage(),
                    ]);
                } finally {
                    $bar->advance();
                }
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info('Daftra stock sync completed.');
        $this->line("Success: {$success}");
        $this->line("Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
