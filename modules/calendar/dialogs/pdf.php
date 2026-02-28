<?php

use Dompdf\Dompdf;
use Dompdf\Options;
use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Date;
use Core\Templates;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$gui = new Gui;
$db = new Db;
$auth = new Auth();
$temp = new Templates();
$date = new Date();
$html = '';
$planId = intval($_POST['params']);

$plan = $db->selectOne('checksplans', ' where id = ?', [$planId]);
$tmpl = $db->selectOne('documents', ' where id = ?', [$plan->document]);
$ins = $db->getRegistry('institutions');
$insp = $db->getRegistry('inspections');
$units = $db->getRegistry('units');

$checks = json_decode($plan->addinstitution, true);
$gui->set('module_id', 14);

$html = '<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 3.5mm }
            table, table tr, table tr td, table tr th{
            border: 1px solid #000;
            border-collapse: collapse;
            padding: 10px;
            }
        </style>
        
        <title>План проверок</title>
    </head>
    <body>';

$header_vars = [
    'today_date' => $plan->document.' '.$date->dateToString(date('Y-m-d')),
    'sign' => ''
];

$html .= $temp->twig_parse($tmpl->header, $header_vars);//$temp->parse($tmpl->header, []);

$html .= stripslashes($plan->longname)
    .'<table class="table_data" id="tbl_registry_items">
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
    $dateArr = explode(' - ', $ch['check_periods']);
    $check_period = $date->correctDateFormatFromMysql($dateArr[0]).' - '.$date->correctDateFormatFromMysql($dateArr[1]);

    $html .= '<tr data-id="' . $check_number . '" tabindex="0" class="noclick">
                    <td>' . $check_number . '</td>
                    <td>' . stripslashes(htmlspecialchars($ins['array'][$ch['institutions']])) .
        stripslashes(htmlspecialchars($units['array'][$ch['units']])) .
        '</td>
                    <td class="group">' . stripslashes($insp['array'][$ch['inspections']]) . '</td>
                    <td>' . $ch['periods'] . '</td>
                    <td>' . $check_period . '</td>
                </tr>';
    $check_number++;
}
$html .= '</tbody>
        </table>';
$html .= $temp->parse($tmpl->bottom, []);
$html .= '<span class="page-number">{{PAGE_NUM}}</span>
<script>
            document.querySelectorAll(".page-number").forEach(el => {
                el.textContent = "Страница " + window.PAGE_NUM + " из " + window.PAGE_COUNT;
            });
        </script>
        </body>
        </html>';

//$html = mb_convert_encoding($html, 'Windows-1251', 'UTF-8');
// instantiate and use the dompdf class
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->set_option('defaultFont', 'DejaVu Sans');
$dompdf->set_option('isHtml5ParserEnabled', true);
$dompdf->set_option('isRemoteEnabled', true);
$options -> set ( 'isHtml5ParserEnabled' , true );
$dompdf->loadHtml($html, 'UTF-8');

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');//landscape

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
//$dompdf->stream();
?>
<div class='pop_up drag' style='width: 60vw;'>
    <div class='title handle'>
        <div class='name'>Просмотр плана проверок</div>
        <div class='button icon close'><span class='material-icons'>close</span></div>
    </div>
    <div class='pop_up_body'>
        <iframe id='pdf-viewer' width='100%' height='600px'></iframe>
        <div class='confirm'>
            <button class='button icon close'><span class='material-icons'>close</span>Закрыть</button>
        </div>
    </div>

</div>
<script>
    pdfData = '<?= base64_encode($dompdf->output()) ?>';
    document.getElementById('pdf-viewer').src = `data:application/pdf;base64,${pdfData}`;
</script>