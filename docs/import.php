<?php

require $_SERVER['DOCUMENT_ROOT'] . '/modules/vendor/autoload.php';

$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($_SERVER['DOCUMENT_ROOT'].'/tbl.xlsx');

$worksheet = $spreadsheet->getActiveSheet();
// Get the highest row number and column letter referenced in the worksheet
$highestRow = $worksheet->getHighestRow(); // e.g. 10
$highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
// Increment the highest column letter
$highestColumn++;

echo '<table border=1>' . "\n";
for ($row = 1; $row <= $highestRow - 1; ++$row) {
    if($worksheet->getCell('A'.$row) != '') {
        echo '<tr>' . PHP_EOL;
        for ($col = 'A'; $col != 'F'/*$highestColumn*/; ++$col) {
            echo (($row == 1) ? '<th>' : '<td>') .
                $worksheet->getCell($col . $row)
                    ->getValue() .
                (($row == 1) ? '</th>' : '</td>') . PHP_EOL;
        }
        echo '</tr>' . PHP_EOL;
    }else{
        return;
    }
}
echo '</table>' . PHP_EOL;
?>
