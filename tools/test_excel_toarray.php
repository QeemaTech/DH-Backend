<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

$relativePath = $argv[1] ?? null;
if (! $relativePath) {
    fwrite(STDERR, "Usage: php tools/test_excel_toarray.php <relativePathOnLocalDisk>\n");
    exit(1);
}

$import = new ProductsImport(1);
$arr = Excel::toArray($import, $relativePath, 'local');

echo "sheets=" . count($arr) . "\n";
echo "sheet0_rows=" . count($arr[0] ?? []) . "\n";
echo "sheet0_row0=" . json_encode(($arr[0][0] ?? null), JSON_UNESCAPED_UNICODE) . "\n";
echo "sheet0_row1=" . json_encode(($arr[0][1] ?? null), JSON_UNESCAPED_UNICODE) . "\n";

