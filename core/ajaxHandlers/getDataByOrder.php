<?php
use Core\Db;
use Core\Date;
use Core\Gui;
use Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$db = new Db();
$date = new Date();
$gui = new Gui();
$reg = new Registry();
$orderId = intval($_POST['orderId']);
$minDate = '';
$maxDate = '';
$reviewPeriod = '';
$checkPeriod = '';
$exArr = [];
$staffListArr = [];

if($orderId > 0) {
    $users = $db->getRegistry('users', "where roles <> '2'", [], ['surname', 'name', 'middle_name']);
    $or = $db->selectOne('agreement', " WHERE id = ?", [$orderId]); //print_r($or);
    $tasks = $db->getRegistry('tasks');

    $actionPeriod = json_decode($or->action_period);
    $actionPeriodText = $or->action_period_text;
    $checkPeriodArr = explode(' - ', $or->check_period);
    $checkPeriod = 'с '.$date->dateToString($checkPeriodArr[0]).' по '.$date->dateToString($checkPeriodArr[1]);
    $datesArr = $date->getDatesFromMonths($actionPeriod);
    $minDate = $datesArr['start'];
    $maxDate = $datesArr['end'];
    $insId = $or->ins_id;
    $unitId = $or->unit_id;

    $ins = $db->selectOne("institutions", " WHERE id = ?", [$or->ins_id]);
    $unit = $db->selectOne('insadress',  " WHERE id = ?", [$or->unit_id] );

    if(strlen($or->executors_list) > 0) {
        $executors = json_decode($or->executors_list);
        $head = $or->executors_head;
        $exArr[$head] = trim($users['array'][$head][0]).' '.
            trim($users['array'][$head][1]).' '.
            trim($users['array'][$head][2]);
        foreach ($executors as $ex) {
            $exArr[$ex] = trim($users['array'][$ex][0]).' '.
                trim($users['array'][$ex][1]).' '.
                trim($users['array'][$ex][2]);
        }
    }

    if(count($exArr) > 0) {
        $count = 1;
        $task = 0;
        $chStaff = [];
        $taskId = 0;
        $dates = '';
        foreach($exArr as $user_id => $name) {
            $html = "<div class='group staff'>
            <h5 class='item w_100 question_number'>Сотрудник №$count</h5>

            <div class='item w_100'>
                <input type='hidden' name='executors[]' value='" . $user_id . "'>
                <input type='hidden' name='is_head[]' value='".($count == 1 ? '1' : '0')."'>
                <strong>".$name. '</strong>'. ($count == 1 ?
                    ' <span class="greenText"> руководитель проверки</span>' : '')."
            </div>
            <div class='item w_50'>
                <div class='el_data datesInputWrapper'>
                    <label>Период проверки</label>
                    <input class='el_input range_date' type='text' name='dates[]'
                           value='" . $dates . "'>
                </div>
            </div>
            <div class='item w_50'>
                <select data-label='Шаблон задачи' name='tasks[]'>
                    " . $gui->buildSelectFromRegistry($tasks['result'], [$task], true)."
                </select>
            </div>
            <div class='item w_50'>
                <div class='el_data'>
                    <div class='custom_checkbox'>
                        <label class='container' style='left: 4px;'>
                            <span class='label-text'>Включить напоминание</span>
                            <input type='hidden' name='allowremind_actual[]' class='allowremind_actual' value='". ($chStaff->allowremind == 1 ? '1' : '0') ."'>
                            <input type='checkbox' name='allowremind_flag[]'
                                   class='is_claim allowremind_cb' tabindex='-1'
                                   value='1'". ($chStaff->allowremind == 1 ? ' checked=\"checked\"' : '' ).">
                            <span class='checkmark'></span>
                        </label>
                    </div>
                </div>
            </div>";
            $reminder = $db->selectOne('reminders', ' WHERE task_id = ? AND employee = ?', [$taskId, $user_id]);
            // Баг 3: remind_id как массив, всегда присутствует (0 если нет записи)
            $html .= '<input type="hidden" name="remind_id[]" value="' . ($reminder->id ?? 0) . '">';
            // Баг 4: показываем блок если allowremind уже включён у сотрудника
            $reminderDisplay = ($chStaff->allowremind == 1) ? '' : ' display: none;';
            $html .= "<div class='group reminder' style='margin-top: -10px;{$reminderDisplay}'><h5
                        class='item w_100 remind_number'>Напоминание</h5>";
            $prevDate = date('Y-m-d', strtotime(date('y-m-d') . ' -1 day'));
            $rDatetime = strlen($reminder->datetime) > 0 ? str_replace(' ', 'T', $reminder->datetime) : $prevDate . 'T10:00';
            $rComment  = htmlspecialchars($reminder->comment ?? '');
            $html .= "<input type='hidden' name='remind_employee[]' value='" . intval($user_id) . "'>
            <div class='item w_50'>
                <div class='el_data'>
                    <label>Дата и время напоминания</label>
                    <input class='el_input single_date_time' type='datetime-local'
                           name='datetime[]' value='" . htmlspecialchars($rDatetime) . "'>
                </div>
            </div>
            <div class='item w_100'>
                <div class='el_data'>
                    <label>Комментарий</label>
                    <textarea class='el_textarea' name='comment[]' rows='2'>" . $rComment . "</textarea>
                </div>
            </div>";
            $html .= "</div>
            </div>";
            $staffListArr[] = $html;
            $count++;
        }
}

    echo json_encode([
        'minDate' => $minDate,
        'maxDate' => $maxDate,
        'actionPeriod' => $actionPeriod,
        'actionPeriodText' => $actionPeriodText,
        'checkPeriod' => $checkPeriod,
        'institution' => htmlspecialchars(stripslashes($ins->short)),
        'unit' => $unit->target_address,
        'insId' => $insId,
        'unitId' => $unitId,
        'executors' => $exArr,
        'staffList' => implode("\n", $staffListArr)
    ]
    );
}
