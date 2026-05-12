<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Exports\ProductsImportTemplate;
use Maatwebsite\Excel\Facades\Excel;

$out = $argv[1] ?? ('storage/app/tmp_products_import_template.xlsx');
@mkdir(dirname($out), 0777, true);

Excel::store(new ProductsImportTemplate(), $out, 'local');

echo "stored_to={$out}\n";

