<?php

use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Date;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$planId = 0;

if (isset($_GET['plan']) && intval($_GET['plan']) > 0 && !isset($_POST['params'])) {
    $planId = intval($_GET['plan']);
} else {
    parse_str($_POST['params'], $paramArr);
    foreach ($paramArr as $name => $value) {
        $_GET[$name] = $value;
    }
    $planId = intval($_GET['id']);
    $_GET['url'] = $_POST['url'];
}


$gui = new Gui;
$db = new Db;
$auth = new Auth();
$date = new Date();
$checks = [];
$planYear = [];
$planUid = [];

$perms = $auth->getCurrentModulePermission();

if ($planId > 0) { //Если выбран конкретный план
    $plan = $db->selectOne('checksplans', ' where id = ?', [$planId]);
    $planUid[0] = $plan->uid;
    $planYear[0] = $plan->year;
    //$planId[0] = $plan->id;
    $checks[0] = json_decode($plan->addinstitution, true);
} else { //Выбран пункт "Задачи без плана". То есть все планы (???)
    $plans = $db->select('checksplans');
    $i = 0;
    foreach ($plans as $plan) {
        $planUid[$i] = $plan->uid;
        $planYear[$i] = $plan->year;
        //$planId[$i] = $plan->id;
        $checks[$i] = json_decode($plan->addinstitution, true);
        $i++;
    }
    //echo '<pre>';print_r($checks); print_r($planUid);echo '</pre>';
    $null_checks = $db->select('checkstaff', " 
    WHERE active = 1 AND (check_uid = '0' OR check_uid IS NULL)"
    ); // print_r($null_checks);
    $n = $i;
    $null_che = [];
    foreach ($null_checks as $che) {

        $checks[$n][0]['institutions'] = intval($che->institution);
        $checks[$n][0]['object_type'] = $che->object_type;
        $planUid[$n] = '0';
        $n++;
    }
    //print_r($null_checks);
    //reset($checks);
    //$checks[0] = array_merge($checks[0], $null_che);
    //echo '<pre>';print_r($checks); print_r($planUid);echo '</pre>';
}

$persons = $db->getRegistry('persons', '', [], ['surname', 'first_name', 'middle_name', 'birth']);
$ins = $db->getRegistry('institutions', '', [], ['short']);
$units = $db->getRegistry('units');
$users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name']);
$insp = $db->getRegistry('inspections');
$tasks_templates = $db->getRegistry('tasks');


$gui->set('module_id', 14);
?>
<style>
    .insName {

    }
</style>
<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
                'title' => 'Календарь проверок',
                'planList' => 'Выбор плана',
                'renew' => 'Обновить календарь',
                'create' => 'Создать задание',
                'switch_plan' => 'Показать',

                //'plans' => 'Планы проверок',
                //'filter_panel' => 'Открыть панель фильтров',
                //'clone' => 'Копия записи',
                //'delete' => 'Удалить выделенные',
                'logout' => 'Выйти'
            ]
        );
        ?>

        <? /*div class="button icon text" title="Журнал работ">
			<span class="material-icons">fact_check</span>Журнал работ
		</div*/ ?>
    </div>

</div>
<div class="scroll_wrap">
    <form method='post' id='table'
          class='ajaxFrm scroll_current'<?= $_COOKIE['calendar_view'] == 'table' ? '' : ' style="display: none"' ?>>
        <!--<input type='hidden' name='registry_id' id='registry_id' value="<? /*= $planId */ ?>">-->
        <table class='table_data statistic' id='tbl_registry_items'>
            <thead>
            <tr class='fixed_thead'>
                <th class='sort'>
                    <?
                    echo $gui->buildSortFilter(
                        'registryitems',
                        '№',
                        'id',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'registryitems',
                        'Объект проверки',
                        'parent_items',
                        'constant',
                        []//$checks['array']
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'registryitems',
                        'Исполнитель',
                        'name',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class='sort'>
                    <?
                    echo $gui->buildSortFilter(
                        'registryitems',
                        'Даты проверки',
                        'name',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class='sort'>
                    <?
                    echo $gui->buildSortFilter(
                        'registryitems',
                        'Статус задачи',
                        'name',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class='sort'>
                    <?
                    echo $gui->buildSortFilter(
                        'registryitems',
                        'Шаблон задачи',
                        'name',
                        'el_data',
                        []
                    );
                    ?>
                </th>
            </tr>
            </thead>


            <tbody>
            <!-- row -->
            <?
            $taskArr = [];
            $subTasks = [];
            $cal_events = [];
            $cal_resource = [];
            $allDates = [];
            $check_number = 1;
            $null_ins = [];
            $allDatesStart = [];
            $allDatesEnd = [];
            $allIns = [];
            $taskIds = [];

            // Загружаем все приказы по учреждениям одним запросом
            $agreementsByIns = [];
            $allAgreements = $db->select('agreement', " WHERE source_table = 'checkinstitutions'");
            foreach ($allAgreements as $agr) {
                $agreementsByIns[intval($agr->source_id)] = $agr;
            }

            if (is_array($checks) && count($checks) > 0) {
                for ($p = 0; $p < count($checks); $p++) {
                    foreach ($checks[$p] as $ch) {
                        //if (!in_array($ch['institutions'], $allIns)) {
                        $allIns[] = $ch['institutions'];
                        $dates = $date->getDatesFromMonths(json_decode($ch['periods_hidden']), $planYear[$p]);
                        $tasks = [];
                        //Собираем массив проверок без дат из плана (внеплановые)
                        if ($dates == []) {
                            $null_ins[] = $ch['institutions'];
                        }

//echo " !!!!!!!!!! WHERE check_uid = '$planUid[$p]' AND institution = '" . $ch['institutions'] . "' ".$ch['object_type']."<br>";
                        $tasks = $db->getRegistry('checkstaff', " WHERE check_uid = '$planUid[$p]' AND institution = '" . $ch['institutions'] . "'");
                        //echo $check_number.' '.$ch['institutions'].' '.count($tasks).'<br>'; print_r($tasks);
                        if (count($tasks['result']) > 0) {
                            $task_number = 1;
                            foreach ($tasks['result'] as $task) {
                                if (!in_array($task->id, $taskIds)) { //исключаем повторения
                                    $dateArr = explode(' - ', $task->dates);
                                    $allDates[] = $dateArr[0];
                                    $allDatesStart[$ch['institutions']][] = $dateArr[0];
                                    $allDatesEnd[$ch['institutions']][] = $dateArr[1];
                                    $executorFio = trim($users['array'][$task->user][0])
                                        . ' ' . trim($users['array'][$task->user][1]) . ' ' . trim($users['array'][$task->user][2]);

                                    $object = $task->object_type == 1 ? stripslashes($ins['result'][$ch['institutions']]->short) . ' ' .
                                        stripslashes(htmlspecialchars($units['array'][$ch['units']])) :
                                        stripslashes(htmlspecialchars($persons['array'][$ch['institutions']][0])) . ' ' .
                                        stripslashes(htmlspecialchars($persons['array'][$ch['institutions']][1])) . ' ' .
                                        stripslashes(htmlspecialchars($persons['array'][$ch['institutions']][2])) . ' ' .
                                        (strlen(trim($persons['array'][$ch['institutions']][3])) > 0 ?
                                            $date->correctDateFormatFromMysql($persons['array'][$ch['institutions']][3]) : '');
                                    $object = str_replace("\n", ' ', $object);

                                    if ($task_number == 1) {
                                        $opClosedClass = ' opened';
                                        if ($_COOKIE['openTaskins' . $ch['institutions']] != 'true' || !isset($_COOKIE['openTaskins' . $ch['institutions']])) {
                                            $opClosedClass = ' closed';
                                        }
                                        echo '<tr data-ins="' . $ch['institutions'] . '" tabindex="0" class="noclick interactive">' .
                                            '<td>' . $check_number . '<div class="circle-plus' . $opClosedClass . '" title="Показать подзадачи">' .
                                            '<div class="circle"><div class="horizontal"></div>' .
                                            '<div class="vertical"></div></div></div></td>' .
                                            '<td class="insName">' . $object . '</td><td colspan="5"></td></tr>';


                                        $ganttStart = $dates != [] ? $dates['start'] : (
                                        !empty($allDatesStart[$ch['institutions']]) ? min($allDatesStart[$ch['institutions']]) : ''
                                        );
                                        $ganttEnd = $dates != [] ? $dates['end'] : (
                                        !empty($allDatesEnd[$ch['institutions']]) ? max($allDatesEnd[$ch['institutions']]) : ''
                                        );
                                        if (!empty($ganttStart) && !empty($ganttEnd)) {
                                            $taskArr[$ch['institutions']] = '{
                                                id: "' . $planUid[$p] . '_' . $ch['institutions'] . '",
                                                name: \'' . $object . '\',
                                                start: "' . $ganttStart . '",
                                                end: "' . $ganttEnd . '"
                                            }';
                                        }
                                        $cal_resource[] = "{
                                            id: '" . $ch['institutions'] . "',
                                            title: '" . $object . "'
                                        }";
                                        $check_number++;
                                    }


                                    echo '
                                    <tr data-id="' . $task->id . '" data-parent="ins' . $ch['institutions'] . '" tabindex="0" class="noclick"' .
                                        ($_COOKIE['openTaskins' . $ch['institutions']] == 'true' ? '' : ' style="display: none"') . '>
                                        <td>' . ($check_number - 1) . '.' . $task_number . '</td>
                                        <td>&nbsp;</td>
                                        <td>' . $executorFio . ' ' . (intval($task->is_head) == 1 ? '<small class="greenText"> руководитель</small>' : '') . '</td>
                                        <td>с ' . $date->correctDateFormatFromMysql($dateArr[0]) . ' по ' .
                                        $date->correctDateFormatFromMysql($dateArr[1]) . '</td>
                                        <td>Назначена</td>
                                        <td>' . $tasks_templates['array'][$task->task_id] . '</td>
                                        <td class="link">
                                            <span class="material-icons view_staff" data-id="' . $task->check_uid . '_' . $task->id . '" title="Редактирование задачи">edit</span>
                                            <!--span class="material-icons view_task" data-id="' . $task->id . '" title="Просмотр задачи">pageview</span-->
                                        </td>
                                    </tr>';


                                    $insId = $ins['result'][$task->institution]->id;
                                    if (!isset($insColor[$insId])) {
                                        $insColor[$insId] = $gui->generateDarkHslHexColor();
                                    }
                                    //$insName = htmlspecialchars($ins['result'][$task->institution]->short);
                                    $name = $object . '  <br>Проверяющий: ' . trim($users['array'][$task->user][0])
                                        . ' ' . trim($users['array'][$task->user][1]) . ' ' . trim($users['array'][$task->user][2]);
                                    $executor = 'Проверяющий: ' . trim($users['array'][$task->user][0])
                                        . ' ' . trim($users['array'][$task->user][1]) . ' ' . trim($users['array'][$task->user][2]);
                                    $subTasks[] = '{
                                        id: "' . $planUid[$p] . '_' . $task->id . '",
                                        name: \'' . $name . '\',
                                        start: "' . $dateArr[0] . '",
                                        end: "' . $dateArr[1] . '",
                                        dependencies : "' . $planUid[$p] . '_' . $task->institution . '",
                                        custom_class : "child_task"
                                    }';

                                    $cal_events[] = "{
                                        id: '" . $planUid[$p] . '_' . $task->id . "',
                                        title: '$executor',
                                        start: '" . $dateArr[0] . "',
                                        end: '" . $dateArr[1] . "',
                                        group: '$object',
                                        resourceId: '" . $insId . "',
                                        color: '$insColor[$insId]' 
                                    }";


                                    $task_number++;
                                }
                                $taskIds[] = $task->id;
                            }

                        } else {
                            $object = str_replace("\n", ' ', stripslashes($ins['result'][$ch['institutions']]->short));
                            $insIdInt = intval($ch['institutions']);
                            $agr = isset($agreementsByIns[$insIdInt]) ? $agreementsByIns[$insIdInt] : null;
                            $agrApproved = $agr && (intval($agr->status) == 1 || intval($agr->approved) == 1);

                            if ($agr === null) {
                                // Приказ не создан
                                $actionCell = '<small><a href="" class="new_order">' .
                                    '<span class="material-icons">control_point</span> Создать приказ на проверку</a></small>';
                            } elseif (!$agrApproved) {
                                // Приказ создан, но не утверждён
                                $actionCell = '<small><span class="material-icons" style="vertical-align:middle;color:var(--color_warning,#e6a817)">hourglass_empty</span> ' .
                                    'Приказ на согласовании</small>';
                            } else {
                                // Приказ утверждён — можно назначать
                                $actionCell = '<small><a href="" class="assign_staff_btn" data-uid="' . $planUid[$p] . '" data-ins="' . $insIdInt . '">' .
                                    '<span class="material-icons">assignment_ind</span> Назначить проверяющих</a></small>';
                            }

                            echo '<tr data-ins="' . $ch['institutions'] . '" tabindex="0" class="noclick notask">' .
                                '<td>' . $check_number . '</td>' .
                                '<td class="insName">' . stripslashes(htmlspecialchars($ins['result'][$ch['institutions']]->short)) . '</td>' .
                                '<td colspan="5">' . $actionCell . '</td></tr>';
//print_r($ch);
                            $null_ins[] = $ch['institutions'];
                            $cal_resource[] = "{
                                            id: '" . $ch['institutions'] . "',
                                            title: '" . $object . "'
                                        }";
                            $dateArr = explode(' - ', $ch['check_periods']);
                            if (!empty($dateArr[0]) && !empty($dateArr[1])) {
                                $allDatesStart[$ch['institutions']][] = $dateArr[0];
                                $allDatesEnd[$ch['institutions']][] = $dateArr[1];
                            }
                            $ganttStart = $dates != [] ? $dates['start'] : (
                            !empty($allDatesStart[$ch['institutions']]) ? min($allDatesStart[$ch['institutions']]) : ''
                            );
                            $ganttEnd = $dates != [] ? $dates['end'] : (
                            !empty($allDatesEnd[$ch['institutions']]) ? max($allDatesEnd[$ch['institutions']]) : ''
                            );
                            if (!empty($ganttStart) && !empty($ganttEnd)) {
                                $taskArr[$ch['institutions']] = '{
                                            id: "' . $planUid[$p] . '_' . $ch['institutions'] . '",
                                            name: \'' . $object . '\',
                                            start: "' . $ganttStart . '",
                                            end: "' . $ganttEnd . '"
                                        }';
                            }
                        }
                        $check_number++;
                        //}
                    }


                }
            }

            if (count($null_ins) > 0) {
                foreach ($null_ins as $id => $nis) {
                    if (!empty($allDatesStart[$nis]) && !empty($allDatesEnd[$nis]) && isset($taskArr[$nis])) {
                        $taskArr[$nis] = preg_replace('/start: "(.*)",/', 'start: "' . min($allDatesStart[$nis]) . '",', $taskArr[$nis]);
                        $taskArr[$nis] = preg_replace('/end: "(.*)"/', 'end: "' . max($allDatesEnd[$nis]) . '"', $taskArr[$nis]);
                    }
                }
            }
            //print_r($taskArr);

            /*$taskArr = [];
            $subTasks = [];
            $cal_events = [];
            $cal_resource = [];
            $allDates = [];
            reset($checks);
            if (is_array($checks) && count($checks) > 0) {
                for ($p = 0; $p < count($checks); $p++) {
                    $insColor = [];
                    foreach ($checks[$p] as $ch) {
                        if (strlen(trim($ch['periods_hidden'])) > 0) {
                            $dates = $date->getDatesFromMonths(json_decode($ch['periods_hidden']), $planYear[$p]);
                            $taskArr[] = '{
                                id: "' . $plan->uid . '_' . $ch['institutions'] . '",
                                name: "' . htmlspecialchars($ins['result'][$ch['institutions']]->short) . '",
                                start: "' . $dates['start'] . '",
                                end: "' . $dates['end'] . '"
                                }';
                            $cal_resource[] = "{
                              id: '" . $ch['institutions'] . "',
                              title: '" . htmlspecialchars($ins['result'][$ch['institutions']]->short) . "'
                            }";

                        }
                    }

                    $tasks = $db->getRegistry('checkstaff', " WHERE check_uid = '" . $planUid[$p] . "'");

                    if (count($tasks) > 0) {
                        $subTasks = [];
                        foreach ($tasks['result'] as $task) {
                            $dateArr = explode(' - ', $task->dates);
                            $allDates[] = $dateArr[0];

                            $insId = $ins['result'][$task->institution]->id;
                            if (!isset($insColor[$insId])) {
                                $insColor[$insId] = $gui->generateDarkHslHexColor();
                            }
                            $insName = htmlspecialchars($ins['result'][$task->institution]->short);
                            $name = $insName . '  <br>Проверяющий: ' . trim($users['array'][$task->user][0])
                                . ' ' . trim($users['array'][$task->user][1]) . ' ' . trim($users['array'][$task->user][2]);
                            $executor = 'Проверяющий: ' . trim($users['array'][$task->user][0])
                                . ' ' . trim($users['array'][$task->user][1]) . ' ' . trim($users['array'][$task->user][2]);
                            $subTasks[] = '{
                                id: "' . $plan->uid . '_' . $task->id . '",
                                name: "' . $name . '",
                                start: "' . $dateArr[0] . '",
                                end: "' . $dateArr[1] . '",
                                dependencies : "' . $plan->uid . '_' . $task->institution . '",
                                custom_class : "child_task"
                            }';

                            $cal_events[] = "{
                                id: '" . $plan->uid . '_' . $task->id . "',
                                title: '$executor',
                                start: '" . $dateArr[0] . "',
                                end: '" . $dateArr[1] . "',
                                group: '$insName',
                                resourceId: '" . $insId . "',
                                color: '$insColor[$insId]'
                            }";
                        }
                    }
                }
            }*/
            ?>
            </tbody>
        </table>
    </form>
    <?
    echo $gui->paging();
    ?>
    <style>
        :root {
            --fc-small-font-size: .85em;
            --fc-page-bg-color: #fff;
            --fc-neutral-bg-color: rgba(208, 208, 208, 0.3);
            --fc-neutral-text-color: #808080;
            --fc-border-color: #ddd;

            --fc-button-text-color: #fff;
            --fc-button-bg-color: var(--color_03);
            --fc-button-border-color: var(--color_03);
            --fc-button-hover-bg-color: var(--color_04);
            --fc-button-hover-border-color: var(--color_04);
            --fc-button-active-bg-color: var(--color_04);
            --fc-button-active-border-color: var(--color_04);

            --fc-event-bg-color: #3788d8;
            --fc-event-border-color: #3788d8;
            --fc-event-text-color: #fff;
            --fc-event-selected-overlay-color: rgba(0, 0, 0, 0.25);

            --fc-more-link-bg-color: #d0d0d0;
            --fc-more-link-text-color: inherit;

            --fc-event-resizer-thickness: 8px;
            --fc-event-resizer-dot-total-width: 8px;
            --fc-event-resizer-dot-border-width: 1px;

            --fc-non-business-color: rgba(215, 215, 215, 0.3);
            --fc-bg-event-color: rgb(143, 223, 130);
            --fc-bg-event-opacity: 0.3;
            --fc-highlight-color: rgba(188, 232, 241, 0.3);
            --fc-today-bg-color: rgba(255, 220, 40, 0.15);
            --fc-now-indicator-color: red;
        }

        .fc-timeline-event {
            padding: 2px 10px;
        }

        .fc-datagrid-cell {
            cursor: pointer;
            color: var(--color_03);
        }

        .fc-datagrid-cell:hover {
            text-decoration: underline;
        }
    </style>

    <div id="calendar"
         class='scroll_current'<?= $_COOKIE['calendar_view'] == 'calendar' ? '' : ' style="display: none"' ?>></div>


    <style>
        .gantt .bar-wrapper .bar {
            fill: #d1eff5;
        }

        .gantt .bar-wrapper.child_task .bar {
            fill: #d1f5d3;
        }

        ::-webkit-scrollbar {
            width: 0.5rem;
            height: 1.2rem;
        }

    </style>

    <div id='gantt'
         style="width:100%; height: 85vh;<?= $_COOKIE['calendar_view'] == 'gantt' ? '' : ' display: none;' ?>"
         class="scroll_current"></div>
</div>

<script>
    var calendarGrid, gantt, tasks;
    $(document).ready(function () {
        calendarGrid = new FullCalendar.Calendar(document.getElementById('calendar'), {
            locale: 'ru',
            navLinks: true,
            selectable: true,
            selectMirror: true,
            editable: true,
            dayMaxEvents: true,
            nowIndicator: true,
            firstDay: 1,
            initialView: 'resourceTimelineYear',//resourceDayGridMonth dayGridMonth
            <?=(is_array($allDates) && count($allDates) > 0) ? 'initialDate: "' . min($allDates) . '",' : ''?>
            stickyHeaderDates: true,
            //multiMonthMaxColumns: 1,
            schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
            headerToolbar: {
                left: 'prevYear,prev,next,nextYear today',
                center: 'title',
                right: 'resourceTimelineYear,multiMonthYear,dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            buttonText: {
                resourceTimelineYear: 'По учреждениям'
            },
            <?php
            if(intval($perms['edit']) == 1){
            ?>
            select: function (arg) {
                el_app.dialog_open("registry_create", {in_calendar: 1, startDate: arg.startStr, endDate: arg.endStr});
                calendarGrid.unselect()
            },
            <?php
            }
            ?>
            eventClick: function (arg) {
                let taskId = arg.el.fcSeg.eventRange.def.publicId;
                if (typeof taskId != "undefined") {
                    el_app.dialog_open('view_staff', {taskId: taskId, in_calendar: 1});
                }
            },
            height: 'auto',
            resources: [
                <? echo implode(",\n", $cal_resource) ?>
            ],
            events: [
                <? echo implode(",\n", $cal_events) ?>
            ],
            /*eventRender: function(event, element) {
                element.append('<span class="event-group">' + event.group + '</span>');
            },*/
            eventDidMount: function (arg) {
                if (arg.event.extendedProps.org === 'Организация A') {
                    arg.el.style.borderLeft = '4px solid red';
                }
            },
            eventContent: function (arg) {
                return {
                    html: `<div class="event-org">${arg.event.extendedProps.group}</div>` +
                        `<div>${arg.event.title}</div>`
                };
            }
        });
        calendarGrid.render();

        $('#calendar [title]').tipsy({
            arrowWidth: 10,
            cls: null,
            duration: 150,
            offset: 16,
            position: 'right'
        });


        /*let ec = EventCalendar.create(document.getElementById('calendar'), {
            view: 'timeGridWeek',
            events: [
                {
                    title: 'Событие 1',
                    date: '2023-10-01',
                    org: 'Организация A'
                }
            ],
            eventRenderer: function(event) {
                return {
                    html: `<div class="event-org-${event.org}">${event.title}</div>`
                };
            }
        });*/

        tasks = [
            <?

            /*reset($checks);
            if(is_array($checks) && count($checks) > 0) {
                for ($p = 0; $p < count($checks); $p++) {
                    foreach ($checks[$p] as $ch) {
                        if (strlen(trim($ch['periods_hidden'])) > 0) {
                            $dates = $date->getDatesFromMonths(json_decode($ch['periods_hidden']), $planYear[$p]);
                            $taskArr[] = '{
                                id: "' . $plan->uid . '_' . $ch['institutions'] . '",
                                name: "' . htmlspecialchars($ins['result'][$ch['institutions']]->short) . '",
                                start: "' . $dates['start'] . '",
                                end: "' . $dates['end'] . '"
                                }';
                        }
                    }

                    echo implode(",\n", $taskArr) . ",\n";


                    $tasks = $db->getRegistry('checkstaff', " WHERE check_uid = '" . $planUid[$p] . "'");
                    if (count($tasks) > 0) {
                        $subTasks = [];//print_r($tasks['result']);
                        foreach ($tasks['result'] as $task) { //print_r($task);
                            $dateArr = explode(' - ', $task->dates);
                            $subTasks[] = '{
                                    id: "' . $plan->uid . '_' . $task->id . '",
                                    name: "' . htmlspecialchars($ins['result'][$task->institution]->short) . '  <br>Проверяющий: ' . trim($users['array'][$task->user][0])
                                . ' ' . trim($users['array'][$task->user][1]) . ' ' . trim($users['array'][$task->user][2]) . '",
                                    start: "' . $dateArr[0] . '",
                                    end: "' . $dateArr[1] . '",
                                    dependencies : "' . $plan->uid . '_' . $task->institution . '",
                                    custom_class : "child_task"
                                }';
                        }
                        echo implode(",\n", $subTasks);
                    }
                }
            }*/
            echo implode(",\n", $taskArr) . ",\n";
            echo implode(",\n", $subTasks);
            ?>
        ]
        gantt = new Gantt('#gantt', tasks, {
            language: "ru",
            view_mode: "Day",
            view_mode_select: false,
            //holidays: 'weekend',
            container_height: "auto",
            auto_move_label: true,
            showProgress: false,
            show_expected_progress: false,
            popup_on: "hover",
            date_format: "DD.MM.YYYY",
            readonly_dates: true,
            scroll_to: "start",
            popup: (ctx) => {
                ctx.set_title(ctx.task.name);
                if (ctx.task.description) ctx.set_subtitle(ctx.task.description);
                else ctx.set_subtitle('');
                let ds = new Date(ctx.task._start);
                let start_date = ds.toLocaleDateString('ru-RU');
                let de = new Date(ctx.task._end);
                let end_date = de.toLocaleDateString('ru-RU');
                ctx.set_details(
                    `${start_date} - ${end_date} (${ctx.task.actual_duration} дней${ctx.task.ignored_duration ? ' + ' + ctx.task.ignored_duration + ' excluded' : ''})`,
                );
            }
            /*custom_popup_html: function(tasks) {
                return '<div class="details-container">' +
                    '<h5>${process.identify}</h5>' +
                    '<p>Process began on: ${process._start.getDate()}</p>' +
                    '<p>Anticipated to complete by ${process._start.getDate()}</p>' +
                    '<p>${process.progress}% accomplished!</p>' +
                    '</div>';
            }*/
        });
        $("#gantt").on('click', function (e) {
            let selfClass = $(e.target).attr('class'),
                $bar = $(e.target).closest('.bar-wrapper'),
                plan_id = el_tools.getUrlVar(document.location.href)
            taskId = 0,
                insId = 0;
            if (selfClass === "bar" || selfClass === 'bar-label' || selfClass === "bar-label big") {
                if ($bar.hasClass("child_task")) {
                    taskId = $bar.data('id');
                    el_app.dialog_open('view_staff', {insId, taskId}, 'calendar');
                } else {
                    insId = $bar.data('id');
                    el_app.dialog_open('ins_info', {plan_id: plan_id.id, ins_id: insId}, 'calendar');
                }


            }
        });

        $(".gantt-container").css("height", $("#gantt").css("height"));
        console.log($('#gantt').css('height'));

        $('.circle-plus').on('click', function () {
            let $self = $(this),
                parent = $self.closest("tr").data("id");
            if ($self.hasClass("opened")) {
                $self.removeClass('opened');
                $("[data-parent=" + parent + "]").slideUp();
                $self.attr("title", "Показать подзадачи");
            } else {
                $self.addClass('opened');
                $('[data-parent=' + parent + ']').slideDown();
                $self.attr('title', 'Скрыть подзадачи');
            }
            el_tools.setcookie('openTask' + parent, $self.hasClass('opened'));
        });

        $(".new_order").off("click").on("click", function (e) {
            e.preventDefault();
            let plan_id = el_tools.getUrlVar(document.location.href),
                ins_id = $(this).closest("tr").data("ins");
            el_app.dialog_open('order_staff', {plan_id: plan_id.id, ins_id: ins_id}, 'calendar');
        });

        $(".assign_staff_btn").off("click").on("click", function (e) {
            e.preventDefault();
            let $row = $(this).closest("tr"),
                ins_id = $row.data("ins"),
                uid = $(this).data("uid");
            el_app.dialog_open('assign_staff', {insId: uid + '_' + ins_id}, 'calendar');
        });

        $(".fc-datagrid-cell").off("click").on("click", function () {
            let plan_id = el_tools.getUrlVar(document.location.href),
                ins_id = $(this).data("resource-id");
            el_app.dialog_open('ins_info', {plan_id: plan_id.id, ins_id: ins_id}, 'calendar');
        });

        $('#tbl_registry_items .insName').off('click').on('click', function () {
            let plan_id = el_tools.getUrlVar(document.location.href),
                ins_id = $(this).closest("tr").data('ins');
            el_app.dialog_open('ins_info', {plan_id: plan_id.id, ins_id: ins_id}, 'calendar');
        });
        <?php
        $open_dialog = 0;
        if (isset($_POST['params'])) {
            $postArr = explode('=', $_POST['params']);
            if ($postArr[0] == 'open_dialog') {
                $open_dialog = intval($postArr[1]);
            }
        } elseif (isset($_GET['open_dialog']) && intval($_GET['open_dialog']) > 0) {
            $open_dialog = intval($_GET['open_dialog']);
        }
        if ($open_dialog > 0) {
            echo 'el_app.dialog_open("view_task", {"taskId": ' . $open_dialog . ', view_result: 0}, "calendar");';
        }
        ?>
    });
</script>
<script src="/modules/calendar/js/registry_items.js?v=<?= $gui->genpass() ?>"></script>