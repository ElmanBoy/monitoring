<?php

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
if(isset($_POST['params'])) {
    if(is_array($_POST['params'])) {
        $docId = intval($_POST['params']['docId']);
    }else{
        $docId = intval($_POST['params']);
    }
}
$user_signs = [];

$tmpl = $db->selectOne('agreement', ' where id = ?', [$docId]);
$docs = $db->selectOne('agreement', ' WHERE id = ?', [$tmpl->consultation]);
$signs = $db->select('signs', " where table_name = 'agreement' AND  doc_id = ?", [$docId]);

$ins = $db->getRegistry('institutions', '', [], ['short']);
$mins = $db->getRegistry('ministries');
$units = $db->getRegistry('units');

//$tmpl = $db->selectOne('agreement', ' where id = ?', [$docId]);
$data['agreementlist'] = json_decode($tmpl->agreementlist, true);
$data['document'] = $tmpl->document;
$data['agreement_date'] = $tmpl->docdate;
if ($tmpl->source_table) {
    $plan = $db->selectOne($tmpl->source_table, ' WHERE id = ?', [$tmpl->source_id]);
    $data['longname'] = $plan->longname;
    $addinstitution = json_decode($plan->addinstitution, true);
    $a = 0;
    if (is_array($addinstitution) && count($addinstitution) > 0) {
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

if (count($signs) > 0) {
    foreach ($signs as $s) {
        $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
    }
}

$users = $db->getRegistry('users', '', [],
    ['surname', 'name', 'middle_name', 'institution', 'ministries', 'division', 'position']
);
$initiator_fio = $users['array'][$tmpl->initiator][0] . ' ' . $users['array'][$tmpl->initiator][1] . ' ' .
    $users['array'][$tmpl->initiator][2];
$initiator_position = $users['array'][$tmpl->initiator][6];

$urgent_types = [
    1 => 'Обычный',
    2 => '<span style="color: #d8720b">Срочный</span>',
    3 => '<span style="color: #d8110b">Незамедлительно</span>'
];

//print_r($tmpl);
$html = '
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
            @font-face {
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
            }
            #agreement_block { 
                font-family: "Jost", sans-serif;
                font-weight: normal;
                font-size: 14px;
                font-kerning: auto;
                hyphens: auto;
                line-height: 20px;
            }
            main { 
                font-family: "Times-Roman", "Times New Roman", serif;
                font-weight: normal;
                font-size: 17px;
                font-kerning: auto;
                hyphens: auto;
                line-height: 20px;
                margin: 0 1cm;
                padding: 1cm 0 0 0;
            }
            #agreement table.table_data{
                margin: 25px 0 25px 0;
            }
            #agreement table.table_data, 
            #agreement table.table_data tr, 
            #agreement table.table_data tr td, 
            #agreement table.table_data tr th{
                border: 1px solid #000;
                border-collapse: collapse;
                padding: 10px;
                cursor: default;
            }
            #agreement table tr td.group{
                border: none;
                margin: 0;
            }
            .table_data .fixed_thead {
                top: 46px;
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
                margin: 15px 0;
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
            .agreement_list table tbody.notComplete ~ tbody{
                opacity: .2;
                cursor: n-resize;/*not-allowed;*/
            }
            .agreement_list table tbody.notComplete ~ tbody td .actions,
            .agreement_list table tbody.notComplete ~ tbody td .el_data{
                display: none;
            }
            .agreement_list table td{
                padding: 5px;
            }
            .agreement_list table th{
                padding: 0 15px;
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
                line-height: 14px;
            }
            .agreement_list table td.center{
                text-align: center;
            }
            .agreement_list .list_type{
                text-align: right;
                margin-top: -25px;
            }
            .button_registry, .button_registry_edit{
                display: none; 
            }
            .agreement_list .item.w_50{
                width: 100%;
                margin-top: 20px;
            }
            .agreement_list button{
                margin: 5px 0;
                padding: 5px 10px;
            }
            .agreement_list tr.redirected td:nth-child(2){
                padding-left: 15px;
            }
        </style>
        <main>';

function checkStageComplete(array $itemArr): bool
{
    $itemUsers = [];
    $itemResults = [];
    foreach ($itemArr as $item) {
        if (isset($item['id'])) {
            $itemUsers[] = $item['id'];
            if (isset($item['result']) && !in_array($item['result']['id'], [4, 5])) {
                $itemResults[] = $item['result'];
            }
        }
    }
    return count($itemUsers) <= count($itemResults);
}


if ($tmpl->documentacial == 6) {

    $agreementlist = json_decode($tmpl->agreementlist, true);
    $list_types = [];
    for ($i = 0; $i < count($agreementlist); $i++) {
        $itemArr = json_decode($agreementlist[$i], true);
        if (!in_array($itemArr[0]['list_type'], $list_types)) {
            $list_types[] = $itemArr[0]['list_type'];
        }
    }
    reset($agreementlist);

    $html .= 'Лист согласования к документу &laquo;' . $docs->name .
        (strlen($docs->doc_number) > 0 ? ' № ' . $docs->doc_number : '') . '&raquo;<br>' .
        'Инициатор согласования: ' . $initiator_fio . ' ' . $initiator_position . '<br>' .
        'Согласование инициировано: ' . $tmpl->initiation/* . '<br>' .
        'Краткое содержание: ' . $tmpl->brief*/
    ;

    $html .= '<div class="agreement_list"><h4>ЛИСТ СОГЛАСОВАНИЯ</h4>' .
        '<div class="list_type">Тип согласования: <strong>' . (count($list_types) > 1 ? 'смешанное' :
            ($list_types[0] == '1' ? 'последовательное' : 'параллельное')) . '</strong></div>' .
        '<table><tr><th>№</th><th style="width: 30%;">ФИО</th><th>Срок согласования</th><th style="width:30%">Результат согласования</th><th>Комментарии</th></tr>';

    //Листаем секции
    for ($i = 0; $i < count($agreementlist); $i++) {
        $itemArr = json_decode($agreementlist[$i], true);

        //Определяем завершен ли этап
        $stageComplete = checkStageComplete($itemArr);

        $html .= '<tbody' . ($stageComplete ? '' : ' class="notComplete"') . '><tr><td class="divider" colspan="5">' .
            (isset($itemArr[0]['stage']) && intval($itemArr[0]['stage']) > 0 ? '<strong>Этап ' . $itemArr[0]['stage'] . '</strong><br>' : '') .
            'Тип согласования: <strong>' .
            (isset($itemArr[0]['list_type']) && $itemArr[0]['list_type'] == '1' ? 'последовательное' : 'параллельное') . '</strong>' .
            '<input type="hidden" name="addAgreement" id="ag' . $i . '" value=\'' . $agreementlist[$i] . '\'></td>';

        //Отрисовка секции списка
        $html .= $reg->buildAgreementList($itemArr, $i, $users,
            $urgent_types, $user_signs, $reg
        );
        $html .= '</tbody>';

    }

    $html .= '</table></div>';

} elseif ($tmpl->documentacial == 3) {
    //$html .= $reg->buildPlanDocument($data, $docId);
} else {
    /*if (strlen($tmpl->header) > 0) {
        $html .= $temp->twig_parse($tmpl->header, []);
    }
    if (strlen($tmpl->body) > 0) {
        $html .= $temp->twig_parse($tmpl->body, []);
    }
    if (strlen($tmpl->bottom) > 0) {
        $html .= $temp->twig_parse($tmpl->bottom, []);
    }*/
}
//[[{"stage":"1","urgent":"1","list_type":"2"},{"id":56,"type":2,"vrio":"0","result":{"id":"2","date":"2025-12-12 14:44:53"},"urgent":"1"}], [{"stage":"","list_type":"2","urgent":"1"},{"id":56,"type":1,"urgent":"1","vrio":"","role":"0"},{"id":7,"type":1,"urgent":"1","vrio":"","role":"1"}]]
//if (intval($tmpl->agreementtemplate) > 0) {

//$tmpl = $db->selectOne('agreement', ' where id = ?', [$tmpl->agreementtemplate]);
//$docs = $db->selectOne('agreement', ' WHERE id = ?', [$tmpl->consultation]);
//$signs = $db->select('signs', " where table_name = 'agreement' AND  doc_id = ?", [$tmpl->agreementtemplate]);
if (count($signs) > 0) {
    foreach ($signs as $s) {
        $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
    }
}
$users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name', 'position']);
$initiator_fio = $users['array'][$tmpl->initiator][0] . ' ' . $users['array'][$tmpl->initiator][1] . ' ' .
    $users['array'][$tmpl->initiator][2];
$initiator_position = $users['array'][$tmpl->initiator][6];

$agreementlist = json_decode($tmpl->agreementlist, true);
if (is_array($agreementlist) && count($agreementlist) > 0) {
    $list_types = [];
    for ($i = 0; $i < count($agreementlist); $i++) {
        $itemArr = $agreementlist[$i];//json_decode($agreementlist[$i], true);

        if (isset($itemArr[0]['list_type']) && !in_array($itemArr[0]['list_type'], $list_types)) {
            $list_types[] = $itemArr[0]['list_type'];
        }
    }
    reset($agreementlist);

    $html .= /*<div style="height: 200px">*/
        '<div id="agreement_block">Лист согласования к документу &laquo;' . $tmpl->name . '&raquo;<br>' .
        'Инициатор согласования: ' . $initiator_fio . ' ' . $initiator_position . '<br>' .
        'Согласование инициировано: ' . $tmpl->initiation/* . '<br>' .
                'Краткое содержание: ' . $tmpl->brief*/
    ;

    $html .= '<div class="agreement_list"><h4>ЛИСТ СОГЛАСОВАНИЯ</h4>' .
        '<div class="list_type">Тип согласования: <strong>' . (count($list_types) > 1 ? 'смешанное' :
            ($list_types[0] == '1' ? 'последовательное' : 'параллельное')) . '</strong></div>' .
        '<table class="agreement-table"><tr>'.
        '<th>№</th><th style="width: 30%;">ФИО</th>'.
        '<th>Срок согласования</th>'.
        '<th style="width:30%">Результат согласования</th>'.
        '<th>Комментарии</th></tr>';

    //Листаем секции
    for ($i = 0; $i < count($agreementlist); $i++) {
        $itemArr = $agreementlist[$i];//json_decode($agreementlist[$i], true);
        if (is_array($itemArr) && count($itemArr) > 0) {
            //Определяем завершен ли этап
            $stageComplete = checkStageComplete($itemArr);

            $html .= '<tbody' . ($stageComplete ? '' : ' class="notComplete"') . '><tr><td class="divider" colspan="5">' .
                (isset($itemArr[0]['stage']) && intval($itemArr[0]['stage']) > 0 ? '<strong>Этап ' . $itemArr[0]['stage'] . '</strong><br>' :
                    '<strong>Подписанты</strong><br>') .
                'Тип согласования: <strong>' .
                (isset($itemArr[0]['list_type']) && $itemArr[0]['list_type'] == '1' ? 'последовательное' : 'параллельное') . '</strong>' .
                '<input type="hidden" name="addAgreement" id="ag' . $i . '" value=\'' . json_encode($agreementlist[$i]) . '\'></td>';
            if (is_array($itemArr)) {
                $html .= $reg->buildAgreementList($itemArr, $i, $users,
                    $urgent_types, $user_signs, $reg
                );
            }
        }
        $html .= '</tbody>';

    }

    $html .= '</table></div></div>';


}
//}

$html .= '</main>';


?>
<div class='pop_up drag' style="width: 60vw; min-height: 70vh;">
    <div class='title handle'>
        <div class='name'>Согласование документа &laquo;<?= $tmpl->name ?>&raquo;</div>
        <div class='button icon close'><span class='material-icons'>close</span></div>
    </div>
    <div class='pop_up_body'>
        <ul class='tab-pane' style=''>
            <li id='tab_agreement' class="active">Согласование</li>
            <li id='tab_preview'>Предпросмотр</li>
        </ul>
        <div class='agreement_block tab-panel' id='tab_agreement-panel'>
            <?= $html ?>
        </div>
        <div class='preview_block tab-panel' id='tab_preview-panel' style='display: none'>
            <iframe id='pdf-viewer' width='100%' height='600px'></iframe>
        </div>
        <div class='confirm'>

            <button class='button icon close'><span class='material-icons'>close</span>Закрыть</button>
        </div>
    </div>
    <script src="/js/assets/cades_sign.js"></script>
    <script>
        function dateNow() {
            const now = new Date();
            return `${String(now.getDate()).padStart(2, '0')}.` +
                `${String(now.getMonth() + 1).padStart(2, '0')}.` +
                `${now.getFullYear()} ` +
                `${String(now.getHours()).padStart(2, '0')}:` +
                `${String(now.getMinutes()).padStart(2, '0')}`;
        }

        function getAgreementList(agj, section, result_type, fallowIds = [], level = 0) {
            let $actions = $('.actions[data-section=' + section + ']'),
                $comment = $actions.closest('td').next('td').find('[name=comment]'),
                $redirect = $actions.find('[name="redirect[]"]'),
                currentDateTime = dateNow();

            for (let i = 0; i < agj.length; i++) {
                if (parseInt(agj[i].id) === <?=$_SESSION['user_id']?>) {
                    if (parseInt(result_type) === 0) {
                        // Только комментарий
                        agj[i].comment = $comment.val();
                    } else if (parseInt(result_type) === 4) {
                        // Перенаправление
                        let vals = $redirect.val() || [];

                        if (vals.length > 0) {
                            // Сохраняем исходные данные сотрудника для повторной записи
                            let originalData = {
                                id: agj[i].id,
                                type: agj[i].type,
                                vrio: agj[i].vrio || '0',
                                urgent: agj[i].urgent || '0',
                                role: agj[i].role || '0'
                            };

                            // Устанавливаем результат перенаправления
                            agj[i].result = {id: 4, date: currentDateTime};

                            // Добавляем redirect если его нет
                            if (!agj[i].redirect) {
                                agj[i].redirect = [];
                            }

                            // Добавляем новых сотрудников в redirect
                            for (let v = 0; v < vals.length; v++) {
                                let exists = agj[i].redirect.some(item => parseInt(item.id) === parseInt(vals[v]));
                                if (!exists) {
                                    agj[i].redirect.push({id: parseInt(vals[v]), type: agj[i].type});
                                }
                            }

                            // Запоминаем для добавления повторной записи
                            if (level === 0) {
                                fallowIds.push({
                                    index: i,
                                    data: originalData
                                });
                            }
                        }
                    } else {
                        // Согласование/подписание/отклонение
                        agj[i].result = {id: parseInt(result_type), date: currentDateTime};

                        // Если отклонение - удаляем redirect если был
                        if (parseInt(result_type) === 5 && agj[i].redirect) {
                            delete agj[i].redirect;
                        }
                    }
                } else if (agj[i].redirect && Array.isArray(agj[i].redirect)) {
                    // Рекурсивно обрабатываем перенаправления
                    getAgreementList(agj[i].redirect, section, result_type, fallowIds, level + 1);
                }
            }

            // Добавляем повторные записи ПОСЛЕ обработки всех перенаправлений
            if (fallowIds.length > 0 && level === 0) {
                // Сортируем по индексу в обратном порядке, чтобы не сбивать индексы
                fallowIds.sort((a, b) => b.index - a.index);

                for (let f = 0; f < fallowIds.length; f++) {
                    let repeatEntry = {
                        id: fallowIds[f].data.id,
                        type: fallowIds[f].data.type,
                        vrio: fallowIds[f].data.vrio,
                        urgent: fallowIds[f].data.urgent,
                        role: fallowIds[f].data.role,
                        result: null // Важно! Нет результата, ждёт действия
                    };

                    // Вставляем сразу после текущей записи
                    agj.splice(fallowIds[f].index + 1 + f, 0, repeatEntry);
                }
            }

            return agj;
        }

        function getAgreementData(section, result_type) {
            let $ag = $('#ag' + section),
                agj = JSON.parse($ag.val());

            let fallowIds = [];
            let agjResult = getAgreementList(agj, section, result_type, fallowIds, 0);

            $ag.val(JSON.stringify(agjResult));

            let $agInputs = $('[name=addAgreement]'),
                agList = [];
            for (let a = 0; a < $agInputs.length; a++) {
                agList.push($($agInputs[a]).val());
            }

            $.post("/", {
                ajax: 1,
                action: "updateAgreement",
                agreementList: agList,
                docId: <?=$docId?>
            }, function (data) {
                let answer = JSON.parse(data);
                if (answer.result) {
                    inform('Отлично!', answer.resultText);

                    // Обновляем значение поля
                    $ag.val(JSON.stringify(answer.resultAgreement[section]));

                    // Динамически обновляем таблицу
                    refreshAgreementTable(answer.resultAgreement);

                } else {
                    el_tools.notify('error', 'Ошибка', answer.resultText);
                }
            });
        }

        // Новая функция для динамического обновления таблицы
        function refreshAgreementTable(agreementList) {
            $('.preloader').fadeIn('fast');

            $.post('/', {
                ajax: 1,
                action: 'renderAgreementTable',
                agreementList: agreementList,
                docId: <?=$docId?>
            }, function (data) {
                let answer = JSON.parse(data);
                if (answer.result) {
                    // ПОЛНОСТЬЮ ЗАМЕНЯЕМ ВЕСЬ .agreement_list
                    $('.agreement_list').replaceWith(answer.html);
                    reinitEvents();
                }
                $('.preloader').fadeOut('fast');
            });
        }

        // Переинициализация событий после обновления DOM
        function reinitEvents() {
            // Перепривязываем события кнопок
            $(".setAgree").off("click").on("click", function (e) {
                e.preventDefault();
                let section = $(this).closest('.actions').data('section'),
                    currentDateTime = dateNow();
                getAgreementData(section, 3);
                $('.actions[data-section=' + section + ']').hide();
                $('#agResult' + section).html("<span style='color: #086a9b'>Согласовано<br>" + currentDateTime + '</span>');
            });

            $('.setReject').off('mousedown keydown').on('mousedown keydown', function (e) {
                e.preventDefault();
                let $comment = $(this).closest('td').next('td').find('[name=comment]'),
                    comment = $comment.val();
                if ($.trim(comment) === "") {
                    alert("Сначала введите причину отклонения.");
                    $comment.trigger("focus");
                } else {
                    let section = $(this).closest('.actions').data('section'),
                        currentDateTime = dateNow();
                    getAgreementData(section, 5);
                    $('.actions[data-section=' + section + ']').hide();
                    $('#agResult' + section).html("<span style='color: var(--red)'>Отклонено<br>" + currentDateTime + '</span>');
                }
            });

            $('[name=comment]').off('blur').on('blur', function (e) {
                e.preventDefault();
                let comment = $(this).val();
                let section = $(this).closest("td").prev("td").find(".actions").data("section");
                getAgreementData(section, 0);
                if ($.trim(comment) !== "") {
                    $(this).closest("td").prev("td").find(".setReject").removeClass("disabled");
                } else {
                    $(this).closest('td').prev('td').find('.setReject').addClass('disabled');
                }
            });

            $('[name=redirect], [name="redirect[]"]').off('change').on('change', function (e, param) {
                e.preventDefault();
                let $self = $(this),
                    section = $(this).closest('.actions').data('section');

                getAgreementData(section, 4);
                $('#agResult' + section).html("<span style='color: #086a9b'>Перенаправлено<br>" + dateNow() + '</span>');

                inform('Перенаправление', 'Документ перенаправлен. Повторная запись появится после согласования цепочки.');
            });

            // Перепривязываем подписи
            bindSign('agreement', <?=$docId?>, <?=$_SESSION['user_id']?>);
        }

        async function loadUser(section, user_id) {
            try {
                const userData = await el_app.getUserById(user_id, 'short');
                $("#" + section + "_" + user_id).text(userData);
            } catch (error) {
                console.error('Не удалось загрузить:', error);
            }
        }

        function getChosenSortedVal(obj) {
            let lis = $(obj).next().find(".search-choice"),
                out = [];
            for (let i = 0; i < lis.length; i++) {
                out.push($(obj).find("option:contains('" + $(lis[i]).find('span').text() + "')").val());
            }
            return out;
        }

        $(document).ready(function () {
            /*
            * 1 - подписание
            * 2 - согласование с эп
            * 3 - согласование
            * 4 - перенапропавление
            * 5 - отклонение*/
            bindSign('agreement', <?=$docId?>, <?=$_SESSION['user_id']?>);

            $(".setAgree").off("click").on("click", function (e) {
                e.preventDefault();
                let section = $(this).closest('.actions').data('section'),
                    currentDateTime = dateNow();
                getAgreementData(section, 3);
                $('.actions[data-section=' + section + ']').hide();
                $('#agResult' + section).html("<span style='color: #086a9b'>Согласовано<br>" + currentDateTime + '</span>');
            });

            $('.setReject').off('mousedown keydown').on('mousedown keydown', function (e) {
                e.preventDefault();
                let $comment = $(this).closest('td').next('td').find('[name=comment]'),
                    comment = $comment.val();
                if ($.trim(comment) === "") {
                    alert("Сначала введите причину отклонения.");
                    $comment.trigger("focus");
                } else {
                    let section = $(this).closest('.actions').data('section'),
                        currentDateTime = dateNow();
                    getAgreementData(section, 5);
                    $('.actions[data-section=' + section + ']').hide();
                    $('#agResult' + section).html("<span style='color: var(--red)'>Отклонено<br>" + currentDateTime + '</span>');
                }
            });

            $('[name=comment]').off('blur').on('blur', function (e) {
                e.preventDefault();
                let comment = $(this).val();
                let section = $(this).closest("td").prev("td").find(".actions").data("section");
                getAgreementData(section, 0);
                if ($.trim(comment) !== "") {
                    $(this).closest("td").prev("td").find(".setReject").removeClass("disabled");
                } else {
                    $(this).closest('td').prev('td').find('.setReject').addClass('disabled');
                }
            });

            $('[name=redirect], [name="redirect[]"]').off('change').on('change', function (e, param) {
                e.preventDefault();
                let $self = $(this),
                    section = $(this).closest('.actions').data('section');

                getAgreementData(section, 4);
                $('#agResult' + section).html("<span style='color: #086a9b'>Перенаправлено<br>" + dateNow() + '</span>');

                inform('Перенаправление', 'Документ перенаправлен. Повторная запись появится после согласования цепочки.');
            });

            let $tab_preview = $('#tab_preview');

            el_app.initTabs();

            $tab_preview.on('click', function () {
                let formData = $('form#registry_create').serialize();
                $('.preloader').fadeIn('fast');
                $.post('/', {
                    ajax: 1, mode: 'popup', module: 'documents', url: 'pdf', pdfOnly: 1,
                    params: {docId: <?=$docId?>}
                }, function (data) {
                    if (data.length > 0) {
                        $('#pdf-viewer').attr('src', 'data:application/pdf;base64,' + data);
                        $('.preloader').fadeOut('fast');
                    }
                })
            });

            $(document).off('doc_signed').on("doc_signed", function (e, param) {
                console.log(param)
                if (param.class.includes("setAgreeSign")) {
                    $(".actions[data-section=" + param.section + "]").hide();
                    getAgreementData(param.section, 2);
                    $("#agResult" + param.section).html("<span style='color: #086a9b'>Согласовано с ЭП<br>" + param.date + "</span>");
                }
                if (param.class.includes('setSign')) {
                    $(".actions[data-section=" + param.section + "]").hide();
                    getAgreementData(param.section, 1);
                    $('#agResult' + param.section).html("<span style='color: #086a9b'>Подписано с ЭП<br>" + param.date + '</span>');
                }
            });
        });
    </script>
</div>