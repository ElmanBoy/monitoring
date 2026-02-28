<?php

use Dompdf\Dompdf;
use Dompdf\Options;
use Core\Gui;
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$gui = new Gui;
$db = new Db;
$auth = new Auth();
$html = '';
$planId = intval($_POST['docId']);

$plan = $db->selectOne('checksplans', ' where id = ?', [$planId]);
$ins = $db->getRegistry('institutions');
$insp = $db->getRegistry('inspections');
$units = $db->getRegistry('units');

$checks = json_decode($plan->addinstitution, true);
$gui->set('module_id', 1);

$html = '
<style>
body { 
                /*font-family: "Jost", sans-serif; 
                font-size: 3.5mm;*/
                font-family: "Times-Roman", "Times New Roman", serif;
                font-weight: normal;
                font-size: 16px;
                font-kerning: auto;
                hyphens: auto;
                line-height: 14px;
            }
</style>
<table class="table_data" id="tbl_registry_items">
            <thead>
            <tr class="fixed_thead">
                <th class="sort">№</th>
                <th class="sort">Объект проверки</th>
                <th class="sort">Предмет проверки</th>
                <th class="sort">Период проверки</th>
                <th class="sort">Проверяемый период</th>
            </tr>
            </thead>
            <tbody>';

$check_number = 1;
foreach ($checks as $ch) {

    $html .= '<tr data-id="' . $check_number . '" tabindex="0" class="noclick">
                    <td>' . $check_number . '</td>
                    <td>' . stripslashes(htmlspecialchars($ins['array'][$ch['institutions']])) .
        stripslashes(htmlspecialchars($units['array'][$ch['units']])) .
        '</td>
                    <td class="group">' . stripslashes($insp['array'][$ch['inspections']]) . '</td>
                    <td>' . $ch['periods'] . '</td>
                    <td>' . $ch['check_periods'] . '</td>
                </tr>';
    $check_number++;
}

$html .='</tbody>
        </table>';


// instantiate and use the dompdf class
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Times-Roman');
$options->set('defaultEncoding', 'UTF-8');
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'landscape');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
//$dompdf->stream();
?>

<iframe id = 'pdf-viewer' width = '100%' height = '600px' ></iframe >

<script >
  // После генерации PDF:
  const pdfData = '<?= base64_encode($dompdf->output()) ?>';
  document . getElementById('pdf-viewer') . src = `data:application/pdf;base64,${pdfData}`;
</script >