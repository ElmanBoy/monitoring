<?php

use Dompdf\Dompdf;
use Dompdf\Options;
use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Templates;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$gui = new Gui;
$db = new Db;
$auth = new Auth();
$temp = new Templates();
$html = '';
$docId = intval($_POST['params']['docId']);

$tmpl = $db->selectOne('documents', ' where id = ?', [$docId]);

//print_r($tmpl);
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
        
        <title>Документ</title>
    </head>
    <body>';

if(strlen($tmpl->header) > 0) {
    $html .= $temp->parse($tmpl->header, []);
}
if(strlen($tmpl->body) > 0) {
    $html .= $temp->parse($tmpl->body, []);
}
if(strlen($tmpl->bottom) > 0) {
    $html .= $temp->parse($tmpl->bottom, []);
}
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
<div class='pop_up drag' style="width: 70vw; min-height: 80vh;">
    <div class='title handle'>
        <div class='name'>Просмотр документа</div>
        <div class='button icon close'><span class='material-icons'>close</span></div>
    </div>
    <div class='pop_up_body'>
        <iframe id='pdf-viewer' width='100%' height='600px' style="min-height: 80vh"></iframe>
        <div class='confirm'>
            <button class='button icon close' id="sign"><span class='material-icons'>approval</span>Подписать / Согласовать</button>
            <button class='button icon close'><span class='material-icons'>close</span>Закрыть</button>
        </div>
    </div>

</div>
<script>
    pdfData = '<?= base64_encode($dompdf->output()) ?>';
    document.getElementById('pdf-viewer').src = `data:application/pdf;base64,${pdfData}`;
</script>