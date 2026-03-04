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

$perms = $auth->getCurrentModulePermission();
$in_calendar = isset($_POST['params']['in_calendar']) && intval($_POST['params']['in_calendar']) == 1;

$taskStr = 0;
$insStr = 0;
if (isset($_POST['params']['taskId'])) {
    $taskStr = $_POST['params']['taskId'];
}
if (isset($_POST['params']['insId'])) {
    $insStr = $_POST['params']['insId'];
}
$orderId = 0;
if (isset($_POST['params']['orderId'])) {
    $orderId = intval($_POST['params']['orderId']);
}
$task = 0;
$new_order_number = '';
$new_order_num = 1000;
$dates = '';
$exist_task = [];
$agreement_data = [];
$plan_uid = '0';
$actionPeriods = [];

if ($auth->isLogin()) {
    if (strlen($insStr) > 1) {
        //Номер задачи пуст - Новая задача
        $taskArr = explode('_', $insStr);
        $plan_uid = $taskArr[0];
        $insId = intval($taskArr[1]);
        $taskId = 0;

        $plan = $db->selectOne('checksplans', ' WHERE uid = ?', [$plan_uid]);
        $plan_id = $plan->id;
        $exist_task = $db->select('checkstaff', " WHERE check_uid = '$plan_uid' AND institution = " . $insId);
        //$plan_uid = $exist_task[$taskId]->check_uid;

    } else {
        //Есть номер задачи - Редактирование существующей задачи
        $taskArr = explode('_', $taskStr);
        $plan_uid = $taskArr[0];
        $taskId = intval($taskArr[1]);
        $current_task = $db->selectOne('checkstaff', " WHERE id = ?", [$taskId]);
        $insId = intval($current_task->institution);
        $unitId = intval($current_task->unit);
        $plan_uid = $current_task->check_uid;
        $exist_task = $db->select('checkstaff', " WHERE check_uid = '$plan_uid' AND institution = " . $insId);
    }
    //echo $plan_uid;
    // Получаем приказ по учреждению (всегда, не только при наличии назначения)
    $agreement_data = $db->selectOne('agreement', " WHERE 
     source_table = 'checkinstitutions' AND source_id = " . $insId
    );
    $order_approved = (intval($agreement_data->status) == 1 || intval($agreement_data->approved) == 1);

    // Если orderId передан напрямую (из ins_info) — берём даты из приказа
    if ($orderId > 0 && empty($minDate)) {
        $order_data = $db->selectOne('agreement', ' WHERE id = ?', [$orderId]);
        if ($order_data) {
            $actionPeriodDecoded = json_decode($order_data->action_period);
            $orderDates = $date->getDatesFromMonths($actionPeriodDecoded);
            $minDate = $orderDates['start'];
            $maxDate = $orderDates['end'];
        }
    }

    if (count($exist_task) > 0) {
        //Если такой приказ уже есть
    } /*else {
        //Если новый приказ, то генерим новый номер приказа
        $doc = $db->selectOne('agreement', " WHERE source_table = 'checkinstitutions' 
        AND doc_number LIKE 'ПРП%' ORDER BY id DESC"
        );
        if (strlen($doc->doc_number) > 0) {
            $plan_number = $doc->doc_number;
            $plan_numberArr = explode('-', $plan_number);
            if ($plan_numberArr[1] == date('Y')) {
                $new_order_num = intval(str_replace('ПРП', '', $plan_numberArr[0])) + 1;
                $new_plan_number = 'ПРП' . $new_order_num . '-' . date('Y');
            }
        }
    }*/


    //Открываем транзакцию
    $busy = $db->transactionOpen('roles', 1);
    $trans_id = $busy['trans_id'];

    if ($busy != []) {

        $units = $db->getRegistry('units', 'where institution = 1 and active =1');
        $ins = $db->getRegistry('institutions');
        $insector = $db->getRegistry('institutions', 'WHERE inspectors = 1');
        $tasks = $db->getRegistry('tasks');
        $ousr = $db->getRegistry('ousr');
        $orders = $db->getRegistry('documents', ' WHERE documentacial = 1');

        //Если это уже назначенная задача
        if ($taskId > 0) {
            $chStaff = $db->selectOne('checkstaff', ' WHERE id = ?', [$taskId]);

            $dates = $chStaff->dates;
            $datesEventArr = explode(' - ', $dates);
            $insId = $chStaff->institution;
            $task = $chStaff->task_id;

            if ($chStaff->object_type == 0) {
                $ins = $db->getRegistry('persons', '', [], ['surname', 'first_name', 'middle_name', 'birth']);
                $object = stripslashes(htmlspecialchars($ins['array'][$insId][0])) . ' ' .
                    stripslashes(htmlspecialchars($ins['array'][$insId][1])) . ' ' .
                    stripslashes(htmlspecialchars($ins['array'][$insId][2])) . ' ' .
                    (strlen(trim($ins['array'][$insId][3])) > 0 ?
                        $date->correctDateFormatFromMysql($ins['array'][$insId][3]) : '');
            } else {
                $object = stripslashes(htmlspecialchars($ins['result'][$insId]->short));
            }
        } else {
            $object = stripslashes(htmlspecialchars($ins['array'][$insId]));
        }//Иначе это новая задача по клику по учреждению

        if (strlen($plan_uid) == 0) {
            $plan_uid = '0';
        }

        if ($plan_uid != '0') {
            $plan = $db->selectOne('checksplans', " WHERE uid = '$plan_uid' ORDER BY version DESC LIMIT 1");
            if (strlen($plan->addinstitution) > 0) {
                $actionPeriods = $date->getReviewPeriodsFromJson($plan->addinstitution, $plan->year);
                $insArr = json_decode($plan->addinstitution, true);
                $plan_name = $plan->short;
                for ($i = 0; $i < count($insArr); $i++) {
                    if (intval($insArr[$i]['institutions']) == $insId) {
                        $actionPeriod = $actionPeriods[$insId]['actionPeriod'];
                        $check_dates = json_decode($insArr[$i]['periods_hidden']);
                        $datesArr = $date->getDatesFromMonths($check_dates, $plan->year);
                        $minDate = $datesArr['start'];
                        $maxDate = $datesArr['end'];
                    }
                }
            }
        }

        $users = $db->getRegistry('users', "where roles <> '2'", [], ['surname', 'name', 'middle_name']);
        $new_order_number = 'ПРП' . $new_order_num . '-' . date('Y');
        $prevDate = date('Y-m-d', strtotime($datesEventArr[0] . ' -1 day'));

        ?>
        <style>
            /*.datesInputWrapper{
                display: none;
            }*/
            .orderInfo {
                width: 50%;
            }
            .greenText{
                margin-left: 10px;
            }
        </style>
        <div class='pop_up drag' style="max-width: 70rem;">
            <div class='title handle'>
                <div class='name'><?= ($taskId > 0 || count($exist_task) > 0 ? 'Редактирование назначения на проверку' : 'Назначение на проверку') ?></div>
                <div class='button icon close'><span class='material-icons'>close</span></div>
            </div>
            <div class='pop_up_body'>
                <form class='ajaxFrm' id='check_staff' onsubmit="return false">
                    <input type='hidden' name='uid' value="<?= $plan_uid ?>">
                    <input type='hidden' name='minDate' value="<?= $minDate ?>">
                    <input type='hidden' name='maxDate' value="<?= $maxDate ?>">
                    <input type='hidden' name='path' value="calendar">
                    <input type='hidden' name='ins' value='<?= $insId ?>'>
                    <input type='hidden' name='unit' value='<?= $unitId ?>'>
                    <input type="hidden" name="actionPeriod" value="<?= $actionPeriod ?>">

                    <div class='group plan_block tab-panel' id='tab_executors-panel'>
                        <?
                        if (!$order_approved) {
                            echo '<div class="item w_100">
                                <div class="el_data" style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 12px; color: #856404;">
                                    <span class="material-icons" style="vertical-align: middle; margin-right: 6px;">warning</span>
                                    <strong>Назначение проверяющих недоступно.</strong> Приказ о проведении проверки ещё не утверждён.
                                </div>
                            </div>';
                        }
                        if ($plan_uid == '0') { //Если задача создается не из плана
                            ?>
                            <div class='item w_50'>
                                <select data-label='План' name='plan'>
                                    <option value="0">Внеплановая проверка</option>
                                    <?
                                    $plans = $db->getRegistry('checksplans', " WHERE active = 1");
                                    echo $gui->buildSelectFromRegistry($plans['result'], [], false,
                                        ['short'], ' '
                                    );
                                    ?>
                                </select>
                            </div>
                            <div class='item w_50' style="display: none">
                                <select data-label='Приказ о проведении проверки' name='order'>
                                </select>
                            </div>
                            <?
                        } else {
                            ?>
                            <input type='hidden' name='plan' value="<?= $plan_id ?>">
                            <input type='hidden' name='order' value="<?= $orderId ?>">
                            <input type='hidden' name='uid' value="<?= $plan_uid ?>">
                            <input type='hidden' name='task_id' value="<?= $taskId ?>">
                            <input type='hidden' name='ins' value="<?= $insId ?>">
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <label>План:</label>
                                    <strong><?= $plan_name ?></strong>
                                </div>
                            </div>
                            <div class="item w_50">
                                <div class="el_data">
                                    <label>Объект проверки:</label>
                                    <strong><?= $object ?></strong>
                                </div>
                            </div>
                            <?
                        }
                        ?>
                        <div class="group" id="orderInfoWrapper">
                            <div id="ins" class="orderInfo"></div>
                            <div id='unit' class='orderInfo'></div>
                            <div id='checkPeriod' class='orderInfo'></div>
                            <div id='actionPeriodText' class='orderInfo'></div>
                        </div>


                        <div id="staff_list">
                            <h3 class='item w_100'>
                                <strong>ПРОВЕРЯЮЩИЕ</strong>
                            </h3>
                            <?php
                            if (count($exist_task) > 0) {
                                $staff_number = 1;
                                foreach ($exist_task as $chStaff) {
                                    $staffDates = $chStaff->dates;
                                    $staffTask  = $chStaff->task_id;
                                    $staffFio   = trim($users['array'][$chStaff->user][0]) . ' '
                                                . trim($users['array'][$chStaff->user][1]) . ' '
                                                . trim($users['array'][$chStaff->user][2]);
                                    $reminder   = $db->selectOne('reminders', ' WHERE task_id = ? AND employee = ?', [$chStaff->id, $chStaff->user]);
                                    ?>
                                    <div class='group staff'>
                                        <h5 class='item w_100 question_number'>Сотрудник №<?= $staff_number ?></h5>
                                        <input type="hidden" name="user_task[]" value="<?= $chStaff->id ?>">
                                        <input type="hidden" name="executors[]" value="<?= $chStaff->user ?>">
                                        <input type="hidden" name="ousr[]" value="<?= $chStaff->ousr ?>">
                                        <div class='item w_100'>
                                            <div class='el_data'>
                                                <label>Сотрудник:</label>
                                                <strong><?= $staffFio ?></strong>
                                                <?php if (intval($chStaff->is_head) == 1): ?>
                                                    <span class='greenText'> руководитель проверки</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <input type="hidden" name="is_head[]" value="<?= intval($chStaff->is_head) ?>">
                                        <div class='item w_50'>
                                            <div class='el_data datesInputWrapper'>
                                                <label>Период проверки</label>
                                                <input class='el_input range_date' type='text' name='dates[]'
                                                       value="<?= $staffDates ?>">
                                            </div>
                                        </div>
                                        <div class='item w_50'>
                                            <select data-label='Шаблон задачи' name='tasks[]'>
                                                <?= $gui->buildSelectFromRegistry($tasks['result'], [$staffTask], true) ?>
                                            </select>
                                        </div>
                                        <div class='item w_50'>
                                            <div class='el_data'>
                                                <div class='custom_checkbox'>
                                                    <label class='container' style='left: 4px;'>
                                                        <span class='label-text'>Включить напоминание</span>
                                                        <input type='checkbox' name='allowremind[]'
                                                               class='is_claim' tabindex='-1'
                                                               value='1'<?= $chStaff->allowremind == 1 ? ' checked="checked"' : '' ?>>
                                                        <span class='checkmark'></span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($reminder): ?>
                                            <input type="hidden" name="remind_id[]" value="<?= $reminder->id ?>">
                                        <?php else: ?>
                                            <input type="hidden" name="remind_id[]" value="0">
                                        <?php endif; ?>
                                        <div class='group reminder' style='margin-top: -10px;<?= $chStaff->allowremind != 1 ? " display:none;" : "" ?>'>
                                            <h5 class='item w_100 remind_number'>Напоминание</h5>
                                            <?= $reg->buildForm(71, [], [
                                                'datetime' => strlen($reminder->datetime) > 0 ? $reminder->datetime : $prevDate . ' 10:00',
                                                'employee' => intval($reminder->employee) > 0 ? $reminder->employee : $chStaff->user,
                                                'comment'  => $reminder->comment
                                            ]) ?>
                                        </div>
                                    </div>
                                    <?php
                                    $staff_number++;
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <?
                    echo '<div class="group">'.$reg->showTaskLog($orderId, 'calendar', 'assign_staff').'</div>';
                    ?>

                    <div style="height: 100px"></div>
                    <div class='confirm'>
                        <!--<button class='button icon text' id="save_doc"><span class='material-icons'>save</span>Сохранить
                        </button>-->
                        <?
                        if ($in_calendar && intval($perms['delete']) == 1) {
                            ?>
                            <button class='button icon text red' id='remove_event'><span
                                        class='material-icons'>delete</span>Удалить
                            </button>
                            <?
                        }
                        if (intval($perms['edit']) == 1) {
                            ?>
                            <button class='button icon text' id="save_doc" <?= !$order_approved ? 'disabled title="Приказ не утверждён"' : '' ?>>
                                <span class='material-icons'>save</span>Сохранить
                            </button>
                            <?
                        } else {
                            ?>
                            <button class='button icon text' id='close'><span class='material-icons'>close</span>Закрыть
                            </button>
                            <?
                        }
                        ?>
                    </div>
                </form>
            </div>
        </div>
        <script src="/modules/calendar/js/registry.js"></script>
        <script src='/js/assets/agreement_list.js'></script>
        <script>
            $(document).ready(function () {
                el_app.initTabs();
                //el_registry.bindCalendar("<?=$minDate?>", "<?=$maxDate?>");
                // Инициализируем flatpickr на каждом поле дат (в т.ч. предзаполненных при редактировании)
                $("#check_staff [name='dates[]']").each(function () {
                    let inputEl = this;
                    let rawVal = inputEl.value;
                    let defaultDates = rawVal.length > 0 ? rawVal.split(' - ') : [];
                    flatpickr(inputEl, {
                        locale: 'ru',
                        mode: 'range',
                        time_24hr: true,
                        dateFormat: 'Y-m-d',
                        altFormat: 'd.m.Y',
                        conjunction: ' - ',
                        altInput: true,
                        allowInput: true,
                        defaultDate: defaultDates,
                        minDate: "<?=$minDate?>",
                        maxDate: "<?=$maxDate?>",
                        altInputClass: 'el_input',
                        firstDayOfWeek: 1
                    });
                });
                let $staffs = $('.staff');

                $("select[name='institutions[]']").trigger('change');

                //agreement_list.agreement_list_init();

                $("[name='plan']").on("change", function () {
                    let $self = $(this),
                        $orderInfoWrapper = $('#orderInfoWrapper'),
                        $staff_list = $('#staff_list');

                    $orderInfoWrapper.hide();
                    $staff_list.hide();
                    $.post("/", {
                        ajax: 1, action: "getOrdersByPlan", planId: $(this).val(),
                        selected: (<?=intval($insId)?>)
                    }, function (data) {
                        let $order = $('select[name=order]'),
                            $uid = $("[name='uid']");
                        //$('.datesInputWrapper').hide();
                        if (data.length > 0) {
                            let answer = JSON.parse(data);
                            $order.html(answer.order).trigger("chosen:updated").closest(".item").show();
                            setTimeout(function () {
                                $order.trigger("change")
                            }, 500, $order);
                            $uid.val(answer.uid);

                        } else {
                            $order.html("").trigger('chosen:updated').closest('.item').hide();
                            //$uid.val("0");
                        }
                    });

                // Автозапуск только для новых назначений (без существующих записей в checkstaff)
                <?php if (count($exist_task) == 0): ?>
                }).trigger("change");
                <?php else: ?>
                });
                <?php endif; ?>

                $("[name=order]").on("change", function () {
                    let $self = $(this),
                        $minDate = $("[name='minDate']"),
                        $maxDate = $("[name='maxDate']"),
                        $actionPeriod = $("[name='actionPeriod']"),
                        $actionPeriodText = $("#actionPeriodText"),
                        $checkPeriodText = $("#checkPeriod"),
                        $insText = $("#ins"),
                        $ins = $("[name=ins]"),
                        $unit = $("[name=unit]"),
                        $unitText = $("#unit"),
                        $orderInfoWrapper = $("#orderInfoWrapper"),
                        $staff_list = $("#staff_list");

                    $orderInfoWrapper.hide();
                    $staff_list.hide();
                    $.post('/', {ajax: 1, action: 'getDataByOrder', orderId: $self.val()},
                        function (data) {
                            if (data.length > 0) {
                                let answer = JSON.parse(data);

                                $(".datesInputWrapper").show();
                                /*$cal.set('minDate', answer.minDate);
                                $cal.set('maxDate', answer.maxDate);*/
                                // Находим ВСЕ элементы с flatpickr и обновляем каждый
                                $("#check_staff [name='dates[]']").each(function () {
                                    // Получаем объект flatpickr из элемента
                                    let fpInstance = this._flatpickr;

                                    if (fpInstance && typeof fpInstance.set === 'function') {
                                        fpInstance.set('minDate', answer.minDate);
                                        fpInstance.set('maxDate', answer.maxDate);
                                    }
                                });
                                $minDate.val(answer.minDate);
                                $maxDate.val(answer.maxDate);
                                $ins.val(answer.insId);
                                $unit.val(answer.unitId);
                                $actionPeriod.val(JSON.stringify(answer.actionPeriod));
                                $actionPeriodText.html('<strong>Период проверки:</strong> ' + answer.actionPeriodText);
                                $checkPeriodText.html('<strong>Проверяемый период:</strong> ' + answer.checkPeriod);
                                $insText.html('<strong>Объект проверки:</strong> ' + answer.institution);
                                $unitText.html('<strong>Адрес:</strong> ' + answer.unit);
                                $staff_list.html("<h3 class='item w_100'><strong>ПРОВЕРЯЮЩИЕ</strong></h3>" + answer.staffList);
                                $orderInfoWrapper.show();
                                $staff_list.show();
                                el_app.mainInit();
                                // Инициализируем flatpickr на новых полях дат
                                $("#check_staff [name='dates[]']").each(function () {
                                    if (!this._flatpickr) {
                                        flatpickr(this, {
                                            locale: 'ru',
                                            mode: 'range',
                                            time_24hr: true,
                                            dateFormat: 'Y-m-d',
                                            altFormat: 'd.m.Y',
                                            conjunction: ' - ',
                                            altInput: true,
                                            allowInput: true,
                                            minDate: answer.minDate,
                                            maxDate: answer.maxDate,
                                            altInputClass: 'el_input',
                                            firstDayOfWeek: 1
                                        });
                                    } else {
                                        this._flatpickr.set('minDate', answer.minDate);
                                        this._flatpickr.set('maxDate', answer.maxDate);
                                    }
                                });
                                $("[name='allowremind[]']").off('change').on('change', function () {
                                    let $reminder = $(this).closest('.group').find('.reminder');
                                    if ($(this).prop('checked')) {
                                        $reminder.show();
                                        $reminder.find('input, select, textarea').attr('disabled', false);
                                    } else {
                                        $reminder.hide();
                                        $reminder.find('input, select, textarea').attr('disabled', true);
                                    }
                                });
                            }
                        });

                    /*if($("[name='plan']").val() === '0' && $self.val() !== '0'){
                        $('.datesInputWrapper').show();
                        $cal.set('minDate', '');
                        $cal.set('maxDate', '');
                    }*/
                <?php if (count($exist_task) == 0): ?>
                }).trigger('change');
                <?php else: ?>
                });
                <?php endif; ?>

                $("[name='allowremind[]']").off('change').on('change', function () {
                    let $reminder = $(this).closest('.group').find('.reminder');
                    if ($(this).prop('checked')) {
                        $reminder.show();
                        $reminder.find('input, select, textarea').attr('disabled', false);
                    } else {
                        $reminder.hide();
                        $reminder.find('input, select, textarea').attr('disabled', true);
                    }
                });

                $('#remove_event').off('click').on('click', async function (e) {
                    e.preventDefault();
                    let calEvent = calendarGrid.getEventById('<?=$taskStr?>');
                    let ok = await confirm('Вы уверены, что хотите удалить это задание?');
                    if (ok) {
                        calEvent.remove();
                        $.post('/', {ajax: 1, action: 'event_delete', id: '<?=$taskId?>'}, function (data) {
                            let answer = JSON.parse(data);
                            if (answer.result) {
                                inform('Отлично!', answer.resultText);
                            } else {
                                el_tools.notify('error', 'Ошибка', answer.resultText);
                            }
                        });
                        el_app.dialog_close('view_staff');
                    }
                });

                $('#save_doc').on('mousedown keypress', function () {
                    let calEvent = calendarGrid.getEventById('<?=$taskStr?>'),
                        datesArr = $("[name='dates[]']").val().split(' - ');
                    calEvent.setProp('title', $("[name='executors[]']").find('option:selected').text());
                    calEvent.setDates(datesArr[0], datesArr[1]);
                });

                $('#assign_staff .close').off('click').on('click', function (e) {
                    e.preventDefault();
                    el_app.dialog_close('assign_staff');
                    $.post('/', {ajax: 1, action: 'task_close', task_id: <?=$orderId?>,
                        module: 'calendar', form_id: 'assign_staff', log_action: 'Закрытие окна назначения'});
                });

                /*$("#check_staff input[name='dates[]'], #check_staff select[name='units[]']," +
                    "#check_staff select[name='ministries[]']")
                    .on('change input', function () {

                    let dates = $("input[name='dates[]']").val(),
                        units = $("select[name='units[]']").val(),
                        task_id = $("#check_staff input[name='task_id']").val(),
                        user_selected = $("input[name='executors_hidden[]']").val(),
                        $users = $("select[name='executors[]']");
                    if (dates.length > 0 && units !== null) {
                        //Если это уже назначенная задача
                        if (parseInt(task_id) > 0) {
                            dates = '';
                        }
                        $.post('/', {
                                ajax: 1,
                                path: 'calendar',
                                action: 'available_staff',
                                dates: dates,
                                units: units,
                                user_selected: user_selected
                            },
                            function (data) {
                                $users.html(data).trigger('chosen:updated');
                            });
                    }
                });*/
                $("input[name='dates[]'] ~ input").mask('99.99.9999 - 99.99.9999');
                $("input[name='dates[]']").trigger('input');

                $('[name=initiator]').val("<?=$_SESSION['user_id']?>").trigger('chosen:updated');

                $('select[name=agreementtemplate]').off('change').on('change', function () {
                    $.post('/', {ajax: 1, action: 'getDocTemplate', temp_id: $(this).val()}, function (data) {
                        let answer = JSON.parse(data),
                            agreementlist = JSON.parse(answer.agreementlist);
                        $('[name=brief]').val(answer.brief);
                        $('[name=initiator]').val(answer.initiator).trigger('chosen:updated');
                        $.post('/', {
                            ajax: 1,
                            action: 'buildAgreement',
                            agreementlist: answer.agreementlist
                        }, function (data) {
                            $('.agreement_list_group').html(data);
                            el_app.mainInit();
                            agreement_list.agreement_list_init();
                        });
                    });
                });

                $('#save_doc').on('mousedown keypress', function () {
                    $(".agreement_block select[name='institutions[]']").attr('disabled', true);
                });

                agreement_list.agreement_list_init();
                $('.staff').show();
            });
        </script>
        <?php
        $reg->insertTaskLog($orderId, 'Назначение открыто для редактирования', 'calendar', 'assign_staff');
    } else {
        ?>
        <script>
            alert("Эта запись редактируется пользователем <?=$busy->user_name?>");
            el_app.dialog_close("assign_staff");
        </script>
        <?
    }

} else {
    echo '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>';
}

?>
