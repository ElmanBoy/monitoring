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
if(isset($_POST['params']) && is_array($_POST['params'])) {
    $plan_id = intval($_POST['params']['plan_id']);
    $doc_id = intval($_POST['params']['doc_id']);
    $ins_id = intval($_POST['params']['ins_id']);
}
$doc_status = 0;

$perms = $auth->getCurrentModulePermission();
$in_calendar = isset($_POST['params']['in_calendar']) && intval($_POST['params']['in_calendar']) == 1;

$taskStr = '';
$insStr = '';
if(isset($_POST['params']['taskId'])) {
    $taskStr = $_POST['params']['taskId'];
}
if(isset($_POST['params']['insId'])){
    $insStr = $_POST['params']['insId'];
}
$task = 0;
$new_order_number = '';
$new_order_num = 1000;
$dates = '';
$exist_task = [];
$agreement_data = [];
$plan_uid = '0';
$actionPeriods = [];
$actionPeriod = '';
$checkPeriod = '';
$minDate = '';
$maxDate = '';
$doc_name = '';

if ($auth->isLogin()) {
    $ins = $db->getRegistry('institution');

    if (strlen($insStr) > 1) {//echo '1111  ';
        //Номер задачи пуст - Новая задача
        $taskArr = explode('_', $insStr);
        $plan_uid = $taskArr[0];
        $insId = intval($taskArr[1]);
        $taskId = 0;

        $exist_task = $db->select('checkstaff', " WHERE check_uid = '$plan_uid' AND institution = " . $insId);
        $plan_uid = $exist_task[$taskId]->check_uid;

    } elseif(strlen($taskStr) > 0) {//echo '2222  '.$taskStr;
        //Есть номер задачи - Редактирование существующей задачи
        $taskArr = explode('_', $taskStr);
        //$plan_uid = $taskArr[0];
        $taskId = intval($taskArr[1]);
        $current_task = $db->select('checkstaff', " WHERE id = ?", [$taskId]);
        $insId = intval($current_task[$taskId]->institution);
        $plan_uid = $current_task[$taskId]->check_uid;
        $exist_task = $db->select('checkstaff', " WHERE check_uid = '$plan_uid' AND institution = " . $insId);
        //echo '<pre>';print_r($exist_task);echo '</pre>';

    }elseif($plan_id > 0){ //Есть id плана, ищем последний номер
        $plan = $db->selectOne('checksplans', " WHERE id = '$plan_id' ORDER BY version DESC LIMIT 1"); //print_r($plan);
        if (strlen($plan->addinstitution) > 0) {
            $actionPeriods = $date->getReviewPeriodsFromJson($plan->addinstitution, $plan->year);
            $insArr = json_decode($plan->addinstitution, true);
            $plan_name = $plan->short;

            if($ins_id > 0){
                $object = stripslashes(htmlspecialchars($ins['result'][$ins_id]->short));
            }

            //Смотрим статус приказа
            $doc_data = $db->selectOne("agreement", " WHERE id = '$doc_id'");
            $doc_status = $doc_data->status;
            $doc_name = $doc_data->name;


            for ($i = 0; $i < count($insArr); $i++) {
                if (intval($insArr[$i]['institutions']) == $ins_id) {
                    $actionPeriod = $actionPeriods[$ins_id]['actionPeriod'];
                    $check_dates = json_decode($insArr[$i]['periods_hidden']); //Когда будет проверка
                    $datesArr = $date->getDatesFromMonths($check_dates, $plan->year);
                    $minDate = $actionPeriods[$ins_id]['action_start_date'];//$datesArr['start'];
                    $maxDate = $actionPeriods[$ins_id]['action_end_date'];//$datesArr['end'];
                    $checkPeriod = $actionPeriods[$ins_id]['checkPeriod'];
                    if(strlen($checkPeriod) > 0) {
                        $checkPeriodArr = explode(' - ', $checkPeriod);
                        $checkPeriodStart = $date->correctDateFormatToMysql($checkPeriodArr[0]);
                        $checkPeriodEnd = $date->correctDateFormatToMysql($checkPeriodArr[1]);
                    }
                }
            }
        }
    }

    if ($doc_id > 0) {
        //Если такой приказ уже есть
        $agreement_data = $db->selectOne('agreement', " WHERE id = " . $doc_id);
        $doc_number = $agreement_data->doc_number;
    } /*else {
        //Если новый приказ, то генерим новый номер приказа
        $doc = $db->selectOne('agreement', " WHERE source_table = 'checkinstitutions' 
        AND doc_number LIKE 'ПРП%' ORDER BY id DESC LIMIT 1");
        if (strlen($doc->doc_number) > 0) {
            $plan_number = $doc->doc_number;
            $plan_numberArr = explode('-', $plan_number);
            if ($plan_numberArr[1] == date('Y')) {
                $new_order_num = intval(str_replace('ПРП', '', $plan_numberArr[0])) + 1;
                $new_plan_number = 'ПРП' . $new_order_num . '-' . date('Y');
            }
        }else{
            $new_plan_number = 'ПРП1000-' . date('Y');
        }
    }*/

    //Если новый приказ, то генерим новый номер приказа
    /*$doc = $db->selectOne('agreement', " WHERE source_table = 'checkinstitutions'
        AND doc_number LIKE 'ПРП%' ORDER BY doc_number DESC LIMIT 1"); //echo '!!!!!!!!!';print_r($doc);
    if (strlen($doc->doc_number) > 0) {
        $plan_number = $doc->doc_number;
        $plan_numberArr = explode('-', $plan_number);
        if ($plan_numberArr[1] == date('Y')) {
            $new_order_num = intval(str_replace('ПРП', '', $plan_numberArr[0])) + 1;
            $new_plan_number = 'ПРП' . $new_order_num . '-' . date('Y');
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
        $oSubQuery = '';
        if($plan->checks > 0){
            $oSubQuery = ' AND checks = '.$plan->checks;
        }
        $orders = $db->getRegistry('documents', ' WHERE documentacial = 1'.$oSubQuery); //print_r($orders);

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

        if(strlen($plan_uid) == 0){
            $plan_uid = '0';
        }

        /*if($plan_id != '0') {
            $plan = $db->selectOne('checksplans', " WHERE id = '$plan_id' ORDER BY version DESC LIMIT 1"); //print_r($plan);
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
        }*/

        $users = $db->getRegistry('users', "where roles <> '2'", [], ['surname', 'name', 'middle_name']);
        $new_order_number = 'ПРП' . $new_order_num . '-' . date('Y');

        $prevDate = date('Y-m-d', strtotime($datesEventArr[0] .' -1 day'));

        ?>
            <style>
                /*.datesInputWrapper{
                    display: none;
                }*/
            </style>
        <div class='pop_up drag' style="width: 70rem">
            <div class='title handle'>
                <div class='name'><?= strlen(trim($doc_name)) > 0 ? 'Редактирование приказа &laquo;'.$doc_name.'&raquo;' : 'Создание нового приказа'?></div>
                <div class='button icon close'><span class='material-icons'>close</span></div>
            </div>
            <div class='pop_up_body'>
                <form class='ajaxFrm' id='order_staff' onsubmit="return false">
                    <input type='hidden' name='uid' value="<?= $plan_uid ?>">
                    <input type='hidden' name='plan_id' value="<?= $plan_id ?>">
                    <input type='hidden' name='current_ins' value="<?= $ins_id ?>">
                    <input type='hidden' name='doc_id' value="<?= $doc_id ?>">
                    <input type='hidden' name='minDate' value="<?= $date->correctDateFormatToMysql($minDate) ?>">
                    <input type='hidden' name='maxDate' value="<?= $date->correctDateFormatToMysql($maxDate) ?>">
                    <input type='hidden' name='checkMinDate' value="<?= $checkPeriodStart ?>">
                    <input type='hidden' name='checkMaxDate' value="<?= $checkPeriodEnd ?>">
                    <input type='hidden' name='path' value="calendar">
                    <input type='hidden' name='documentacial' value='1'>
                    <input type='hidden' name='approved' value='0'>
                    <input type="hidden" name="actionPeriod" value="<?=$actionPeriod?>">
                    <input type='hidden' name='checkPeriod' value="<?= $checkPeriod ?>">
                    <input type="hidden" name="doc_status" value="<?=$doc_status?>">

                    <ul class='tab-pane' style=''>
                        <li id='tab_order' class='active'>Приказ</li>
                        <li id='tab_agreement'>Согласование</li>
                        <li id='tab_preview' style="display: none">Предпросмотр</li>
                    </ul>


                    <div class="agreement_block tab-panel" id='tab_order-panel'>
                        <div class='group'>
                            <h3 class='item w_100'>
                                <strong>ПРИКАЗ О ПРОВЕДЕНИИ ПРОВЕРКИ</strong>
                            </h3>

                            <?
                            if ($plan_id == '0') { //Если задача создается не из плана
                                ?>
                                <div class='item w_50 required'>
                                    <select data-label='План' name='plan' required>
                                        <option value="0">Внеплановая проверка</option>
                                        <?
                                        $plans = $db->getRegistry('checksplans', ' WHERE active = 1');
                                        echo $gui->buildSelectFromRegistry($plans['result'], [$agreement_data->plan_id], false,
                                            ['short'], ' '
                                        );
                                        ?>
                                    </select>
                                </div>

                                <?
                            } else {
                                ?>
                                <input type='hidden' name='uid' value="<?= $plan_uid ?>">
                                <input type='hidden' name='task_id' value="<?= $taskId ?>">

                                <input type="hidden" name="plan" value="<?=$plan_id?>">
                                <div class='item w_50'>
                                    <div class='el_data'>
                                        <label>План:</label>
                                        <strong><?= $plan_name ?></strong>
                                    </div>
                                </div>
                                <? /*if($ins_id > 0){ ?>
                                <div class="item w_50">
                                    <div class="el_data">
                                        <label>Объект проверки:</label>
                                        <strong><?= $object ?></strong>
                                    </div>
                                </div>
                                <?
                                }*/
                            }
                            ?>
                            <?/*div class='item w_50 required' style='display: none'>
                                <select data-label='Объект проверки' name='ins' required>
                                </select>

                            </div*/?>
                            <?
                            $i = [
                                'type' => 'select',
                                'field_name' => 'ins',
                                'label' => 'Объект проверки',
                                'default_value' => $agreement_data->ins_id,
                                'required' => '1'
                            ];
                            echo $reg->renderSelect($i, ['default_value' => $agreement_data->ins_id]);
                            ?>
                            <?
                            $i = [
                                'type' => 'select',
                                'field_name' => 'unit_id',
                                'label' => 'Юр. адрес',
                                'default_value' => $agreement_data->unit_id,
                                'required' => '1'
                            ];
                            echo $reg->renderSelect($i, ['default_value' => $agreement_data->unit_id]);
                            ?>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <label>Номер приказа</label>
                                    <input class='el_input' type='text' name='order_number' readonly="readonly"
                                           placeholder="Номер документа формируется автоматически после согласования"
                                           value="<?= $agreement_data->doc_number ?>">
                                </div>
                            </div>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <label>Дата приказа</label>
                                    <input class='el_input' type='text' name='order_date'
                                           readonly='readonly'
                                           placeholder='Дата документа формируется автоматически после согласования'
                                           value="<?= $agreement_data->docdate ?>">
                                </div>
                            </div>
                            <?
                            if(strlen($actionPeriod) > 0) {
                                $actionPeriodArr = explode(' - ', $actionPeriod);
                                $actionPeriodStart = $date->correctDateFormatToMysql($actionPeriodArr[0]);
                                $actionPeriodEnd = $date->correctDateFormatToMysql($actionPeriodArr[1]);
                            }
                            $action_period = $agreement_data->action_period ?? $actionPeriodStart.' - '.$actionPeriodEnd;
                            if(substr_count($agreement_data->action_period, '[') > 0) {
                                $action_period = $date->getMonthDateRange(json_decode($agreement_data->action_period), $plan->year);
                            }
                            ?>
                            <div class='item w_50 required'>
                                <div class='el_data'>
                                    <label>Срок проведения проверки</label>
                                    <input class='el_input date_range' type='date' name='action_period' required
                                           value="<?= $action_period ?>">
                                </div>
                            </div>
                            <? /*
                            $p = [
                                'field_name' => 'action_period',
                                'label' => 'Срок проведения проверки',
                                'default_value' => $agreement_data->action_period_text,
                                'default_value_hidden' => $agreement_data->action_period,
                                'required' => '1'
                            ];
                            echo $reg->renderQuarter($p, []);
                            */?>
                            <div class='item w_50 required'>
                                <div class='el_data'>
                                    <label>Проверяемый период</label>
                                    <input class='el_input date_range' type='date' name='check_period' required
                                           value="<?= $agreement_data->check_period ?? $checkPeriodStart.' - '.$checkPeriodEnd ?>">
                                </div>
                            </div>
                            <div class='item w_50 required'>
                                <select data-label='Шаблон приказа' name='document' required>
                                    <?
                                    echo $gui->buildSelectFromRegistry($orders['result'], [$agreement_data->document]);
                                    ?>
                                </select>
                            </div>
                            <div class='item w_50 required'>
                                <input type='hidden' name='executors_hidden[]' value="<?= $chStaff->user ?>">
                                <select data-label='Руководитель проверки' name='executors_head' required="required">
                                    <?
                                    echo $gui->buildSelectFromRegistry($users['result'], [$agreement_data->executors_head], true,
                                        ['surname', 'name', 'middle_name'], ' '
                                    );
                                    ?>
                                </select>
                            </div>
                            <div class='item w_100 required'>
                                <input type='hidden' name='executors_list_hidden[]' value="<?= $chStaff->user ?>">
                                <select data-label='Проверяющие' multiple name='executors_list[]' required>
                                    <? $executors_selected = json_decode($agreement_data->executors_list) ?? [];
                                    echo $gui->buildSelectFromRegistry($users['result'], $executors_selected, true,
                                        ['surname', 'name', 'middle_name'], ' '
                                    );
                                    ?>
                                </select>
                            </div>
                            <?
                            echo $reg->renderFileInput([], ['document_id' => $doc_id], 'edit');
                            echo $reg->showTaskLog($doc_id, 'calendar', 'order_staff');
                            ?>
                        </div>
                    </div>
                    <div class='agreement_block tab-panel' id='tab_agreement-panel' style="display: none">
                        <div class='group'>
                            <h3 class='item w_100'>
                                <strong>ЛИСТ СОГЛАСОВАНИЯ</strong>
                            </h3>
                        </div>
                        <?= $reg->buildForm(67, [], (array)$agreement_data); ?>
                    </div>

                    <div class='preview_block tab-panel' id='tab_preview-panel' style='display: none'>
                        <iframe id='pdf-viewer' width='100%' height='600px'></iframe>
                    </div>

                    <div style="height: 30px"></div>
                    <div class='confirm'>
                        <!--<button class='button icon text' id="save_doc"><span class='material-icons'>save</span>Сохранить
                        </button>-->
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
        <?/*script src="/modules/calendar/js/registry.js"></script*/?>
        <script src='/js/assets/agreement_list.js'></script>
        <script>
            function bindOrderCalendar()
            {
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
                $('[name=order_number]').attr('readonly', true);
                $("[name='plan'], input[name=plan_id]").trigger("change");
                //el_registry.bindCalendar("<?=$minDate?>", "<?=$maxDate?>");
                let $cal = $("#check_staff [name='dates[]']").flatpickr({
                        locale: 'ru',
                        mode: 'range',
                        time_24hr: true,
                        dateFormat: 'Y-m-d',
                        altFormat: 'd.m.Y',
                        conjunction: '-',
                        altInput: true,
                        allowInput: true,
                    <?php
                    if($dates != ''){
                    ?>
                        defaultDate: ["<?= implode('", "', explode(' - ', $dates)) ?>"],
                    <?php
                    }
                    ?>
                        minDate: "<?=$minDate?>",
                        maxDate: "<?=$maxDate?>",
                        altInputClass: 'el_input',
                        firstDayOfWeek: 1
                    }),
                    $staffs = $('.staff');

                bindOrderCalendar();

                /*for (let i = 0; i < $staffs.length; i++) {
                    el_app.bindSetMinistriesByOrg($($staffs[i]));
                    el_app.bindSetUnitsByOrg($($staffs[i]));
                    //el_registry.bindSetExecutorByUnit($($staffs[i]));
                }*/
                $("select[name='institutions[]']").trigger('change');

                //agreement_list.agreement_list_init();

                $("select[name=plan], input[name=plan_id]").on("change", function () {
                    let $self = $(this),
                        selected_ins = $("[name=current_ins]").val();
                    $.post("/", {
                        ajax: 1,
                        action: "getInsFromPlan",
                        planId: $self.val(),
                        selected: selected_ins,
                        documentacial: 1
                    }, function (data) {
                        let $ins = $('select[name=ins]'),
                            $uid = $("[name='uid']"),
                            $order = $("[name='document']");
                        //$('.datesInputWrapper').hide();
                        if(data.length > 0){
                            let answer = JSON.parse(data);
                            $ins.html(answer.ins).trigger("chosen:updated").closest(".item").show();
                            $order.html(answer.order).trigger('chosen:updated')
                            setTimeout(function(){$ins.trigger("change")}, 500, $ins);
                            $uid.val(answer.uid);

                        }else{
                            $ins.html("").trigger('chosen:updated').closest('.item').hide();
                            //$uid.val("0");
                        }
                    });

                }).trigger("change");


                $("[name=ins]").on("change", function(){
                    let $self = $(this),
                        $minDate = $("[name='minDate']"),
                        $maxDate = $("[name='maxDate']"),
                        $actionPeriod = $("[name='actionPeriod']"),
                        $checkPeriod = $("[name='checkPeriod']"),
                        $unit = $("[name='unit_id']");
                    $.post('/', {
                            ajax: 1,
                            action: 'getPeriodByIns',
                            uid: $("[name=uid]").val(),
                            insId: $self.val(),
                            unit_selected: $('[name=unit_hidden]').val()
                        },
                        function (data) {
                            if(data.length > 0) {
                                let answer = JSON.parse(data);

                                $(".datesInputWrapper").show();
                                // Находим ВСЕ элементы с flatpickr и обновляем каждый
                                $("#check_staff [name='dates[]']").each(function() {
                                    // Получаем объект flatpickr из элемента
                                    let fpInstance = this._flatpickr;

                                    if (fpInstance && typeof fpInstance.set === 'function') {
                                        fpInstance.set('minDate', answer.minDate);
                                        fpInstance.set('maxDate', answer.maxDate);
                                    }
                                });
                                $minDate.val(answer.minDate);
                                $maxDate.val(answer.maxDate);
                                $actionPeriod.val(answer.actionPeriod);
                                $checkPeriod.val(answer.checkPeriod);
                                $unit.html(answer.units).trigger("chosen:updated").trigger("change");
                                bindOrderCalendar();
                            }
                        });

                    /*if($("[name='plan']").val() === '0' && $self.val() !== '0'){
                        $('.datesInputWrapper').show();
                        $cal.set('minDate', '');
                        $cal.set('maxDate', '');
                    }*/
                });

                $('#tab_order-panel .item.required input:not([type="hidden"]):not(.chosen-search-input), #tab_order-panel .item.required select')
                    .on("change input", function(){
                    let $required = $('#tab_order-panel .item.required input:not([type="hidden"]):not(.chosen-search-input), #tab_order-panel .item.required select'),
                        empty = 0;
                    for(let i = 0; i < $required.length; i++){
                        let val = $($required[i]).val();
                        if(val === ""){
                            empty++;
                        }
                    }
                    if(empty === 0){
                        $("#tab_preview").show();
                    }else{
                        $('#tab_preview').hide();
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
                <?
                if($taskStr > 0){
                    ?>
                $('#save_doc').on('mousedown keypress', function () {
                    let calEvent = calendarGrid.getEventById('<?=$taskStr?>'),
                        datesArr = $("[name='dates[]']").val().split(' - ');
                    calEvent.setProp('title', $("[name='executors[]']").find('option:selected').text());
                    calEvent.setDates(datesArr[0], datesArr[1]);
                });
                <?
                }
                ?>

                $("[name=executors_head]").on("change", function(){
                    let user_head = $(this).val(),
                        $executor_list = $("[name='executors_list[]']"),
                        currentValues = $executor_list.val();
                    $("[name='executors_list[]'] option")
                        .prop("disabled", false).attr('title', '');
                    $("[name='executors_list[]'] option[value='" + String(user_head) + "']")
                        .prop('disabled', true).attr("title", "Назначен руководителем проверки");
                    if(currentValues) {
                        let newValues = $.grep(currentValues, function (value) {
                            return value != user_head;
                        });

                        $executor_list.val(newValues).trigger('chosen:updated');
                    }
                });

                $('#order_staff #close, #order_staff .close').off('click').on('click', function (e) {
                    e.preventDefault();
                    el_app.dialog_close('order_staff');
                    $.post('/', {ajax: 1, action: 'task_close', task_id: <?=$doc_id?>,
                        module: 'calendar', form_id: 'order_staff', log_action: 'Закрытие окна приказа'});
                });

                $("input[name='dates[]'] ~ input").mask('99.99.9999 - 99.99.9999');
                $("input[name='dates[]']").trigger('input');

                $('[name=initiator]').val("<?=$_SESSION['user_id']?>").trigger('chosen:updated');

                $('select[name=agreementtemplate]').off('change').on('change', function () {
                    $.post('/', {ajax: 1, action: 'getDocTemplate', temp_id: $(this).val()}, function (data) {
                        if(data.length > 0) {
                            let answer = JSON.parse(data),
                                agreementlist = JSON.parse(answer.agreementlist);
                            $('[name=brief]').val(answer.brief);
                            $('[name=initiator]').val(answer.initiator).trigger('chosen:updated');
                            $.post('/', {
                                ajax: 1,
                                action: 'buildAgreement',
                                oneSignOnly: 1,
                                agreementlist: answer.agreementlist
                            }, function (data) {
                                $('.agreement_list_group').html(data);
                                el_app.mainInit();
                                agreement_list.agreement_list_init();
                            });
                        }
                    });
                });

                $('#save_doc').on('mousedown keypress', function () {
                    $(".agreement_block select[name='institutions[]']").attr('disabled', true);
                });

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

                agreement_list.agreement_list_init();
                $('.staff').show();

                $('.group.signers .new_signer').on('click', function () { console.log("new_signer");
                    $(".group.signers .role").html("<option value='1' selected='selected'>Подписывает</option>");
                });
            });
        </script>
        <?php
        $reg->insertTaskLog($doc_id, 'Приказ открыт для редактирования', 'calendar', 'order_staff');
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
