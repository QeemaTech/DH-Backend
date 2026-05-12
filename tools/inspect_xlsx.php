<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$path = $argv[1] ?? null;
if (! $path) {
    fwrite(STDERR, "Usage: php tools/inspect_xlsx.php <path>\n");
    exit(1);
}

$ss = IOFactory::load($path);
$sheet = $ss->getSheet(0);

echo "sheet=" . $sheet->getTitle() . PHP_EOL;
echo "highestRow=" . $sheet->getHighestRow() . PHP_EOL;
echo "highestCol=" . $sheet->getHighestColumn() . PHP_EOL;

foreach (['A1','B1','C1','A2','B2','C2'] as $addr) {
    $v = $sheet->getCell($addr)->getValue();
    echo "cell{$addr}=" . json_encode($v, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

// Compare PhpSpreadsheet rangeToArray behaviors (some files return empty with returnCellRef=true)
$rta1 = $sheet->rangeToArray('A1:'.$sheet->getHighestColumn().'1', null, true, true, true);
$rta2 = $sheet->rangeToArray('A1:'.$sheet->getHighestColumn().'1', null, true, true, false);
echo "rangeToArray(returnCellRef=true)=" . json_encode($rta1[0] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo "rangeToArray(returnCellRef=false)=" . json_encode($rta2[0] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;
$rta1b = $sheet->rangeToArray('A2:'.$sheet->getHighestColumn().'2', null, true, true, true);
$rta2b = $sheet->rangeToArray('A2:'.$sheet->getHighestColumn().'2', null, true, true, false);
echo "rangeToArrayRow2(returnCellRef=true)=" . json_encode($rta1b[0] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo "rangeToArrayRow2(returnCellRef=false)=" . json_encode($rta2b[0] ?? [], JSON_UNESCAPED_UNICODE) . PHP_EOL;

foreach ([1, 2, 3] as $r) {
    $vals = [];
    foreach (['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T'] as $col) {
        $vals[$col] = $sheet->getCell($col.$r)->getValue();
    }
    echo "row{$r}=" . json_encode($vals, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

