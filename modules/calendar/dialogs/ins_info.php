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
$planInspectionNames = [];


if ($auth->isLogin()) {
    if ($insId > 0) {
        $users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name']);
        $taskTemplates = $db->getRegistry('tasks');
        $inspections = $db->getRegistry('inspections');
        $ins = $db->selectOne('institutions', ' WHERE id = ?', [$insId]); //print_r($plan);
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

                // Извлекаем тип проверки для данного учреждения из JSON плана
                $planInstitutions = json_decode($plan->addinstitution, true) ?? [];
                $planInspectionName = '';
                foreach ($planInstitutions as $ch) {
                    if (intval($ch['institutions']) == $insId) {
                        $inspId = $ch['inspections'] ?? '';
                        if (strlen($inspId) > 0) {
                            if (substr_count($inspId, '[') > 0) {
                                foreach (json_decode($inspId) as $iid) {
                                    $planInspectionName .= ($planInspectionName ? '; ' : '') . $inspections['array'][$iid];
                                }
                            } else {
                                $planInspectionName = $inspections['array'][$inspId] ?? '';
                            }
                        }
                        break;
                    }
                }
                $planInspectionNames[$plan->id] = $planInspectionName;
                $orders = $db->select('agreement', ' WHERE status = 1 AND plan_id = ? AND ins_id = ?',
                    [$plan->id, $insId]
                );
                if (count($orders) > 0) {
                    foreach ($orders as $order) {
                        $tasks = $db->select('checkstaff', ' WHERE order_id = ?', [$order->id]);
                        $tasks_info = [];
                        if (count($tasks) > 0) {
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
                } else {
                    // Приказ не оформлен — загружаем проверяющих напрямую из checkstaff по check_uid
                    $tasks = $db->select('checkstaff', ' WHERE check_uid = ? AND institution = ?',
                        [$plan->uid, $insId]
                    );
                    $tasks_info = [];
                    if (count($tasks) > 0) {
                        foreach ($tasks as $task) {
                            $tasks_info[$task->user] = [
                                'is_head' => $task->is_head,
                                'task' => $task->task_id,
                                'dates' => $task->dates,
                                'status' => $task->done
                            ];
                            $executors[$plan->id][$task->user] = trim($users['array'][$task->user][0]) . ' ' .
                                trim($users['array'][$task->user][1]) . ' ' .
                                trim($users['array'][$task->user][2]);
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
    <div class='pop_up drag' style="width: 75rem">
        <div class='title handle'>
            <div class='name'><?= 'Объект проверки &laquo;' . $ins_name_short . '&raquo;' ?></div>
            <div class='button icon close'><span class='material-icons'>close</span></div>
        </div>
        <div class='pop_up_body'>
            <form class='ajaxFrm' id='ins_info' onsubmit="return false">

                <div class="agreement_block tab-panel" id='tab_order-panel'>
                    <div class='group'>

                        <!-- Объект проверки -->
                        <div class="ins-tree">

                            <div class="ins-tree__node ins-tree__node--object">
                                <span class="material-icons">apartment</span>
                                <div class="ins-tree__content">
                                    <div class="ins-tree__label">Объект проверки</div>
                                    <div class="ins-tree__title"><?= $ins_name ?></div>
                                    <div class="ins-tree__sub"><?= $ins_address ?></div>
                                </div>
                            </div>

                            <?php if (count($planNames) > 0): ?>
                                <?php foreach ($planNames as $id => $name): ?>

                                    <!-- Уровень 1: План -->
                                    <div class="ins-tree__branch">
                                        <div class="ins-tree__node ins-tree__node--plan">
                                            <span class="material-icons">assignment</span>
                                            <div class="ins-tree__content">
                                                <div class="ins-tree__label">План проверок</div>
                                                <a href="" class="viewDocument ins-tree__title" data-id="<?= $id ?>"
                                                   data-type="3"><?= $name ?></a>
                                            </div>
                                        </div>

                                        <?php if (isset($ordersNames[$id])): ?>

                                            <!-- Уровень 2: Приказ -->
                                            <div class="ins-tree__branch">
                                                <div class="ins-tree__node ins-tree__node--order">
                                                    <span class="material-icons">gavel</span>
                                                    <div class="ins-tree__content">
                                                        <div class="ins-tree__label">Приказ о проверке</div>
                                                        <a href="" class="viewDocument ins-tree__title"
                                                           data-id="<?= $order_ids[$id] ?>"><?= $ordersNames[$id] ?></a>
                                                        <div class="ins-tree__actions">
                                                            <a href="" class="new_task" data-uid="<?= $plan_uid[$id] ?>"
                                                               data-order="<?= $order_ids[$id] ?>">
                                                                <span class="material-icons"><?= count($executors[$id]) > 0 ? 'edit' : 'control_point' ?></span>
                                                                <?= count($executors[$id]) > 0 ? 'Редактировать задания' : 'Назначить проверяющих' ?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if (isset($executors[$id]) && count($executors[$id]) > 0): ?>
                                                    <!-- Уровень 3: Проверяющие (при наличии приказа) -->
                                                    <?php include __DIR__ . '/_ins_tree_staff.php' ?>
                                                <?php endif; ?>
                                            </div>

                                        <?php elseif (isset($executors[$id]) && count($executors[$id]) > 0): ?>

                                            <!-- Уровень 2: Приказа нет — показываем тип проверки -->
                                            <div class="ins-tree__branch">
                                                <div class="ins-tree__node ins-tree__node--order">
                                                    <span class="material-icons">fact_check</span>
                                                    <div class="ins-tree__content">
                                                        <div class="ins-tree__label">Тип проверки</div>
                                                        <span class="ins-tree__title"><?= $planInspectionNames[$id] ?: 'Не указан' ?></span>
                                                        <div class="ins-tree__actions">
                                                            <a href="" class="new_task" data-uid="<?= $plan_uid[$id] ?>"
                                                               data-order="0">
                                                                <span class="material-icons">edit</span> Редактировать
                                                                задания
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Уровень 3: Проверяющие (без приказа) -->
                                                <?php include __DIR__ . '/_ins_tree_staff.php' ?>
                                            </div>

                                        <?php endif; ?>
                                    </div>

                                <?php endforeach; ?>
                            <?php endif; ?>

                        </div>
                        <!-- /ins-tree -->

                        <?= $reg->renderFileInput([], ['document_id' => $insId], 'edit') ?>
                        <?= $reg->showTaskLog($insId, 'calendar', 'ins_info') ?>

                    </div>
                </div>

                <div style="height: 30px"></div>
                <div class='confirm'>
                    <button class='button icon text' id="save_doc" style="display: none">
                        <span class='material-icons'>save</span>Сохранить
                    </button>
                    <button class='button icon text' id='close'>
                        <span class='material-icons'>close</span>Закрыть
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .ins-tree {
            padding: 10px 0 10px 10px;
        }

        .ins-tree__branch {
            position: relative;
            padding-left: 40px;
            margin-top: 8px;
        }

        /* L-образная линия от родителя к дочернему узлу */
        .ins-tree__branch::before {
            content: '';
            position: absolute;
            left: 20px;
            top: -8px;
            width: 20px;
            height: 30px;
            border-left: 2px solid var(--color_02);
            border-bottom: 2px solid var(--color_02);
            border-radius: 0 0 0 4px;
            pointer-events: none;
        }

        /* Продолжение вертикальной линии для не-последних дочерних элементов */
        .ins-tree__branch:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 20px;
            top: 22px;
            bottom: 0;
            width: 2px;
            background: var(--color_02);
            pointer-events: none;
        }

        .ins-tree__node {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 6px;
            margin-bottom: 4px;
            border: 1px solid var(--color_02);
            background: #fff;
        }

        .ins-tree__node > .material-icons {
            font-size: 22px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .ins-tree__node--object {
            background: var(--color_06);
            border-color: var(--color_03);
        }

        .ins-tree__node--object > .material-icons {
            color: var(--color_03);
        }

        .ins-tree__node--plan > .material-icons {
            color: var(--color_03);
        }

        .ins-tree__node--order > .material-icons {
            color: var(--blue);
        }

        .ins-tree__node--staff > .material-icons {
            color: var(--green);
        }

        .ins-tree__content {
            flex: 1;
            min-width: 0;
            position: relative;
        }

        .ins-tree__label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--color_03);
            margin-bottom: 2px;
        }

        .ins-tree__title {
            font-weight: 500;
            display: block;
        }

        .ins-tree__sub {
            font-size: 90%;
            color: var(--color_03);
        }

        .ins-tree__actions {
            margin-top: 4px;
            font-size: 90%;
        }

        .ins-tree__actions a {
            color: var(--color_03);
        }

        .ins-tree__actions a:hover {
            text-decoration: underline;
        }

        .ins-tree__actions .material-icons {
            font-size: 16px;
            vertical-align: middle;
        }

        .ins-tree__table {
            margin-top: 6px;
        }
    </style>
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
                $.post('/', {
                    ajax: 1, action: 'task_close', task_id: <?=$insId?>,
                    module: 'calendar', form_id: 'ins_info', log_action: 'Закрытие окна объекта проверки'
                });
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