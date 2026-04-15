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

$taskStr = $_POST['params']['taskId'];
$insStr = $_POST['params']['insId'];
$in_calendar = isset($_POST['params']['in_calendar']) && intval($_POST['params']['in_calendar']) == 1;
$task = [];
$new_order_number = '';
$new_order_num = 1000;
$dates = '';
$minDate = '';
$reminder = null;

if(strlen($insStr) > 1){
    //Номер задачи пуст - Новая задача
    $taskArr = explode('_', $insStr);
    $plan_uid = $taskArr[0];
    $insId = $taskArr[1];
    $taskId = 0;
}else{
    //Есть номер задачи - Редактирование существующей задачи
    $taskArr = explode('_', $taskStr);
    $plan_uid = $taskArr[0];
    $taskId = intval($taskArr[1]);
}


if ($auth->isLogin()) {
    //Открываем транзакцию
    $busy = $db->transactionOpen('roles', 1);
    $trans_id = $busy['trans_id'];

    if ($busy != []) {

        $units = $db->getRegistry('units', 'where institution = 12 and active =1');
        $ins = $db->getRegistry('institutions');
        $insector = $db->getRegistry('institutions', 'WHERE inspectors = 1');
        $tasks = $db->getRegistry('tasks');
        $ousr = $db->getRegistry('ousr');
        $orders = $db->getRegistry('documents', ' WHERE documentacial = 1' . $auth->getDocumentMinistryFilter());


        //Если это уже назначенная задача
        if($taskId > 0) {
            $chStaff = $db->selectOne('checkstaff', ' WHERE id = ?', [$taskId]);

            $dates = $chStaff->dates;
            $datesEventArr = explode(' - ', $dates);
            $minDate = $datesEventArr[0];

            $insId = $chStaff->institution;
            $taskRaw = $chStaff->task_id;
            // task_id хранится как jsonb-массив "[1,2]" или как скаляр "1"
            $taskDecoded = json_decode($taskRaw, true);
            $task = is_array($taskDecoded) ? array_map('intval', $taskDecoded) : [intval($taskRaw)];

            if($chStaff->object_type == 0){
                $ins = $db->getRegistry('persons', '', [], ['surname', 'first_name', 'middle_name', 'birth']);
                $object = stripslashes(htmlspecialchars($ins['array'][$insId][0])).' '.
                    stripslashes(htmlspecialchars($ins['array'][$insId][1])).' '.
                    stripslashes(htmlspecialchars($ins['array'][$insId][2])).' '.
                    (strlen(trim($ins['array'][$insId][3])) > 0 ?
                        $date->correctDateFormatFromMysql($ins['array'][$insId][3]) : '');
            }else{
                $object = stripslashes(htmlspecialchars($ins['result'][$insId]->short));
            }
            $reminder = $db->selectOne('reminders', ' WHERE task_id = ? AND employee = ?', [$taskId, $chStaff->user]);
        }else{
            $object = stripslashes(htmlspecialchars($ins['array'][$insId][0]));
        }//Иначе это новая задача по клику по учреждению

        $plan = $db->selectOne('checksplans', " WHERE uid = '$plan_uid' ORDER BY version DESC LIMIT 1");
        if(strlen($plan->addinstitution) > 0) {
        $insArr = json_decode($plan->addinstitution, true);

            for ($i = 0; $i < count($insArr); $i++) {
                if (intval($insArr[$i]['institutions']) == $insId) {
                    $check_dates = json_decode($insArr[$i]['periods_hidden']);
                    $datesArr = $date->getDatesFromMonths($check_dates, $plan->year);
                    $minDate = $datesArr['start'];
                    $maxDate = $datesArr['end'];
                }
            }
        }

        $staffMinistryFilter = '';
        if (!$auth->isAdmin() && !$auth->haveUserRole(2)) {
            $userMinistries = $_SESSION['user_ministry'] ?? [];
            if (!empty($userMinistries)) {
                $ministryConds = array_map(fn($id) => "ministries @> '[" . intval($id) . "]'", $userMinistries);
                $staffMinistryFilter = " AND (roles @> '[\"2\"]' OR " . implode(' OR ', $ministryConds) . ")";
            }
        }
        $users = $db->getRegistry('users', "where roles <> '2'" . $staffMinistryFilter . " ORDER BY surname, name, middle_name", [], ['surname', 'name', 'middle_name']);
        $new_order_number = 'ПРП' . $new_order_num . '-' . date('Y');
        $prevDate = date('Y-m-d', strtotime($datesEventArr[0] .' -1 day'));

        // --- Логика запроса на продление (только для существующих задач) ---
        $currentUserId = intval($_SESSION['user_id']);
        $isOwnTask = false;
        $isHead    = false;
        $extStatus = 0;
        $extDates  = '';
        $extComment = '';
        $extMinDate = date('Y-m-d');
        if ($taskId > 0) {
            $isOwnTask = (intval($chStaff->user) === $currentUserId);
            $headRecord = $db->selectOne('checkstaff',
                ' WHERE check_uid = ? AND institution = ? AND is_head = 1 AND active = 1 AND "user" = ?',
                [$chStaff->check_uid, $chStaff->institution, $currentUserId]
            );
            $isHead     = ($headRecord !== null) || $auth->isAdmin();
            $extStatus  = intval($chStaff->extension_request_status ?? 0);
            $extDates   = $chStaff->extension_requested_dates ?? '';
            $extComment = $chStaff->extension_request_comment ?? '';
            $currentEndDate = isset($datesEventArr[1]) ? trim($datesEventArr[1]) : '';
            $extMinDate = $currentEndDate ? date('Y-m-d', strtotime($currentEndDate . ' +1 day')) : date('Y-m-d');
        }
        ?>
        <div class='pop_up drag' style='width: 60vw; min-height: 50vh;'>
            <div class='title handle'>
                <div class='name'><?= ($taskId > 0 ? 'Редактирование назначения на проверку' : 'Назначение на проверку') ?></div>
                <div class='button icon close'><span class='material-icons'>close</span></div>
            </div>
            <div class='pop_up_body'>
                <form class='ajaxFrm' id='view_staff' onsubmit="return false">
                    <input type="hidden" name="uid" value="<?=$plan_uid?>">
                    <input type='hidden' name='task_id' value="<?= $taskId ?>">
                    <input type='hidden' name='ins' value="<?= $insId ?>">
                    <input type='hidden' name='minDate' value="<?= $minDate ?>">
                    <input type='hidden' name='maxDate' value="<?= $maxDate ?>">
                    <input type="hidden" name="in_calendar" value="<?=intval($_POST['params']['in_calendar'])?>">
                    <input type='hidden' name='path' value="calendar">
                    <div class='group'>
                        <div class="item w_100">
                            <div class="el_data">
                                <label>Объект проверки:</label>
                                <strong id="object_name"><?= $object ?></strong>
                            </div>
                        </div>
                        <div class='group w_100'><label>Кто проверяет:</label></div>
                        <div class='group staff'>
                            <h5 class='item w_100 question_number'>Сотрудник №1</h5>

                            <div class='item w_50'>
                                <div class='el_data'>
                                    <label>Период проверки</label>
                                    <input class='el_input range_date' type='text' name='dates[]'
                                           value="<?= $dates ?>">
                                </div>
                            </div>
                            <?/*div class='item w_50'>
                                <select data-label='Ведомство' name='institutions[]'>
                                    <?
                                    echo $gui->buildSelectFromRegistry($insector['result'], [1], true);
                                    ?>
                                </select>
                            </div>
                            <div class='item w_50'>
                                <input type='hidden' name='ministries_hidden[]' value="<?= $chStaff->ministry ?>">
                                <select data-label='Управление' name='ministries[]'>

                                </select>
                            </div>
                            <div class='item w_50'>
                                <input type="hidden" name="units_hidden[]" value="<?=$chStaff->unit?>">
                                <select data-label='Отдел' name='units[]'>
                                    <? /*
                                    echo $gui->buildSelectFromRegistry($units['result'], [$chStaff->unit], true);
                                    ?>
                                </select>
                            </div*/?>

                            <div class='item w_50'>
                                <input type='hidden' name='executors_hidden[]' value="<?= $chStaff->user ?>">
                                <select data-label='Сотрудник' name='executors[]'>
                                    <?
                                    echo $gui->buildSelectFromRegistry($users['result'], [$chStaff->user], true,
                                        ['surname', 'name', 'middle_name'], ' '
                                    );
                                    ?>
                                </select>
                            </div>

                            <div class='item w_50'>
                                <div class='custom_checkbox'>
                                    <label class='container' style="top: 12px;">
                                        <span class='label-text'>Является руководителем проверки</span>
                                        <input type='radio' name='is_head[]' class='is_claim'
                                               tabindex='-1' value='1'<?=(intval($chStaff->is_head) == 1 ? ' checked="checked"' : '')?>>
                                        <span class='checkmark radio'></span>
                                    </label>
                                </div>
                            </div>

                            <div class='item w_50' style="display: none">
                                <select data-label='Структурное подразделение' name='ousr[]'>
                                    <?
                                    echo $gui->buildSelectFromRegistry($ousr['result'], [$chStaff->ousr], true);
                                    ?>
                                    ?>
                                </select>
                            </div>
                            <div class='item w_50'>
                                <select data-label='Шаблон задачи' name='tasks[]' multiple>
                                    <?
                                    echo $gui->buildSelectFromRegistry($tasks['result'], $task, false);
                                    ?>
                                </select>
                            </div>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <div class='custom_checkbox'>
                                        <label class='container' style="left: 4px;">
                                            <span class='label-text'>Включить напоминание</span>
                                            <input type='checkbox' name='allowremind'
                                                   class='is_claim' tabindex='-1' value='1'<?=$chStaff->allowremind == 1 ? ' checked="checked"' : ''?>>
                                            <span class='checkmark'></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?
                            if($reminder != null){
                                echo '<input type="hidden" name="remind_id" value="'.$reminder->id.'">';
                            }
                            ?>
                        </div>
                    </div>

                    <div class="group reminder" style="margin-top: -10px;"><h5 class='item w_100 remind_number'>Напоминание</h5></div>
                    <?
                    echo $reg->buildForm(71, [], [
                            'datetime' => strlen($reminder->datetime) > 0 ? $reminder->datetime : $prevDate . ' 10:00',
                            'employee' => intval($reminder->employee) > 0 ? $reminder->employee : $chStaff->user,
                            'comment' => $reminder->comment
                        ]
                    );
                    ?>

                    <?php if ($taskId > 0 && ($isOwnTask || $isHead)): ?>
                    <!-- Блок запроса на продление -->
                    <div class="group">
                        <div class="group staff">
                            <div id="extension_block">

                            <?php if ($isOwnTask && !$isHead): ?>
                                <!-- Инспектор: показываем статус и форму запроса -->
                                <?php if ($extStatus === 1): ?>
                                    <div class="el_data" style="background:#fff3cd;border:1px solid #ffc107;border-radius:4px;padding:10px;margin-bottom:0.5rem;">
                                        <span class="material-icons" style="vertical-align:middle;margin-right:4px;">hourglass_empty</span>
                                        <strong>Запрос на продление ожидает рассмотрения.</strong>
                                        Запрошенный период: <strong><?= htmlspecialchars($extDates) ?></strong>
                                        <?php if (strlen($extComment) > 0): ?>
                                            <br><em><?= htmlspecialchars($extComment) ?></em>
                                        <?php endif; ?>
                                        <br><button class="button icon text red" id="btn_cancel_extension" style="margin-top:6px;">
                                            <span class="material-icons">cancel</span>Отозвать запрос
                                        </button>
                                    </div>
                                <?php elseif ($extStatus === 2): ?>
                                    <div class="el_data" style="background:#d4edda;border:1px solid #28a745;border-radius:4px;padding:10px;margin-bottom:0.5rem;">
                                        <span class="material-icons" style="vertical-align:middle;margin-right:4px;color:#28a745;">check_circle</span>
                                        <strong>Продление утверждено.</strong> Новый период: <strong><?= htmlspecialchars($extDates) ?></strong>
                                    </div>
                                <?php elseif ($extStatus === 3): ?>
                                    <div class="el_data" style="background:#f8d7da;border:1px solid #dc3545;border-radius:4px;padding:10px;margin-bottom:0.5rem;">
                                        <span class="material-icons" style="vertical-align:middle;margin-right:4px;color:#dc3545;">cancel</span>
                                        <strong>Запрос отклонён.</strong>
                                        <?php if (strlen($extComment) > 0): ?>
                                            Причина: <em><?= htmlspecialchars($extComment) ?></em>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($extStatus !== 1): ?>
                                    <div id="extension_form_wrap">
                                        <h5 style="margin:0.5rem 0 0.3rem;">Запросить продление срока проверки</h5>
                                        <div class="group" style="margin:0;">
                                            <div class="item w_50">
                                                <div class="el_data">
                                                    <label>Новая дата окончания проверки</label>
                                                    <input class="el_input" id="ext_dates_input" type="text"
                                                           placeholder="Выберите дату">
                                                </div>
                                            </div>
                                            <div class="item w_50">
                                                <div class="el_data">
                                                    <label>Комментарий (необязательно)</label>
                                                    <textarea class="el_input" id="ext_comment_input"
                                                              placeholder="Почему нужно продление..."></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <button class="button icon text blue" id="btn_request_extension">
                                            <span class="material-icons">update</span>Отправить запрос руководителю
                                        </button>
                                    </div>
                                <?php endif; ?>

                            <?php elseif ($isHead && $extStatus === 1): ?>
                                <!-- Руководитель: видит запрос инспектора -->
                                <?php
                                $inspectorUser = $db->selectOne('users', ' WHERE id = ?', [$chStaff->user]);
                                $inspectorFio = trim($inspectorUser->surname ?? '') . ' ' . trim($inspectorUser->name ?? '') . ' ' . trim($inspectorUser->middle_name ?? '');
                                ?>
                                <div class="el_data" style="background:#fff3cd;border:1px solid #ffc107;border-radius:4px;padding:10px;margin-bottom:0.5rem;">
                                    <span class="material-icons" style="vertical-align:middle;margin-right:4px;">pending_actions</span>
                                    <strong>Запрос на продление от инспектора <?= htmlspecialchars(trim($inspectorFio)) ?>:</strong><br>
                                    Текущий период: <strong><?= htmlspecialchars($chStaff->dates) ?></strong><br>
                                    Запрошенный период: <strong><?= htmlspecialchars($extDates) ?></strong>
                                    <?php if (strlen($extComment) > 0): ?>
                                        <br>Комментарий: <em><?= htmlspecialchars($extComment) ?></em>
                                    <?php endif; ?>
                                </div>
                                <div class="group" style="margin:0 0 0.5rem;">
                                    <div class="item w_50">
                                        <div class="el_data">
                                            <label>Комментарий при отклонении</label>
                                            <textarea class="el_input" id="ext_resolve_comment"
                                                      placeholder="Причина отклонения..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button class="button icon text" id="btn_approve_extension">
                                    <span class="material-icons">check_circle</span>Одобрить
                                </button>
                                <button class="button icon text red" id="btn_reject_extension" style="margin-left:0.5rem;">
                                    <span class="material-icons">cancel</span>Отклонить
                                </button>
                            <?php endif; ?>
                        </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="height: 100px"></div>
                    <div class='confirm'>
                        <?
                        if($in_calendar && intval($perms['delete']) == 1){
                        ?>
                            <button class='button icon text red' id='remove_event'><span class='material-icons'>delete</span>Удалить
                            </button>
                        <?
                        }
                        if(intval($perms['edit']) == 1){
                        ?>
                        <button class='button icon text' id="save_doc"><span class='material-icons'>save</span>Сохранить</button>
                        <?
                        }else{
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
            <?php
            if($chStaff->allowremind == 1){
            ?>
            $('.reminder, .reminder ~ div:first()').show();
            <?php
            }else{
            ?>
            $('.reminder, .reminder ~ div:first()').hide();
            <?php
            }
            ?>
            $(document).ready(function(){
                const $reminder = $('.reminder, .reminder ~ div:first()');
                el_calendar_registry.bindCalendar("<?=$minDate?>", "<?=$maxDate?>");
                let cal = $(".staff [name='dates[]']").flatpickr({
                    locale: 'ru',
                    mode: 'range',
                    time_24hr: true,
                    dateFormat: 'Y-m-d',
                    altFormat: 'd.m.Y',
                    conjunction: '-',
                    altInput: true,
                    allowInput: true,
                    defaultDate: '',
                    minDate: "<?=$minDate?>",
                    maxDate: "<?=$maxDate?>",
                    altInputClass: 'el_input',
                    firstDayOfWeek: 1,
                })
                //el_app.bindSetMinistriesByOrg($(".staff"));
                //el_app.bindSetUnitsByOrg($('.staff'));
                //el_calendar_registry.bindSetExecutorByUnit($('.staff'));
                agreement_list.agreement_list_init();
                $("select[name='institutions[]']").trigger("change");

                $("[name='allowremind']").off("change").on("change", function(){
                    if($(this).prop("checked")){
                        $reminder.show();
                        $reminder.find("input, select, textarea").attr("disabled", false);
                    }else{
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
                        $.post("/", {ajax: 1, action: "event_delete", id: '<?=$taskId?>'}, function(data){
                            let answer = JSON.parse(data);
                            if(answer.result){
                                inform('Отлично!', answer.resultText);
                            }else{
                                el_tools.notify('error', 'Ошибка', answer.resultText);
                            }
                        });
                        el_app.dialog_close("view_staff");
                    }
                });

                $("#save_doc").on("mousedown keypress", function(){
                    let calEvent = calendarGrid.getEventById('<?=$taskStr?>'),
                        datesArr = $("[name='dates[]']").val().split(' - ');
                    calEvent.setProp("title", $("[name='executors[]']").find('option:selected').text());
                    calEvent.setDates(datesArr[0], datesArr[1]);
                });

                $("#close").off("click").on("click", function(e){
                    e.preventDefault();
                    el_app.dialog_close("view_staff");
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
                $("input[name='dates[]']").trigger('input');

                $('[name=initiator]').val("<?=$_SESSION['user_id']?>").trigger('chosen:updated');

                $('select[name=agreementtemplate]').off('change').on('change', function () {
                    $.post('/', {ajax: 1, action: 'getDocTemplate', temp_id: $(this).val()}, function (data) {
                        let answer = JSON.parse(data),
                            agreementlist = JSON.parse(answer.agreementlist);
                        $('[name=brief]').val(answer.brief);
                        $('[name=initiator]').val(answer.initiator).trigger('chosen:updated');
                        $.post('/', {ajax: 1, action: 'buildAgreement', agreementlist: answer.agreementlist}, function (data) {
                            $('.agreement_list_group').html(data);
                            el_app.mainInit();
                            agreement_list.agreement_list_init();
                        });
                    });
                });

                $('#save_doc').on('mousedown keypress', function () {
                    $(".agreement_block select[name='institutions[]']").attr('disabled', true);
                });

                $('[name=order_number]').val('<?=$new_order_number?>');

                <?php
                if(intval($perms['edit']) != 1){
                ?>
                $("form#view_staff input, form#view_staff select, form#view_staff textarea").not("#extension_block *").attr("disabled", true).trigger('chosen:updated');
                <?php
                }
                ?>

                <?php if ($taskId > 0 && $isOwnTask && !$isHead && $extStatus !== 1): ?>
                // --- Datepicker для запроса продления (только новая дата окончания) ---
                flatpickr("#ext_dates_input", {
                    locale: 'ru',
                    dateFormat: 'Y-m-d',
                    altFormat: 'd.m.Y',
                    altInput: true,
                    altInputClass: 'el_input',
                    allowInput: true,
                    minDate: "<?= $extMinDate ?>",
                    firstDayOfWeek: 1
                });

                $("#btn_request_extension").off("click").on("click", function () {
                    let newEndDate = $("#ext_dates_input").val(),
                        comment = $("#ext_comment_input").val();
                    if (!newEndDate) {
                        el_tools.notify('error', 'Ошибка', 'Укажите новую дату окончания проверки');
                        return;
                    }
                    $(this).attr('disabled', true);
                    $.post("/", {
                        ajax: 1,
                        path: 'calendar',
                        action: 'request_extension',
                        task_id: <?= $taskId ?>,
                        new_end_date: newEndDate,
                        request_comment: comment
                    }, function (data) {
                        let answer = JSON.parse(data);
                        if (answer.result) {
                            inform('Готово', answer.resultText);
                            setTimeout(function () { el_app.dialog_close('view_staff'); }, 1500);
                        } else {
                            el_tools.notify('error', 'Ошибка', answer.resultText);
                            $("#btn_request_extension").attr('disabled', false);
                        }
                    });
                });
                <?php endif; ?>

                <?php if ($taskId > 0 && $isOwnTask && !$isHead && $extStatus === 1): ?>
                // --- Отзыв запроса ---
                $("#btn_cancel_extension").off("click").on("click", async function () {
                    let ok = await confirm("Отозвать запрос на продление?");
                    if (!ok) return;
                    $.post("/", {
                        ajax: 1,
                        path: 'calendar',
                        action: 'resolve_extension',
                        task_id: <?= $taskId ?>,
                        resolve_action: 'reject',
                        comment: 'Отозвано инспектором'
                    }, function (data) {
                        let answer = JSON.parse(data);
                        if (answer.result) {
                            inform('Готово', 'Запрос отозван');
                            setTimeout(function () { el_app.dialog_close('view_staff'); }, 1200);
                        } else {
                            el_tools.notify('error', 'Ошибка', answer.resultText);
                        }
                    });
                });
                <?php endif; ?>

                <?php if ($taskId > 0 && $isHead && $extStatus === 1): ?>
                // --- Руководитель: одобрить / отклонить ---
                $("#btn_approve_extension").off("click").on("click", function () {
                    $.post("/", {
                        ajax: 1,
                        path: 'calendar',
                        action: 'resolve_extension',
                        task_id: <?= $taskId ?>,
                        resolve_action: 'approve',
                        comment: $("#ext_resolve_comment").val()
                    }, function (data) {
                        let answer = JSON.parse(data);
                        if (answer.result) {
                            inform('Готово', answer.resultText);
                            setTimeout(function () { el_app.dialog_close('view_staff'); }, 1500);
                        } else {
                            el_tools.notify('error', 'Ошибка', answer.resultText);
                        }
                    });
                });

                $("#btn_reject_extension").off("click").on("click", async function () {
                    let comment = $("#ext_resolve_comment").val();
                    if (!comment) {
                        let ok = await confirm("Отклонить без комментария?");
                        if (!ok) return;
                    }
                    $.post("/", {
                        ajax: 1,
                        path: 'calendar',
                        action: 'resolve_extension',
                        task_id: <?= $taskId ?>,
                        resolve_action: 'reject',
                        comment: comment
                    }, function (data) {
                        let answer = JSON.parse(data);
                        if (answer.result) {
                            inform('Готово', answer.resultText);
                            setTimeout(function () { el_app.dialog_close('view_staff'); }, 1500);
                        } else {
                            el_tools.notify('error', 'Ошибка', answer.resultText);
                        }
                    });
                });
                <?php endif; ?>

            });
        </script>
        <?php
    } else {
        ?>
        <script>
            alert("Эта запись редактируется пользователем <?=$busy->user_name?>");
            el_app.dialog_close("role_edit");
        </script>
        <?
    }

} else {
    echo '<script>alert("Ваша сессия устарела.");document.location.href = "/"</script>';
}

?>
