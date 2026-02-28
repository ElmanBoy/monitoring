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
$signs = $db->select('signs', ' where doc_id = ?', [$planId]);
$tmpl = $db->selectOne('documents', ' where id = ?', [$plan->document]);
$ins = $db->getRegistry('institutions');
$insp = $db->getRegistry('inspections');
$units = $db->getRegistry('units');
$users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name']);

$author = $users['array'][$plan->author][0].' '.
    mb_substr(trim($users['array'][$plan->author][1]), 0, 1).'. '.
    mb_substr(trim($users['array'][$plan->author][2]), 0, 1).'.';
$date_createArr = explode(' ', $plan->created_at);

$checks = json_decode($plan->addinstitution, true);
$gui->set('module_id', 1);

$html = '<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: "Times-Roman", "Times New Roman", serif; font-size: 3.5mm; margin: 0px; }
            table, table tr, table tr td, table tr th{
                border: 1px solid #000;
                border-collapse: collapse;
                padding: 12px;
            }
            main{
                margin: 1cm;
            }
            footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                text-align: left;
                font-size: 11px;
                color: #000;
                padding: 5px 0 0 12px;
                border-top: 1px solid #000;
                height: .1cm;
            }
            footer img{
                position: absolute;
                bottom: -30px;
                right: 10px;
                width: 100px;
            }
            @page {
                margin: 1cm 0 1cm 0;
            }
        </style>
        
        <title>План проверок</title>
    </head>
    <body><footer>Документ создан в электронной форме. № '.$plan->doc_number.' от '.$date->dateToString($date_createArr[0]).' 
    Исполнитель: '.$author.'<img src="data:image/png;base64,' . $temp->bottom_logo . '"></footer><main>';

$sign_vars = [];
foreach($signs as $sign){
    $sign_vars[$sign->user_id] = $sign->sign;
}

$header_vars = [
    'today_date' => $date->dateToString(date('Y-m-d')),
    'sign' => $temp->getSign(json_decode($sign_vars[1], 'true')['certificate_info'])
];
$html .= $temp->twig_parse($tmpl->header, $header_vars);

$html .= $temp->twig_parse($plan->longname, ['year' => date('Y')]);

$body_vars = [];
$check_number = 1;
foreach ($checks as $ch) {
    $dateArr = explode(' - ', $ch['check_periods']);
    $check_period = $date->correctDateFormatFromMysql($dateArr[0]).' - '.$date->correctDateFormatFromMysql($dateArr[1]);

    $insName = is_array($ins['array'][$ch['institutions']])
        ? $ins['array'][$ch['institutions']][0]
        : $ins['array'][$ch['institutions']];

    $body_vars[] = [
        'check_number' => $check_number,
        'institution' => stripslashes($insName),
        'unit' => stripslashes($units['array'][$ch['units']]),
        'inspections' => stripslashes($insp['array'][$ch['inspections']]),
        'period' => $ch['periods'],
        'check_periods' => $check_period
    ];
    $check_number++;
}
$html .= $temp->twig_parse($tmpl->body, ['checks' => $body_vars]);

$bottom_vars = [
    'today_date' => $date->dateToString(date('Y-m-d')),
    'sign' => $temp->getSign(json_decode($sign_vars[4], true)['certificate_info']),
    'approval_sheet' => ''
];
$html .= $temp->twig_parse($tmpl->bottom, $bottom_vars);

$html .= '</main>
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
$dompdf->setPaper('A4', 'landscape');//portrait



// Render the HTML as PDF
$dompdf->render();
$canvas = $dompdf->getCanvas();
$footerText = 'Страница {PAGE_NUM} из {PAGE_COUNT}. Страница создана: '.$date->dateToString($plan->created_at);
$canvas->page_text(8, 575, $footerText, 'DejaVu Sans', 8, [0, 0, 0]);
// Output the generated PDF to Browser
//$dompdf->stream();
?>
<div class='pop_up drag' style="min-width: 50vw">
    <div class='title handle'>
        <div class='name'>Просмотр плана проверок</div>
        <div class='button icon close'><span class='material-icons'>close</span></div>
    </div>
    <div class='pop_up_body'>
        <iframe id='pdf-viewer' width='100%' height='600px'></iframe>
        <div class='confirm'>
            <?
            /*if(in_array($_SESSION['user_id'], json_decode($plan->signators)) && strlen($sign_vars[$_SESSION['user_id']]) == 0){
            ?>
            <button class='button icon text green setSign'><span class='material-icons'>verified</span>Подписать</button>
            <?
            }*/
            ?>
            <button class='button icon close'><span class='material-icons'>close</span>Закрыть</button>
        </div>
    </div>

</div>
<script>
    pdfData = '<?= base64_encode($dompdf->output()) ?>';
    document.getElementById('pdf-viewer').src = `data:application/pdf;base64,${pdfData}`;
    bindSign('checksplans', <?=$plan->id?>, <?=$_SESSION['user_id']?>);
    $(document).on("doc_signed", function(){
        el_app.dialog_close('pdf');
        el_app.dialog_open('pdf', <?=$plan->id?>, 'plans');
    })
</script>