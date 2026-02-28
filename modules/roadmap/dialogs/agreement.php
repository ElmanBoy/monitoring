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
$docId = intval($_POST['params']['docId']);
$user_signs = [];

$tmpl = $db->selectOne('agreement', ' where id = ?', [$docId]);
$docs = $db->selectOne('agreement', ' WHERE id = ?', [$tmpl->consultation]);
$signs = $db->select('signs', " where table_name = 'agreement' AND  doc_id = ?", [$docId]);
if (count($signs) > 0) {
    foreach ($signs as $s) {
        $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
    }
}
$users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name', 'position']);
$initiator_fio = $users['array'][$tmpl->initiator][0] . ' ' . $users['array'][$tmpl->initiator][1] . ' ' .
    $users['array'][$tmpl->initiator][2];
$initiator_position = $users['array'][$tmpl->initiator][3];




//print_r($tmpl);
$html = '
        <style>
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
            main { 
                /*font-family: "Jost", sans-serif; 
                font-size: 3.5mm;*/
                
                font-family: "Jost", sans-serif;
                font-weight: normal;
                font-size: 16px;
                font-kerning: auto;
                hyphens: auto;
                line-height: 20px;
                margin: 1cm;
            }
            #agreement table.table_data, 
            #agreement table.table_data tr, 
            #agreement table.table_data tr td, 
            #agreement table.table_data tr th{
                border: 1px solid #000;
                border-collapse: collapse;
                padding: 10px;
            }
            #agreement table tr td.group{
                border: none;
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
            .button_registry{
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


    if (strlen($tmpl->header) > 0) {
        $html .= $temp->twig_parse($tmpl->header, [], []);
    }
    if (strlen($tmpl->body) > 0) {
        $html .= $temp->twig_parse($tmpl->body, [], []);
    }
    if (strlen($tmpl->bottom) > 0) {
        $html .= $temp->twig_parse($tmpl->bottom, [], []);
    }
    $check = $db->selectOne('checkstaff', ' WHERE id = ?', [$docId]);
    $tasks = $db->select('checkstaff', " WHERE institution = ?", [5]);
    $tids = [];
    if($tasks){
        foreach($tasks as $task){
            $tids[] = $task->id;
        }
    }
    if(count($tids) > 0) {

        $violations = $db->db::getAll('SELECT * FROM ' . TBL_PREFIX . 'checksviolations WHERE tasks IN (' . implode(', ', $tids) . ')');
        if ($violations) {
            $num = 1;
            $html .= '<div style="height: 30px"></div>';
            foreach ($violations as $vi) {
                $html .= '<div class="item w_100"><strong>№'.$num.'.</strong> '.$vi['name'] . '<p>&nbsp;</p>'.
                    '<div class="item w_100">
                        <div class="el_data">
                            <label>Возражения</label>
                            <input type="hidden" name="violation_id[]" value="'.$vi['id'].'">
                            <textarea class="el_textarea" name="objections[]">'.stripslashes(htmlspecialchars($vi['objections'])).'</textarea>
                        </div>
                    </div>'.
                    '</div><hr>';
                $num++;
            }
        }
    }


$html .= '</main>';


?>
<style>
    .violations_list{
        display: none;
    }
</style>
<div class='pop_up drag' style="width: 60vw; min-height: 70vh;">
    <div class='title handle'>
        <div class='name'>Согласование документа</div>
        <div class='button icon close'><span class='material-icons'>close</span></div>
    </div>
    <div class='pop_up_body'>
        <form class='ajaxFrm noreset' id='objections' onsubmit='return false'>
        <?= $html ?>
        <div class='confirm'>

            <button class='button icon text save'><span class='material-icons'>send</span>Отправить</button>
        </div>
        </form>
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
            let $comment = $('.actions[data-section=' + section + ']').closest('td').next('td').find('[name=comment]'),
                $redirect = $('.actions[data-section=' + section + '] [name="redirect[]"]'),
                currentDateTime = dateNow();

            for (let i = 0; i < agj.length; i++) {
                if (parseInt(agj[i].id) === <?=$_SESSION['user_id']?>) {
                    if (parseInt(result_type) === 0) {
                        //Если добавлен только комментарий
                        agj[i].comment = $comment.val();
                    } else if (parseInt(result_type) === 4) {
                        let vals = $redirect.val();
                        for(let v = 0; v < vals.length; v++) {
                            //Если добавлено только перенаправление
                            if (typeof agj[i].redirect != "undefined") {
                                //Если редирект уже был - добавляем еще один в рекурсии
                                if(typeof agj[i].redirect.find(item => parseInt(item.id) === parseInt(vals[v])) == "undefined") {
                                    agj[i].redirect.push({id: parseInt(vals[v]), type: agj[i].type});
                                }
                            } else {
                                //Если это первый редирект - создаем новый
                                agj[i].result = {id: 4, date: currentDateTime};
                                agj[i].redirect = [{id: parseInt(vals[v]), type: agj[i].type}];
                                if (level === 0) {
                                    fallowIds.push({index: i, id: agj[i].id, type: agj[i].type});
                                }
                            }
                        }
                    } else {
                        agj[i].result = {id: result_type, date: currentDateTime};
                    }
                } else if (typeof agj[i].redirect != 'undefined') {
                    agj.concat(getAgreementList(agj[i].redirect, section, result_type, fallowIds, level + 1));
                }
            }
            if (fallowIds.length > 0/* && level === 0*/) {
                for (let f = 0; f < fallowIds.length; f++) {
                    agj.splice(fallowIds[f].index + 1, 0, {id: fallowIds[f].id, type: fallowIds[f].type});
                }
            }

            return agj;
        }

        function getAgreementData(section, result_type) {
            let $ag = $('#ag' + section),
                agj = JSON.parse($ag.val());

            let agjResult = getAgreementList(agj, section, result_type);

            //console.log(agj);
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
                } else {
                    el_tools.notify('error', 'Ошибка', answer.resultText);
                }
            });
        }

        async function loadUser(section, user_id) {
            try {
                const userData = await el_app.getUserById(user_id, 'short');
                $("#" + section + "_" + user_id).text(userData);
            } catch (error) {
                console.error('Не удалось загрузить:', error);
            }
        }

        function getChosenSortedVal(obj){
            let lis = $(obj).next().find(".search-choice"),
                out = [];
            for(let i = 0; i < lis.length; i++){
                out.push($(obj).find("option:contains('" + $(lis[i]).find('span').text() + "')").val());
            }
            return out;
        }

        $(document).ready(function () {
            /*
            * 1 - подписание
            * 2 - согласование с эп
            * 3 - согласование
            * 4 - перенапропавление*/
            el_app.mainInit();
            //el_registry.create_init();
            bindSign('agreement', <?=$docId?>, <?=$_SESSION['user_id']?>);

            $(".setAgree").off("click").on("click", function (e) {
                e.preventDefault();
                let section = $(this).closest('.actions').data('section'),
                    currentDateTime = dateNow();
                getAgreementData(section, 3);
                $('.actions[data-section=' + section + ']').hide();
                $('#agResult' + section).html("<span style='color: #086a9b'>Согласовано<br>" + currentDateTime + '</span>');
            });



            $('[name=comment]').off('blur').on('blur', function (e) {
                e.preventDefault();
                let section = $(this).closest("td").prev("td").find(".actions").data("section");
                getAgreementData(section, 0);
            });

            $('[name=redirect], [name="redirect[]"]').off('change').on('change', function (e, param) {
                e.preventDefault();
                let $self = $(this),
                    section = $(this).closest('.actions').data('section'),
                    number = $(this).closest("tr").find("td:first").text(),
                    name = $(this).closest('tr').find('td:nth-child(2)').text(),
                    //subNumber = parseInt($(this).nextAll(".redirected").last().find("td:first").text().split('.')) || 0,
                    urgent = $(this).closest('tr').find('td:nth-child(3)').html(),
                    buttons = $('.actions[data-section=' + section + '] button').hide();

                getAgreementData(section, 4);

                $('#agResult' + section).html("<span style='color: #086a9b'>Перенаправлено<br>" + dateNow() + '</span>');

                let padding = parseInt($(this).closest("tr").find("td:nth-child(2)").css("padding-left").replace("px", ""));
                let trHtml = '',
                    lastHtml = '';

                if (typeof param.deselected != 'undefined') {
                    $('#' + section + '_' + param.deselected).closest("tr").remove();
                }

                if(typeof param.selected != "undefined") {
                    trHtml = "<tr class='redirected'>" +
                        "<td></td>" +
                        "<td style='padding-left: " + (padding + 15) + "px' id='" + section + '_' + param.selected + "'></td>" +
                        '<td>' + urgent + '</td>' +
                        '<td></td>' +
                        '<td></td>' +
                        '</tr>';
                    loadUser(section, param.selected);

                    if ($(this).closest('tr').next('tr').hasClass('redirected')) {
                        $(this).closest('tr').nextAll('tr.redirected:last').after(trHtml);
                    } else {
                        $(this).closest('tr').after(trHtml);
                    }
                }

                if (!$self.closest('tr').nextAll('tr.redirected:last').next('tr').hasClass('returned')) {
                    lastHtml = "<tr class='returned'>" +
                        '<td></td>' +
                        '<td style="padding-left: ' + padding + 'px">' + name + '</td>' +
                        '<td>' + urgent + '</td>' +
                        '<td></td>' +
                        '<td></td>' +
                        '</tr>';
                    $self.closest('tr').nextAll('.redirected:last').after(lastHtml);
                }

                if($self.closest('tr').nextAll('tr.redirected').length === 0){
                    $self.closest('tr').next(".returned").remove();
                    buttons.show();
                }else{
                    buttons.hide();
                }

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