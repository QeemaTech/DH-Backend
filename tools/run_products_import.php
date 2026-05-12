<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

$relativePath = $argv[1] ?? null;
if (! $relativePath) {
    fwrite(STDERR, "Usage: php tools/run_products_import.php <relativePathOnLocalDisk>\n");
    fwrite(STDERR, "Example: php tools/run_products_import.php imports/FILE.xlsx\n");
    exit(1);
}

$import = new ProductsImport(1);

try {
    // NOTE: Some installs route ShouldQueue imports differently; for debugging we pull rows first then feed the importer.
    $rows = Excel::toArray($import, $relativePath, 'local');
    $sheet0 = $rows[0] ?? [];
    $import->collection(collect($sheet0));
    echo "done\n";
    echo "rowCount=" . $import->getRowCount() . "\n";
    echo "failures=" . $import->failures()->count() . "\n";
    echo "errors=" . count($import->errors()) . "\n";
} catch (Throwable $e) {
    echo "failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

