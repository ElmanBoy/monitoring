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

error_log(date('Y-m-d H:i:s') . ' [assign_staff] params=' . json_encode($_POST['params'] ?? []) . ' insStr=' . json_encode($insStr) . ' taskStr=' . json_encode($taskStr ?? '') . "\n", 3, $_SERVER['DOCUMENT_ROOT'] . '/logs/PHP_errors.log');

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
        $current_task = $db->selectOne('checkstaff', ' WHERE id = ?', [$taskId]);
        $insId = intval($current_task->institution);
        $unitId = intval($current_task->unit);
        $plan_uid = $current_task->check_uid;
        $exist_task = $db->select('checkstaff', " WHERE check_uid = '$plan_uid' AND institution = " . $insId);
    }
    //echo $plan_uid;
    // Получаем приказ по учреждению (всегда, не только при наличии назначения)
    // ИСПРАВЛЕНО: ищем по ins_id вместо source_id, т.к. source_id указывает на cam_checkinstitutions.id
    $agreement_data = $db->selectOne('agreement', " WHERE documentacial = 1 AND plan_id = (SELECT id FROM cam_checksplans WHERE uid = '$plan_uid' LIMIT 1) AND ins_id = " . $insId);
    // Для внеплановых задач (plan_uid='0') приказ не требуется
    $is_unplanned = ($plan_uid === '0');
    $order_approved = $is_unplanned || (intval($agreement_data->status ?? 0) == 1);

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

        $users = $db->getRegistry('users', "where roles <> '2' ORDER BY surname, name, middle_name", [], ['surname', 'name', 'middle_name']);
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

            .greenText {
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
                            echo '<div class="item w_100 order-warning">
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
                                    $plans = $db->getRegistry('checksplans', ' WHERE active = 1');
                                    echo $gui->buildSelectFromRegistry($plans['result'], [], false,
                                        ['short'], ' '
                                    );
                                    ?>
                                </select>
                            </div>
                            <div class='item w_50 unplanned-ins-wrapper'>
                                <select data-label='Объект проверки' name='ins_select'>
                                    <option value="0">Выберите учреждение</option>
                                    <?= $gui->buildSelectFromRegistry($ins['result'], [], false, ['short'], ' ') ?>
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
                                    $staffTask = $chStaff->task_id;
                                    $staffFio = trim($users['array'][$chStaff->user][0]) . ' '
                                        . trim($users['array'][$chStaff->user][1]) . ' '
                                        . trim($users['array'][$chStaff->user][2]);
                                    $reminder = $db->selectOne('reminders', ' WHERE task_id = ? AND employee = ?', [$chStaff->id, $chStaff->user]);
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
                                                        <input type='hidden' name='allowremind_actual[]'
                                                               class='allowremind_actual'
                                                               value='<?= $chStaff->allowremind == 1 ? '1' : '0' ?>'>
                                                        <input type='checkbox' name='allowremind_flag[]'
                                                               class='is_claim allowremind_cb' tabindex='-1'
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
                                        <div class='group reminder'
                                             style='margin-top: -10px;<?= $chStaff->allowremind != 1 ? ' display:none;' : '' ?>'>
                                            <h5 class='item w_100 remind_number'>Напоминание</h5>
                                            <?php
                                            $rDatetime = strlen($reminder->datetime) > 0 ? str_replace(' ', 'T', $reminder->datetime) : $prevDate . 'T10:00';
                                            $rComment = $reminder->comment ?? '';
                                            ?>
                                            <input type="hidden" name="remind_employee[]" value="<?= $chStaff->user ?>">
                                            <div class="item w_50">
                                                <div class="el_data">
                                                    <label>Дата и время напоминания</label>
                                                    <input class="el_input single_date_time" type="datetime-local"
                                                           name="datetime[]"
                                                           value="<?= htmlspecialchars($rDatetime) ?>">
                                                </div>
                                            </div>
                                            <div class="item w_100">
                                                <div class="el_data">
                                                    <label>Комментарий</label>
                                                    <textarea class="el_textarea" name="comment[]"
                                                              rows="2"><?= htmlspecialchars($rComment) ?></textarea>
                                                </div>
                                            </div>
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
                    echo '<div class="group">' . $reg->showTaskLog($orderId, 'calendar', 'assign_staff') . '</div>';
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
                            <button class='button icon text'
                                    id="save_doc" <?= !$order_approved ? 'disabled title="Приказ не утверждён"' : '' ?>>
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
                //el_calendar_registry.bindCalendar("<?=$minDate?>", "<?=$maxDate?>");
                // Инициализируем flatpickr на каждом поле дат (в т.ч. предзаполненных при редактировании)
                /*$("#check_staff [name='dates[]']").each(function () {
                    let inputEl = this;
                    let rawVal = inputEl.value;
                    console.log('dates value:', rawVal);
                    console.log('minDate:', "<?=$minDate?>", 'maxDate:', "<?=$maxDate?>");
                    let defaultDates = rawVal.length > 0 ? rawVal.split(' - ') : [];
                    console.log('defaultDates:', defaultDates);
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
                });*/

                $("#check_staff [name='dates[]']").each(function () {
                    let inputEl = this;
                    let rawVal = inputEl.value;

                    // Сохраняем оригинальное значение
                    let defaultDates = rawVal.length > 0 ? rawVal.split(' - ') : [];

                    // Сначала создаем flatpickr
                    let fp = flatpickr(inputEl, {
                        locale: 'ru',
                        mode: 'range',
                        time_24hr: true,
                        dateFormat: 'Y-m-d',
                        altFormat: 'd.m.Y',
                        conjunction: ' - ',
                        altInput: true,
                        allowInput: true,
                        minDate: "<?=$minDate?>",
                        maxDate: "<?=$maxDate?>",
                        altInputClass: 'el_input',
                        firstDayOfWeek: 1
                    });

                    // Затем устанавливаем даты программно
                    if (defaultDates.length === 2) {
                        fp.setDate(defaultDates, true); // true для режима range
                    }
                });

                let $staffs = $('.staff');

                $("select[name='institutions[]']").trigger('change');

                // При загрузке если внеплановая — сразу показываем форму
                if ($("[name='plan']").val() === '0') {
                    $("[name='plan']").trigger('change');
                }

                // Обработчик события закрытия диалога создания приказа
                $(document).off('dialog_closed.order_staff_refresh').on('dialog_closed.order_staff_refresh', function(e, dialogId) {
                    if (dialogId === 'order_staff') {
                        // Перезагружаем список приказов для выбранного плана
                        let $plan = $("[name='plan']");
                        let planId = $plan.val();

                        if (planId && planId !== '0') {
                            let $order = $('select[name=order]');
                            // Сохраняем текущее значение перед обновлением
                            let currentValue = $order.val();

                            $.post("/", {
                                ajax: 1,
                                action: "getOrdersByPlan",
                                planId: planId,
                                selected: currentValue || 0
                            }, function (data) {
                                if (data.length > 0) {
                                    let answer = JSON.parse(data);

                                    // Временно отключаем обработчик change чтобы избежать ложного срабатывания
                                    $order.off('change');

                                    $order.html(answer.order);

                                    // Автоматически выбираем последний созданный приказ (первый в списке после пустого)
                                    let $options = $order.find('option');
                                    if ($options.length > 1) {
                                        let newValue = $options.eq(1).val();
                                        // Если есть новый приказ и он отличается от текущего
                                        if (newValue && newValue !== '0' && newValue !== currentValue) {
                                            $order.val(newValue);
                                        } else if (currentValue && currentValue !== '0') {
                                            // Восстанавливаем предыдущее значение если новый приказ не был создан
                                            $order.val(currentValue);
                                        }
                                    }

                                    // Обновляем chosen и восстанавливаем обработчик change
                                    $order.trigger("chosen:updated");

                                    // Восстанавливаем обработчик change
                                    $("[name=order]").on("change", handleOrderChange);

                                    // Вызываем change только если значение действительно изменилось
                                    let finalValue = $order.val();
                                    if (finalValue && finalValue !== '0' && finalValue !== currentValue) {
                                        $order.trigger("change");
                                    }
                                }
                            });
                        }
                    }
                });

                // При загрузке из листинга (plan задан) — НЕ триггерим change сразу,
                // он вызовется автоматически после загрузки списка приказов в обработчике change плана

                // При выборе учреждения для внеплановой — обновляем скрытое поле ins
                $(document).on('change', "select[name='ins_select']", function () {
                    $("[name='ins']").val($(this).val());
                    if ($(this).val() === '0') {
                        $('#save_doc').prop('disabled', true);
                    } else {
                        $('#save_doc').prop('disabled', false);
                    }
                });

                //agreement_list.agreement_list_init();

                $("[name='plan']").on("change", function () {
                    let $self = $(this),
                        $orderInfoWrapper = $('#orderInfoWrapper'),
                        $staff_list = $('#staff_list');

                    // Если выбрана внеплановая проверка — снимаем блокировку
                    if ($self.val() === '0') {
                        $('.order-warning').hide();
                        $('#save_doc').prop('disabled', true); // до выбора учреждения
                        $('select[name=order]').closest('.item').hide();
                        $('.unplanned-ins-wrapper').show();
                        $orderInfoWrapper.show();
                        $staff_list.show();
                        return;
                    } else {
                        // При выборе плана — скрываем селект учреждения
                        $('.unplanned-ins-wrapper').hide();
                    }

                    $orderInfoWrapper.hide();
                    $staff_list.hide();
                    $.post("/", {
                        ajax: 1, action: "getOrdersByPlan", planId: $(this).val(),
                        selected: <?=intval($insId) > 0 ? intval($insId) : 0?>
                    }, function (data) {
                        let $order = $('select[name=order]'),
                            $uid = $("[name='uid']");
                        //$('.datesInputWrapper').hide();
                        if (data.length > 0) {
                            let answer = JSON.parse(data);
                            $order.html(answer.order).closest(".item").show();

                            // Выбираем первый доступный приказ (не пустой)
                            let $options = $order.find('option');
                            if ($options.length > 1) {
                                let firstOrderValue = $options.eq(1).val();
                                if (firstOrderValue && firstOrderValue !== '0') {
                                    $order.val(firstOrderValue);
                                }
                            }

                            $order.trigger("chosen:updated");

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
                    <?php if (count($exist_task) == 0 && $plan_uid === '0'): ?>
                }).trigger("change");
                <?php else: ?>
            });
            <?php endif; ?>

            // Выносим обработчик change в отдельную функцию для возможности переподключения
            function handleOrderChange() {
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
                    $staff_list = $("#staff_list"),
                    $saveBtn = $("#save_doc"),
                    $plan = $("[name='plan']");

                // Для внеплановых проверок приказ не требуется
                let isUnplanned = $plan.length > 0 && $plan.val() === '0';

                // Проверяем, что приказ выбран (только для плановых проверок)
                if (!isUnplanned && (!$self.val() || $self.val() == '0' || $self.val() == '')) {
                    $orderInfoWrapper.hide();
                    $staff_list.hide();

                    // Получаем plan_id и ins_id для создания приказа
                    let planId = $plan.val() || '0';
                    let insId = $ins.val() || '0';

                    // Показываем предупреждение
                    let warningHtml = '<div class="item w_100 order-warning" style="margin-top: 10px;">' +
                        '<div class="el_data" style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 12px; color: #856404;">' +
                        '<span class="material-icons" style="vertical-align: middle; margin-right: 6px;">warning</span>' +
                        '<strong>Приказ на проведение проверки не найден.</strong>&nbsp;Выберите план с утверждённым приказом или&nbsp;' +
                        '<a href="#" class="create-order-link" data-plan-id="' + planId + '" data-ins="' + insId + '" style="color: #856404; text-decoration: underline; font-weight: bold;">создайте приказ</a>.' +
                        '</div>' +
                        '</div>';

                    // Удаляем старое предупреждение если есть
                    $(".order-warning").remove();

                    // Вставляем предупреждение после поля выбора приказа
                    $self.closest('.item').after(warningHtml);

                    // Навешиваем обработчик на ссылку создания приказа
                    $(".create-order-link").off("click").on("click", function (e) {
                        e.preventDefault();
                        let $btn = $(this),
                            plan_id = $btn.data("plan-id"),
                            ins_id = $btn.data("ins");
                        el_app.dialog_open('order_staff', {plan_id: plan_id, ins_id: ins_id}, 'calendar');
                    });

                    // Блокируем кнопку сохранения
                    $saveBtn.prop('disabled', true).attr('title', 'Приказ не найден');

                    return;
                }

                // Удаляем предупреждение если оно было
                $(".order-warning").remove();

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

                            // Разблокируем кнопку сохранения
                            $saveBtn.prop('disabled', false).removeAttr('title');

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
                            $("[name='allowremind_flag[]']").off('change').on('change', function () {
                                let $group = $(this).closest('.group');
                                let $reminder = $group.find('.reminder');
                                let checked = $(this).prop('checked');
                                $group.find('.allowremind_actual').val(checked ? '1' : '0');
                                if (checked) {
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
            }

            // Привязываем обработчик к селекту
            $("[name=order]").on("change", handleOrderChange);

            <?php if (count($exist_task) == 0): ?>
            // Автоматически вызываем change при загрузке
            $("[name=order]").trigger('change');
            <?php endif; ?>

            $("[name='allowremind_flag[]']").off('change').on('change', function () {
                let $group = $(this).closest('.group');
                let $reminder = $group.find('.reminder');
                let checked = $(this).prop('checked');
                $group.find('.allowremind_actual').val(checked ? '1' : '0');
                if (checked) {
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
                $.post('/', {
                    ajax: 1, action: 'task_close', task_id: <?=$orderId?>,
                    module: 'calendar', form_id: 'assign_staff', log_action: 'Закрытие окна назначения'
                });
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
            })
            ;
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