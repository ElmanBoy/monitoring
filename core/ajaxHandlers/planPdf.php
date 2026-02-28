<?php
//error_reporting(E_ALL);
use Core\InstitutionDeclension;
use Core\PersonNameDeclension;
use Core\PositionDeclension;
use Core\Registry;
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
$reg = new Registry();

$planId = is_array($_POST['params']) ? intval($_POST['params']['docId']) : intval($_POST['params']);
$docType = isset($_POST['params']['docType']) ? intval($_POST['params']['docType']) : 0;
$docId = 0;
$data = [];
$doc_data = [];
$html = '';
$docName = '';
$documentacial = 3;
$agreementlist = [];
$outputType = isset($_POST['outputType']) ? intval($_POST['outputType']) : 1;
$users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name', 'position']);
$ins = $db->getRegistry('institutions');

if(isset($_POST['data']) && strlen($_POST['data']) > 0) { //Предпросмотр документа во время создания
    //Если данные плана переданы для предпросмотра
    parse_str($_POST['data'], $data);
//print_r($data);
    $plan = $db->selectOne('checksplans', ' uid = ? OR plan = ?', [$data['uid'], $data['plan']]);
    $agr = $db->selectOne('agreement', ' source_table = ? AND source_id = ?', ['checksplans', $plan->id]);
    $document = $db->selectOne('documents', " WHERE id = ?", [$data['document']]);
    $docId = $agr->id;
    $documentacial = intval($document->documentacial);

    if($documentacial == 3) { //Предпросмотр плана
        $plan = $db->selectOne('checksplans', ' uid = ?', [$data['uid']]);
        $agr = $db->selectOne('agreement', ' source_table = ? AND source_id = ?', ['checksplans', $plan->id]);
        $docId = $agr->id;
        $data['agreement_date'] = $date->dateToString($agr->docdate);//TODO: выяснить почему не выводится дата
    }else{ //Предпросмотр иного документа
        //$tmpl = $db->selectOne('documents', " WHERE id = ?", [$data['document']]);

        $executorsArr = [];
        $executors = '';
        if(is_array($data['executors_list']) && count($data['executors_list']) > 0){
            foreach($data['executors_list'] as $ex){
                $executorsArr[] = PersonNameDeclension::decline(trim($users['array'][$ex][0]).' '.
                    trim($users['array'][$ex][1]).' '.
                    trim($users['array'][$ex][2]), 'accusative').', '.
                    PositionDeclension::decline(mb_strtolower(trim($users['array'][$ex][3])), 'accusative');
            }
            $executors = implode(';<br>', $executorsArr);
        }

        $actionPeriodArr = [];
        $checkPeriodArr = [];
        if(strlen($data['action_period_hidden'][0]) > 0) {
            if(substr_count($data['action_period_hidden'][0], '[') > 0) {
                $actionPeriodArr = explode(' - ', $date->getMonthDateRange(json_decode($data['action_period_hidden'][0])));
            }
        }else{
            $actionPeriodArr = explode(' - ', $data['actionPeriod']);
        }
        $checkPeriodArr = explode(' - ', $data['check_period']);
        $ins_name = htmlspecialchars(stripslashes($ins['array'][$data['ins']]));
        $ins_genitive = InstitutionDeclension::decline($ins_name, 'genitive');

        $doc_data = [
            'minDate' => $data['minDate'],
            'maxDate' => $data['maxDate'],
            'order_date' => $date->dateToString($data['order_date']),
            'order_number' => $data['order_number'],
            'doc_number' => $data['order_number'],
            'institution' => $ins_name,
            'check_institution' => $ins_name,
            'institution_genitive' => $ins_genitive,
            'plan_year' => $plan->year,
            'agreement_date' => $date->dateToString($agr->docdate),
            'action_period_start' => $date->dateToString($actionPeriodArr[0]),
            'action_period_end' => $date->dateToString($actionPeriodArr[1]),
            'check_period_start' => $date->dateToString($checkPeriodArr[0]),
            'check_period_end' => $date->dateToString($checkPeriodArr[1]),
            'executors_head' => PersonNameDeclension::decline(trim($users['array'][$data['executors_head']][0]).' '.
                trim($users['array'][$data['executors_head']][1]).' '.
                trim($users['array'][$data['executors_head']][2]), 'accusative').', '.
                PositionDeclension::decline(mb_strtolower(trim($users['array'][$data['executors_head']][3])), 'accusative'),
            'executors' => $executors
        ];
    }
    $outputType = 0;
}elseif($planId > 0){ //Просмотр документа из базы данных
    $subQuery = '';
    if($docType > 0){
        $subQuery = "source_id = ? AND documentacial = ".$docType;
    }else{
        $subQuery = 'id = ?';
    }
    //echo $subQuery;
    $agr = $db->selectOne('agreement', $subQuery, [$planId]);
    //print_r($agr);
    //$document = $db->selectOne('documents', ' WHERE id = ?', [$agr->document]);
    $documentacial = intval($agr->documentacial);
    //print_r($agr);
    if($documentacial == 3) { //Просмотр сохраненного плана
        $plan = $db->selectOne('checksplans', ' id = ?', [$agr->source_id]);
        $docId = $agr->id;
        $docName = $plan->short;
        $planData = json_decode($plan->addinstitution, true);
        if (is_array($planData) && count($planData) > 0) {
            for ($i = 0; $i < count($planData); $i++) {
                $action_start = $date->getMonthNameByNumber(intval($planData[$i]['periods_hidden'][0]));
                $data['check_types'][$i] = $plan->checks;
                $data['institutions'][$i] = $planData[$i]['institutions'];
                $data['units'][$i] = $planData[$i]['units'];
                $data['periods'][$i] = $planData[$i]['periods'];
                $data['periods_hidden'][$i] = $planData[$i]['periods_hidden'];
                $data['start_month'] = $action_start;
                $data['inspections'][$i] = $plan->inspections;
                $data['check_periods'][$i] = $planData[$i]['check_periods'];
            }
        }
        $data['inspections'] = $plan->inspections;
        $data['initiator'] = $agr->initiator;
        $data['checks'] = $plan->checks;
        $data['longname'] = $plan->longname;
        $data['year'] = $plan->year;
        $data['agreementlist'] = json_decode($agr->agreementlist, true);
        $data['agreement_date'] = $date->dateToString($agr->docdate);
        $data['document'] = $agr->document;
        $docId = $agr->id;
        //print_r($data);
    }else{ //Просмотр иного сохраненного документа, не плана
        $executorsArr = [];
        $executors = '';
        $executors_list = json_decode($agr->executors_list, true);
        $docName = $agr->name;

        if(is_array($executors_list) && count($executors_list) > 0){
            foreach($executors_list as $ex){
                $executorsArr[] = PersonNameDeclension::decline(trim($users['array'][$ex][0]).' '.
                    trim($users['array'][$ex][1]).' '.
                    trim($users['array'][$ex][2]), 'accusative').', '.
                    PositionDeclension::decline(mb_strtolower(trim($users['array'][$ex][3])), 'accusative');
            }
            $executors = implode(';<br>', $executorsArr);
        }
        $actionPeriodArr = [];
        if(strlen($agr->action_period) > 0) {
            if(substr_count($agr->action_period, '[') > 0) {
                $actionPeriodArr = explode(' - ', $date->getMonthDateRange(json_decode($agr->action_period)));
            }else{
                $actionPeriodArr = explode(' - ', $agr->action_period);
            }
        }
        $checkPeriodArr = explode(' - ', $agr->check_period);
        $ins_name = htmlspecialchars(stripslashes($ins['array'][$agr->ins_id]));
        $ins_genitive = InstitutionDeclension::decline($ins_name, 'genitive');

        $doc_data = [
            'order_date' => $date->dateToString($agr->docdate),
            'order_number' => $agr->doc_number,
            'doc_number' => $agr->doc_number,
            'initiator' => $agr->initiator,
            'document' => $agr->document,
            'institution' => $ins_name,
            'institution_genitive' => $ins_genitive,
            'check_institution' => $ins_name,
            'check_institution_genitive' => $ins_genitive,
            //'plan_year' => $plan->year,
            'action_period_start' => $date->dateToString($actionPeriodArr[0]),
            'action_period_end' => $date->dateToString($actionPeriodArr[1]),
            'check_period_start' => $date->dateToString($checkPeriodArr[0]),
            'check_period_end' => $date->dateToString($checkPeriodArr[1]),
            'executors_head' => PersonNameDeclension::decline(trim($users['array'][$agr->executors_head][0]).' '.
                trim($users['array'][$agr->executors_head][1]).' '.
                trim($users['array'][$agr->executors_head][2]), 'accusative').', '.
                PositionDeclension::decline(mb_strtolower(trim($users['array'][$agr->executors_head][3])), 'accusative'),
            'executors' => $executors,
           'agreementlist' => json_decode($agr->agreementlist, true)
        ];
    }
    $docId = $agr->id;
    //$outputType = 1;
}else{
    $html .= '<p>Не передан id документа</p>';
}
//print_r($data);
$initiator = intval($data['initiator']) ?? intval($doc_data['initiator']);


$initiator_fio = $users['array'][$initiator][0] . ' ' . $users['array'][$initiator][1] . ' ' .
    $users['array'][$initiator][2];
$initiator_position = $users['array'][$initiator][3];

$orientation = 'landscape';
$footer_position = 576;
$data = array_merge($data, $doc_data);

$html .= '<html lang="ru">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            
        @font-face {
            font-family: "Times New Roman";
            font-style: normal;
            font-weight: 400;
            src: url("/fonts/timesnrcyrmt.ttf") format("truetype");
        }
        @font-face {
            font-family: "Times New Roman";
            font-style: italic;
            font-weight: 400;
            src: url("/fonts/timesnrcyrmt_inclined.ttf") format("truetype");
        }
        @font-face {
            font-family: "Times New Roman";
            font-style: normal;
            font-weight: 700;
            src: url("/core/vendor/dompdf/dompdf/lib/fonts/Times-Bold.ttf") format("truetype");/*/fonts/timesnrcyrmt_bold.ttf*/
        }
        @font-face {
            font-family: "Times New Roman";
            font-style: italic;
            font-weight: 700;
            src: url("/fonts/timesnrcyrmt_boldinclined.ttf") format("truetype");
        }
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
                line-height: 18px;
            }
            table, table tr, table tr td, table tr th{
                border: 1px solid #000;
                border-collapse: collapse;
                padding: 10px;
            }
            table tr td.group{
                border: none;
            }
            strong, b{
                font-weight: 900;
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
                line-height: 14px;
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
    <body><footer>Документ создан в электронной форме. №&nbsp;'.$doc_data['doc_number'].' от '.
    $date->correctDateFormatFromMysql(date('Y-m-d H:i:s')).'. 
    Исполнитель: '.$initiator_fio.'<img src="data:image/png;base64,' . $temp->bottom_logo . '"></footer><main>';

if($documentacial == 3 || $documentacial == 0){
    //Если это план проверок
    $orientation = 'landscape';
    $footer_position = 576;
}else{
    $orientation = 'portrait';
    $footer_position = 820;
}
$html .= $reg->buildPlanDocument($data, $docId);

$html .= '</main>
        </body>
        </html>';
//echo $html;
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Times-Roman');
$options->set('defaultEncoding', 'UTF-8');
$options->set('isRemoteEnabled', true);
//$options->set('isFontSubsettingEnabled', true);
$dompdf = new Dompdf($options);


$dompdf->loadHtml(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', $orientation);//landscape

// Render the HTML as PDF
$dompdf->render();
/*$fontMetrics = $dompdf->getFontMetrics();
$fontFamilies = $fontMetrics->getFontFamilies();
print_r($fontFamilies);*/
$canvas = $dompdf->getCanvas();
$footerText = 'Страница {PAGE_NUM} из {PAGE_COUNT}. Страница создана: '.$date->dateToString(date('Y-m-d H:i:s'));
$canvas->page_text(8, $footer_position, $footerText, 'DejaVu Sans', 8, [0, 0, 0]);

$pdfData = base64_encode($dompdf->output());
//echo $outputType;
if($outputType == 0){
    echo $pdfData;
}else{
    echo "
<div class='pop_up drag' style='width: 60vw'>
    <div class='title handle'>
        <div class='name'>Просмотр документа &laquo;".$docName."&raquo;</div>
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
  // После генерации PDF:
    document.getElementById('pdf-viewer').src = `data:application/pdf;base64,$pdfData`;
</script>";
}

