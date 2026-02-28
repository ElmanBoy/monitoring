<?php

use Dompdf\Dompdf;
use Dompdf\Options;
use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Date;
use Core\Templates;
use Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$gui = new Gui;
$db = new Db;
$auth = new Auth();
$temp = new Templates();
$date = new Date();
$reg = new Registry();
$html = '';
$docId = intval($_POST['params']['docId']);
$is_inst = boolval($_POST['params']['is_inst']);
$data = [];
$user_signs = [];
$shortName = '';

function buildArgeementList($itemArr, $section, $users, $urgent_types, $user_signs, $level = 0): string
{
    global $temp;
    $html = '';
    $rowNumber = 1;

    $itemArr = is_string($itemArr) ? json_decode($itemArr, true) : $itemArr;

    if ($level == 0) {
        $start = 1;
    } else {
        $start = 0;
    }

    //Листаем списки в секциях
    for ($l = $start; $l < count($itemArr); $l++) {

        $user_fio = $users['array'][$itemArr[$l]['id']][0] . ' ' . mb_substr($users['array'][$itemArr[$l]['id']][1], 0, 1) . '. ' .
            mb_substr($users['array'][$itemArr[$l]['id']][2], 0, 1) . '.';

        //Нумерация только на первом уровне
        if ($level == 0) {
            if (is_array($itemArr[$l - 1]['redirect'])) {
                $rowNumber = ($rowNumber - 1);
            }
        }

        $html .= '<tr><td>' . ($level == 0 ? $rowNumber.(is_array($itemArr[$l - 1]['redirect']) ? '.'.($l - 1) : '') : '') . '</td>'.
            '<td'.($level > 0 ? ' style="padding-left: '.(15 * $level).'px"' : '').'>' . $user_fio . '</td><td class="center">' .
            $urgent_types[$itemArr[0]['urgent']] .
            '</td><td class="center">';

        //Если это строка текущего авторизованного сотрудника
        if ($_SESSION['user_id'] == $itemArr[$l]['id']) {
            $html .= "<div class='actions' data-section='" . $section . "'>";
            $nextAllow = true;

            if ($itemArr[0]['list_type'] == '1') {

                //Если согласование последовательное, указываем согласование какого сотрудника ждём
                if ($user_signs[$itemArr[$l - 1]['id']][$section]['type'] != intval($itemArr[$l - 1]['type']) &&
                    (!is_array($itemArr[$l - 1]['result']) || $itemArr[$l - 1]['result']['id'] == 4)) {

                    $sign_type = intval($itemArr[$l]['type']) == 1 ? 'подпись' : 'согласование';
                    $html .= 'Ожидается ' . $sign_type . ' сотрудника<br>';

                    $uid = ($itemArr[$l - 1]['result']['id'] == 4) ?
                        $itemArr[$l - 1]['redirect'][array_key_last($itemArr[$l - 1]['redirect'])]['id'] :
                        $itemArr[$l - 1]['id'];
                    $html .= $users['array'][$uid][0] . ' ' .
                        mb_substr($users['array'][$uid][1], 0, 1) . '. ' .
                        mb_substr($users['array'][$uid][2], 0, 1) . '.';
                    //Ждём согласования предыдущего сотрудника
                    $nextAllow = false;
                }
            }
            //Если можно не ждать согласование предыдущего сотрудника
            if ($nextAllow) {
                if (!is_array($itemArr[$l]['redirect'])) {
                    /*
                    * 1 - подписание
                    * 2 - согласование с эп
                    * 3 - согласование
                    * 4 - перенапропавление*/
                    $user_sign_type = intval($user_signs[$itemArr[$l]['id']][$section]['type']);
                    $user_sign_date = $user_signs[$itemArr[$l]['id']][$section]['date'];
                    switch (intval($itemArr[$l]['type'])){
                        case 1:
                            if ($user_sign_type == 1) {
                                $html .= "Подписано с ЭП<br>" .
                                    date('d.m.Y H:i', strtotime($user_sign_date));
                                $html .= $temp->getSign($user_signs[$itemArr[$l]['id']][$section]['certificate_info']['certificate_info']);
                                //echo '!!!!!';print_r($user_signs[$itemArr[$l]['id']][$section]['certificate_info']['certificate_info']);
                            }
                            break;

                        case 2:
                        case 3:
                            //Без подписи ЭП
                            if (intval($itemArr[$l]['result']['id']) == 3) {
                                $html .= "Согласовано<br>" .
                                    $itemArr[$l]['result']['date'] ;
                            }else {
                                //С подписью ЭП
                                if ($user_sign_type == 2) {
                                    $html .= "Согласовано с ЭП<br>" .
                                        date('d.m.Y H:i', strtotime($user_sign_date));
                                    $html .= $temp->getSign($user_signs[$itemArr[$l]['id']][$section]['certificate_info']['certificate_info']);
                                }
                            }
                            break;
                    }
                }
            }
        }
        if (is_array($itemArr[$l]['redirect'])) {
            $html .= 'Перенаправлено<br>' . $itemArr[$l]['result']['date'] . '';
        }
        $comment = htmlspecialchars(trim($itemArr[$l]['comment']));
        $html .= '</td><td>' . $comment . '</td></tr>';

        if (is_array($itemArr[$l]['redirect'])) {
            $redirected = $itemArr[$l]['redirect'];
            $html .= buildArgeementList(
                $redirected,
                $section,
                $users,
                $urgent_types,
                $user_signs,
                $level + 1
            );
        }
        if ($level == 0) {
            $rowNumber++;
        }
    }
    return $html;
}

if(isset($_POST['params']['is_doc']) && intval($_POST['params']['is_doc']) == 1){
    $tmpl = $db->selectOne('documents', ' where id = ?', [$docId]);
}else {
    $tmpl = $db->selectOne('agreement', ' where id = ?', [$docId]);
    $data['agreementlist'] = json_decode($tmpl->agreementlist, true);
    $data['document'] = $tmpl->document;
    $data['agreement_date'] = $tmpl->docdate;
    $plan = $db->selectOne($tmpl->source_table, " WHERE id = ?", [$tmpl->source_id]);
    $data['longname'] = $plan->longname;
    $shortName = '&laquo;'.($plan->short ?? $tmpl->name).'&raquo;';
    $addinstitution = json_decode($plan->addinstitution, true);
    $a = 0;
    if(is_array($addinstitution) && count($addinstitution) > 0) {
        foreach ($addinstitution as $add) {
            $data['institutions'][$a] = $add['institutions'];
            $data['check_types'][$a] = $add['check_types'];
            $data['units'][$a] = $add['units'];
            $data['periods'][$a] = $add['periods'];
            $data['periods_hidden'][$a] = $add['periods_hidden'];
            $data['inspections'][$a] = $add['inspections'];
            $data['check_periods'][$a] = $add['check_periods']; //print_r($data);
            $a++;
        }
    }
}
$signs = $db->select('signs', " where table_name = 'agreement' AND  doc_id = ?", [$docId]);
if (count($signs) > 0) {
    foreach ($signs as $s) {
        $user_signs[$s->user_id][$s->section] = [
            'type' => $s->type,
            'date' => $s->created_at,
            'certificate_info' => json_decode($s->sign, true)
        ];
    }
}
$users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name', 'position']);
$initiator_fio = $users['array'][$tmpl->initiator][0] . ' ' . $users['array'][$tmpl->initiator][1] . ' ' .
    $users['array'][$tmpl->initiator][2];
$initiator_position = $users['array'][$tmpl->initiator][3];

$urgent_types = [
    1 => 'Обычный',
    2 => 'Срочный',
    3 => 'Незамедлительно'
];


$html = '<html lang="ru">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            /*@font-face {
                font-family: "Jost";
                src: url("/fonts/Jost-Light.ttf") format("truetype");
                font-weight: 300;
                font-style: normal;
                font-display: swap;
            }
            
            @font-face {
                font-family: "Jost";
                src: url("/fonts/Jost-Regular.ttf") format("truetype");
                font-weight: 400;
                font-style: normal;
                font-display: swap;
            }
            
            @font-face {
                font-family: "Jost";
                src: url("/fonts/Jost-Medium.ttf") format("truetype");
                font-weight: 500;
                font-style: normal;
                font-display: swap;
            }
            
            @font-face {
                font-family: "Jost";
                src: url("/fonts/Jost-SemiBold.ttf") format("truetype");
                font-weight: 600;
                font-style: normal;
                font-display: swap;
            }
            
            @font-face {
                font-family: "Jost";
                src: url("/fonts/Jost-Bold.ttf") format("truetype");
                font-weight: 700;
                font-style: normal;
                font-display: swap;
            }*/
            .page, .page-break { 
                break-after: page; 
                clear: both;
                page-break-after: always;
            }
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
            table, table tr, table tr td, table tr th{
                border: 1px solid #000;
                border-collapse: collapse;
                padding: 10px;
            }
            table tr td.group{
                border: none;
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
            .agreement_list{
                background-color: #dde8f7;
                padding: 10px;
                margin: 10px 0;
                font-size: 95%;
            }
            .agreement_list h4{
                font-weight: 600;
                font-size: 14px;
                margin: 0;
            }
            .agreement_list table{
                width: 100%;
                margin: 0;
            }
            .agreement_list table,
            .agreement_list table td,
            .agreement_list table th{
                background-color: #fff;
                border-collapse: collapse;
                border: 1px solid #c5d4fc;
                font-size: 95%;
                vertical-align: middle;
            }
            .agreement_list table td{
                padding: 5px;
                line-height: 10px;
            }
            .agreement_list table th{
                padding: 5px;
            }
            .agreement_list table th{
                background-color: #e5e5e5;
                color:  #4f6396;
                font-size: 95%;
                text-align: center;
            }
            .agreement_list table td.divider{
                font-size: 80%;
                background-color: #dde8f7;
                padding: 0 2px;
                line-height: 10px;
            }
            .agreement_list table td.center{
                text-align: center;
            }
            .agreement_list .list_type{
                text-align: right;
                margin-top: -25px;
            }
        </style>
        
        <title>Документ</title>
    </head>
    <body><footer>Документ создан в электронной форме. №&nbsp;'.$tmpl->doc_number.' от '.
    $date->correctDateFormatFromMysql($tmpl->created_at).'. 
    Исполнитель: '.$initiator_fio.'<img src="data:image/png;base64,' . $temp->bottom_logo . '"></footer><main>';

if($tmpl->documentacial == 3 || $tmpl->documentacial == 0){
    //Если это план проверок
    $orientation = 'landscape';
    $footer_position = 576;
}else{
    $orientation = 'portrait';
    $footer_position = 820;
}

if($tmpl->documentacial == 6){

    $agreementlist = json_decode($tmpl->agreementlist, true);
    $list_types = [];
    for ($i = 0; $i < count($agreementlist); $i++) {
        $itemArr = json_decode($agreementlist[$i], true);
        if (!in_array($itemArr[0]['list_type'], $list_types)) {
            $list_types[] = $itemArr[0]['list_type'];
        }
    }
    reset($agreementlist);

    $html .= 'Лист согласования к документу ' . $tmpl->consultation . '<br>' .
        'Инициатор согласования: ' . $initiator_fio . ' ' . $initiator_position . '<br>' .
        'Согласование инициировано: ' . $tmpl->initiation/* . '<br>' .
        'Краткое содержание: ' . $tmpl->brief*/;

    $html .= '<div class="agreement_list"><h4>ЛИСТ СОГЛАСОВАНИЯ</h4>' .
        '<div class="list_type">Тип согласования: <strong>' . (count($list_types) > 1 ? 'смешанное' :
            ($list_types[0] == '1' ? 'последовательное' : 'параллельное')) . '</strong></div>' .
        '<table><tr><th>№</th><th style="width: 30%;">ФИО</th><th style="width: 20%">Срок согласования</th><th style="width:25%">Результат согласования</th><th>Комментарии</th></tr>';

    //Листаем секции
    for ($i = 0; $i < count($agreementlist); $i++) {
        $itemArr = json_decode($agreementlist[$i], true); //print_r($itemArr);
        $html .= '<tr><td class="divider" colspan="5">'.
            (isset($itemArr[0]['stage']) ? '<strong>Этап '.$itemArr[0]['stage'].'</strong><br>' : '') .
            'Тип согласования: <strong>' .
            (isset($itemArr[0]['list_type']) && $itemArr[0]['list_type'] == '1' ? 'последовательное' : 'параллельное') . '</strong></td>';
        $html .= buildArgeementList($itemArr, $i, $users, $urgent_types, $user_signs);

    }

    $html .= '</table></div>';

} elseif($tmpl->documentacial == 3) {
    $html .= $reg->buildPlanDocument($data, $docId);
}else {
    if (strlen($tmpl->header) > 0) {
        $html .= $temp->twig_parse($tmpl->header, []);
    }
    if (strlen($tmpl->body) > 0) {
        $html .= $temp->twig_parse($tmpl->body, []);
    }
    if (strlen($tmpl->bottom) > 0) {
        $html .= $temp->twig_parse($tmpl->bottom, []);
    }
}

$html .= '</main>
        </body>
        </html>';
//echo $html;
//$html = mb_convert_encoding($html, 'Windows-1251', 'UTF-8');
// instantiate and use the dompdf class
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Jost');
$options->set('defaultEncoding', 'UTF-8');
$options->set('isRemoteEnabled', true);
//$options->set('isFontSubsettingEnabled', true);
$dompdf = new Dompdf($options);
/*$dompdf->getFontMetrics()->registerFont(
    ['family' => 'Jost', 'style' => 'normal', 'weight' => 'normal'],
    $_SERVER['DOCUMENT_ROOT'].'/fonts/Jost-Regular.ttf'
);*/
$dompdf->loadHtml($html, 'UTF-8');

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', $orientation);//landscape

// Render the HTML as PDF
$dompdf->render();
$fontMetrics = $dompdf->getFontMetrics();
$fontFamilies = $fontMetrics->getFontFamilies();
//print_r($fontFamilies);
$canvas = $dompdf->getCanvas();
$footerText = 'Страница {PAGE_NUM} из {PAGE_COUNT}. Страница создана: '.$date->dateToString($tmpl->created_at);
$canvas->page_text(8, $footer_position, $footerText, 'DejaVu Sans', 8, [0, 0, 0]);

// Output the generated PDF to Browser
//$dompdf->stream();
if(isset($_POST['pdfOnly']) && intval($_POST['pdfOnly']) == 1){
    echo base64_encode($dompdf->output());
}else{
?>
<div class='pop_up drag' style="width: 70vw; min-height: 70vh;">
    <div class='title handle'>
        <div class='name'>Просмотр документа <?=$shortName?></div>
        <div class='button icon close'><span class='material-icons'>close</span></div>
    </div>
    <div class='pop_up_body'>
        <iframe id='pdf-viewer' width='100%' height='600px' style="min-height: 80vh"></iframe>
        <div class='confirm'>
            <?
            if($is_inst){
                ?>
                <button class='button icon close' id="act_agree"><span class='material-icons'>check</span>С актом ознакомлены</button>
            <?
            }else{
            ?>
            <button class='button icon close'><span class='material-icons'>close</span>Закрыть</button>
            <?
            }
            ?>
        </div>
    </div>

</div>
<script>
    $("#act_agree").off("click").on("click", function(){
        $.post("/", {ajax: 1, action: "act_agree", user_id: <?=$_SESSION['user_id']?>, act_id: <?=$docId?>}, function(data){
            let answer = JSON.parse(data);
            if(answer.result){
                inform('Отлично!', answer.resultText);
            }else{
                el_tools.notify('error', 'Ошибка', answer.resultText);
            }
        })
    });
    document.getElementById('pdf-viewer').src = `data:application/pdf;base64,<?= base64_encode($dompdf->output()) ?>`;
</script>
<?php
}