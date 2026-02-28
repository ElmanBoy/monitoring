<?php

use Dompdf\Dompdf;
use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Registry;
use Core\Date;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
//print_r($_POST);
$gui = new Gui;
$db = new Db;
$auth = new Auth();
$reg = new Registry();
$date = new Date();

$plan_id = 0;
$doc_id = 0;
$ins_id = 0;
if (isset($_POST['params']) && is_array($_POST['params'])) {
    $plan_id = intval($_POST['params']['plan_id']);
    $doc_id = intval($_POST['params']['doc_id']);
    $ins_id = intval($_POST['params']['ins_id']);
    $insId = intval($_POST['params']['ins_id']);
    if (substr_count($_POST['params']['ins_id'], '_') > 0) {
        $ins_arr = explode('_', $_POST['params']['ins_id']);
        $ins_id = $insId = $ins_arr[1];
    }
}
$doc_status = 0;

$perms = $auth->getCurrentModulePermission();


$ins_name = '';
$ins_address = '';
$planNames = [];
$orders = [];
$order_ids = [];
$ordersNames = [];
$executors = [];
$executors_head = [];
$plan_uid = [];
$tasks_info = [];
$taskTemplates = [];


if ($auth->isLogin()) {
    if ($insId > 0) {
        $users = $db->getRegistry('users', "", [], ['surname', 'name', 'middle_name']);
        $taskTemplates = $db->getRegistry('tasks');
        $ins = $db->selectOne('institutions', " WHERE id = ?", [$insId]); //print_r($plan);
        $ins_name = $ins->name;
        $ins_name_short = $ins->short;
        $ins_address = $ins->legal;

        $plans = $db->select('checksplans', 'WHERE active = 1 AND
         addinstitution @> \'[{"institutions": "' . $insId . '"}]\'::jsonb;'
        );
        //print_r($plans);
        if (count($plans) > 0) {
            foreach ($plans as $plan) {
                $planNames[$plan->id] = $plan->short . (strlen($plan->doc_number) > 0 ? ' № ' . $plan->doc_number : '');
                $plan_uid[$plan->id] = $plan->uid;
                $orders = $db->select('agreement', ' WHERE status = 1 AND plan_id = ? AND ins_id = ?',
                    [$plan->id, $insId]
                );
                if (count($orders) > 0) {

                    foreach ($orders as $order) {

                        $tasks = $db->select('checkstaff', ' WHERE order_id = ?', [$order->id]);
                        if (count($tasks) > 0) {
                            $tasks_info = [];
                            foreach ($tasks as $task) {
                                $tasks_info[$task->user] = [
                                        'is_head' => $task->is_head,
                                        'task' => $task->task_id,
                                        'dates' => $task->dates,
                                        'status' => $task->done
                                    ];
                            }
                        }

                        $order_ids[$plan->id] = $order->id;
                        $ordersNames[$plan->id] = $order->name .
                            (strlen($order->doc_number) > 0 ? ' № ' . $order->doc_number .
                                ' от ' . $date->dateToString($order->docdate) : '');

                        $executors[$plan->id][$order->executors_head] = trim($users['array'][$order->executors_head][0]) . ' ' .
                            trim($users['array'][$order->executors_head][1]) . ' ' .
                            trim($users['array'][$order->executors_head][2]);

                        foreach (json_decode($order->executors_list) as $ex) {
                            $executors[$plan->id][$ex] = trim($users['array'][$ex][0]) . ' ' .
                                trim($users['array'][$ex][1]) . ' ' .
                                trim($users['array'][$ex][2]);
                        }
                    }
                }
            }
        }
    }
    ?>
    <style>
        /*.datesInputWrapper{
            display: none;
        }*/
    </style>
    <div class='pop_up drag' style="width: 70rem">
        <div class='title handle'>
            <div class='name'><?= 'Объект проверки &laquo;' . $ins_name_short . '&raquo;' ?></div>
            <div class='button icon close'><span class='material-icons'>close</span></div>
        </div>
        <div class='pop_up_body'>
            <form class='ajaxFrm' id='ins_info' onsubmit="return false">

                <? /*ul class='tab-pane' style=''>
                        <li id='tab_order' class='active'>Приказ</li>
                        <li id='tab_agreement'>Согласование</li>
                        <li id='tab_preview' style="display: none">Предпросмотр</li>
                    </ul*/ ?>


                <div class="agreement_block tab-panel" id='tab_order-panel'>
                    <div class='group'>
                        <h2 class='item w_100'>
                            <strong>Объект проверки:</strong>
                        </h2>
                        <div class='item w_100' style="display: block">
                            <strong>Полное наименование:</strong> <?= $ins_name ?>
                            <br>
                            <strong>Юридический адрес:</strong> <?= $ins_address ?>
                        </div>
                        <? if (count($planNames) > 0) { ?>
                            <h2 class='item w_100'>
                                <strong>Включён в планы:</strong>
                            </h2>
                            <div class='item w_100' style='display: block'>
                                <?
                                foreach ($planNames as $id => $name) {
                                    echo '<a href="" class="viewDocument" data-id="' . $id . '" data-type="3">' . $name . '</a>';
                                    if (isset($ordersNames[$id])) {
                                        echo '<ul class="docs">';
                                        echo '<li><a href="" class="viewDocument" data-id="' . $order_ids[$id] . '">' . $ordersNames[$id] . '</a>' .
                                            ' <small><a href="" class="new_task" data-uid="' . $plan_uid[$id] .
                                            '" data-order="' . $order_ids[$id] . '">' .
                                            '<span class="material-icons">control_point</span> Создать задание</a></small>';
                                        echo '<br><strong>Проверяющие:</strong>' .
                                            '<table class="num_list"><tr><th>№</th><th>ФИО</th>' .
                                            '<th>Задача</th><th>Период проверки</th><th>Статус</th></tr>';
                                        $i = 0;
                                        foreach ($executors[$id] as $uid => $executor) {
                                            $status = '<span class="greyText">Ожидает назначения</span>';
                                            if(strlen($tasks_info[$uid]['dates']) > 0){
                                                $status = '<span class="blueText">Назначена</span>';
                                                if($tasks_info[$uid]['status'] == 1){
                                                    $status = '<span class="greenText">Выполнена</span>';
                                                }
                                            }
                                            echo '<tr><td>' . ($i + 1) . '. </td>' .
                                                '<td>' . $executor .
                                                ($tasks_info[$uid]['is_head'] ? ' <span class="greenText">руководитель</span>' : '') .
                                                '</td>' .
                                                '<td>' . $taskTemplates['array'][$tasks_info[$uid]['task']] . '</td>' .
                                                '<td>' . $date->periodToString($tasks_info[$uid]['dates']) . '</td>' .
                                                '<td>' . $status . '</td>' .
                                                '</tr>';
                                            $i++;
                                        }
                                        echo '</table></li>';
                                    }
                                    echo '</ul>';
                                }
                                ?>
                            </div>
                        <? } ?>
                        <?= $reg->renderFileInput([], ['document_id' => $insId], 'edit') ?>
                        <?
                        echo $reg->showTaskLog($insId, 'calendar', 'ins_info');
                        ?>
                    </div>
                </div>


                <? /*div class='preview_block tab-panel' id='tab_preview-panel' style='display: none'>
                        <iframe id='pdf-viewer' width='100%' height='600px'></iframe>
                    </div*/ ?>
                <div style="height: 30px"></div>
                <div class='confirm'>
                    <button class='button icon text' id="save_doc" style="display: none"><span class='material-icons'>save</span>Сохранить
                    </button>

                    <button class='button icon text' id='close'><span class='material-icons'>close</span>Закрыть
                    </button>

                </div>
            </form>
        </div>
    </div>
    <? /*script src="/modules/calendar/js/registry.js"></script*/ ?>
    <script src='/js/assets/agreement_list.js'></script>
    <script>
        function bindOrderCalendar() {
            let minDate = $('[name=minDate]').val(),
                maxDate = $('[name=maxDate]').val(),
                checkMinDate = $('[name=checkMinDate]').val(),
                checkMaxDate = $('[name=checkMaxDate]').val(),
                $range_date = $('[name=check_period]'),
                $range_action = $('[name=action_period]');
//console.log(minDate, maxDate, checkMinDate, checkMaxDate);
            let cal = $range_date.flatpickr({
                locale: 'ru',
                mode: 'range',
                time_24hr: true,
                dateFormat: 'Y-m-d',
                altFormat: 'd.m.Y',
                altInput: true,
                allowInput: true,
                defaultDate: '',
                minDate: checkMinDate,
                maxDate: checkMaxDate,
                altInputClass: 'el_input',
                firstDayOfWeek: 1,
            });
            let cal2 = $range_action.flatpickr({
                locale: 'ru',
                mode: 'range',
                time_24hr: true,
                dateFormat: 'Y-m-d',
                altFormat: 'd.m.Y',
                altInput: true,
                allowInput: true,
                defaultDate: '',
                minDate: minDate,
                maxDate: maxDate,
                altInputClass: 'el_input',
                firstDayOfWeek: 1,
            });

            $('[name=check_period] ~ input').mask('99.99.9999 - 99.99.9999');
            $('[name=action_period] ~ input').mask('99.99.9999 - 99.99.9999');

            if (typeof el_app.calendars.popup_calendar != 'undefined' && 'push' in el_app.calendars.popup_calendar) {
                el_app.calendars.popup_calendar.push(cal);
                el_app.calendars.popup_calendar.push(cal2);
            }
        }

        $(document).ready(function () {
            el_app.initTabs();


            $("[name=executors_head]").on("change", function () {
                let user_head = $(this).val(),
                    $executor_list = $("[name='executors_list[]']"),
                    currentValues = $executor_list.val();
                $("[name='executors_list[]'] option")
                    .prop("disabled", false).attr('title', '');
                $("[name='executors_list[]'] option[value='" + String(user_head) + "']")
                    .prop('disabled', true).attr("title", "Назначен руководителем проверки");
                if (currentValues) {
                    let newValues = $.grep(currentValues, function (value) {
                        return value != user_head;
                    });

                    $executor_list.val(newValues).trigger('chosen:updated');
                }
            });

            $('#ins_info .close, #ins_info #close').off('click').on('click', function (e) {
                e.preventDefault();
                el_app.dialog_close('ins_info');
                $.post('/', {ajax: 1, action: 'task_close', task_id: <?=$insId?>,
                    module: 'calendar', form_id: 'ins_info', log_action: 'Закрытие окна объекта проверки'});
            });

            $("input[name='dates[]'] ~ input").mask('99.99.9999 - 99.99.9999');
            $("input[name='dates[]']").trigger('input');

            let $document = $('[name=document]'),
                $tab_preview = $('#tab_preview');
            $document.on('change', function () {
                if ($(this).val() > 0) {
                    //$tab_preview.show();
                } else {
                    $tab_preview.hide();
                }
            });
            if ($document.val() > 0) {
                $tab_preview.show();
            }
            $tab_preview.on('click', function () {
                let formData = $('form#order_staff').serialize();
                $('.preloader').fadeIn('fast');
                $.post('/', {ajax: 1, action: 'planPdf', data: formData}, function (data) {
                    if (data.length > 0) {
                        $('#pdf-viewer').attr('src', 'data:application/pdf;base64,' + data);
                        $('.preloader').fadeOut('fast');
                    }
                })
            });

            $('.new_task').off('click').on('click', function (e) {
                e.preventDefault();
                let orderId = $(this).data('order'),
                    uid = $(this).data('uid');
                el_app.dialog_open('assign_staff', {insId: uid + '_' + <?=$insId?>, orderId: orderId});
            });

            $('.viewDocument').off('click').on('click', function (e) {
                e.preventDefault();
                let taskId = $(this).data('id'),
                    docType = $(this).data("type");
                el_app.dialog_open('planPdf', {docId: taskId, docType: docType}, 'documents');
            });

            $(document).on("files_has_added", function () {
                $("#close").hide();
                $("#save_doc").show();
            });
            $(document).on('files_has_removed', function () {
                $('#close').show();
                $('#save_doc').hide();
            });

        });
    </script>
    <?php
    $reg->insertTaskLog($insId, 'Объект проверки открыт для просмотра', 'calendar', 'ins_info');

} else {
    echo '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>';
}

?>
