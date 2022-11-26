<?php

/**
 * USAGE
 * php convert.php [zip_file] [output_file]
 */

if(PHP_SAPI != 'cli') {
  die('CLI only');
}

if( count($argv) != 3) {
  die("Error: invalid parameter count\n");
}

// check zip_file exists
if(!file_exists($argv[1])) {
  die("Error: zip_file missing\n");
}

// check zip_file mime type
if(mime_content_type($argv[1]) != 'application/zip') {
  die("Error: invalid zip_file\n");
}

// check zip_file is not empty
$zip = new ZipArchive();

$zip->open($argv[1]);

if($zip->numFiles == 0) {
  die("Error: empty archive\n");
}

$data = [];
// iterate csv_files
for ($i = 0; $i < $zip->numFiles; $i++) {
  $file = $zip->statIndex($i);
  if(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) == 'csv') {
    $content = $zip->getFromName($file['name']);
    $rows = str_getcsv($content, "\n");
    foreach($rows as $row) {
      $data[] = str_getcsv($row, ";");
    }
  }
}

if(empty($data)) {
  die("Error: no transactions found\n");
}

$output = fopen($argv[2], 'w');

if(!$output) {
  die("Error: cannot write to output file\n");
}

$cols = [
  'NR CRT',
  'Data tranzactie',
  'Data valuta',
  'Cont',
  'Detalii',
  'Beneficiar',
  'CUI/CNP',
  'IBAN',
  'Referinta tranzactie',
  'Debit',
  'Credit',
  'Sold initial',
  'Sold final',
];

fputcsv($output, $cols);

$rand = 1;
foreach($data as $record) {
  $suma = (float) str_replace(',', '.', str_replace('.', '', $record[9]));
  fputcsv($output, [
    $rand++,
    $record[3],
    $record[7],
    $record[1],
    implode('\n', array_filter([$record[20], $record[21], $record[22], $record[23], $record[24], $record[25], $record[26]])),
    $record[18],
    $record[19],
    $record[16] . $record[17],
    $record[11],
    $record[9] < 0 ? number_format($suma, 2, '.', '') : '',
    $record[9] > 0 ? number_format($suma, 2, '.', '') : '',
    number_format((float) str_replace(',', '.', str_replace('.', '', $record[5])), 2, '.', ''),
    number_format((float) str_replace(',', '.', str_replace('.', '', $record[6])), 2, '.', ''),
  ]);
}
fclose($output);

print($rand . " tranzactions written to " . $argv[2] . "\n");