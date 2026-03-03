<?php

use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Date;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$gui = new Gui;
$db = new Db;
$auth = new Auth();
$date = new Date();

$planId = 0;


/*
 * 8 - Сотрудник ОУСР - видит только себя
 * 7 - Руководитель ОУСР - видит только свой ОУСР
 * 6 - Оператор объекта контроля - видит только свой объект
 * 5 - Руководитель объекта контроля - видит только свой объект
 * 4 - Оператор министерства - видит только себя
 * 3 - Руководитель подразделения министерства - видит только свое подразделение
 * 2 - Руководитель министерства - видит все
 * 1 - Администратор - видит все
 * */

//Смотрим всегда текущий план
$plan = $db->select('checksplans', ' WHERE active = 1');// ORDER BY year ASC LIMIT 1

$ins = $db->getRegistry('institutions', '', [], ['short']); //print_r($ins['array']);
$units = $db->getRegistry('units');
$users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name']);
$persons = $db->getRegistry('persons', '', [], ['surname', 'first_name', 'middle_name', 'birth']);
$insp = $db->getRegistry('inspections');
$tasks_templates = $db->getRegistry('tasks');
$taskArr = [];
foreach ($plan as $pl) {
    $planUid = $pl->uid;
    $planId = $pl->id;
    $tasks = json_decode($pl->addinstitution, true);

    foreach ($tasks as $ch) {
        $ch['planUid'] = $planUid;
        $taskArr[] = $ch;
    }
}
$checks[0] = $taskArr;
//echo '<pre>';print_r($checks);echo '</pre>';
$null_checks = $db->select('checkstaff', " WHERE check_uid = '0'"); //print_r($null_checks);
$n = count($taskArr);
$null_che = [];
foreach ($null_checks as $che) {

    $checks[0][$n]['institutions'] = intval($che->institution);
    $checks[0][$n]['object_type'] = $che->object_type;
    $checks[0][$n]['planUid'] = $che->check_uid;
    $planUid[$n] = '0';
    $n++;
}

$gui->set('module_id', 15);
?>
<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
                'title' => 'Назначенные задачи',
                //'planList' => 'Выбор плана',
                'renew' => 'Обновить список задач',
                //'switch_plan' => 'Показать',
                'create' => 'Создать задание',
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
    <form method='post' id='registry_items_delete' class='ajaxFrm'>
        <input type='hidden' name='registry_id' id='registry_id' value="<?= $planId ?>">
        <table class='table_data' id='tbl_registry_items'>
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
                <th class='sort'>
                    <?
                    echo $gui->buildSortFilter(
                        'registryitems',
                        'Статус',
                        'active',
                        'constant',
                        ['1' => 'Ожидает выполнения', '0' => 'Выполнена']
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
            $permissionQueryArr = [];
            $permissionQuery = '';
            $taskIds = [];

            if (intval($_SESSION['user_ousr']) > 0 && intval($_SESSION['user_roles']) == 7) {
                //Руководитель ОУСР
                $permissionQueryArr[] = ' ousr = ' . intval($_SESSION['user_ousr']);
            }
            if (intval($_SESSION['user_division']) > 0 && intval($_SESSION['user_roles']) == 3) {
                //Руководитель подразделения министерства - выбираем всех сотрудников подразделения
                $staff = $db->select('users', ' WHERE division = ' . intval($_SESSION['user_division']));
                if (count($staff) > 0) {
                    $div_users = [];
                    foreach ($staff as $id => $s) {
                        $div_users[] = $s->id;
                    }
                    $permissionQueryArr[] = ' user IN (' . implode(', ', $div_users) . ')';
                }
            }
            if(in_array(intval($_SESSION['user_roles']), [4, 8])){
                //Оператор министерства или ОУСР
                $permissionQueryArr[] = ' user = ' . intval($_SESSION['user_id']);
            }

            $permissionQuery = count($permissionQueryArr) > 0 ? ' AND ' . implode(' AND ', $permissionQueryArr) : '';
            //echo $permissionQuery;


            $check_number = 1;
            if (is_array($checks) && count($checks) > 0) {
                for ($c = 0; $c < count($checks); $c++) {
                    foreach ($checks[$c] as $ch) {

                        $tasks = $db->getRegistry('checkstaff', " WHERE check_uid = '" . $ch['planUid'] . "' 
                        AND institution = '" . $ch['institutions'] . "'$permissionQuery"
                        );
                        //echo '<pre>';/*print_r($tasks);*/echo " WHERE check_uid = '" . $ch['planUid'] . "'
                        //AND institution = '" . $ch['institutions'] . "'$permissionQuery";echo '</pre><hr>';

                        if (count($tasks) > 0) {
                            $task_number = 0;
                            foreach ($tasks['result'] as $task) {
                                if (!in_array($task->id, $taskIds)) { //исключаем повторения
                                    $dateArr = explode(' - ', $task->dates);
                                    $executorFio = trim($users['array'][$task->user][0])
                                        . ' ' . trim($users['array'][$task->user][1]) . ' ' . trim($users['array'][$task->user][2]);

                                    $object = $task->object_type == 1 ? stripslashes(htmlspecialchars($ins['result'][$ch['institutions']]->short)) . ' ' .
                                        stripslashes(htmlspecialchars($units['array'][$ch['units']])) :
                                        stripslashes(htmlspecialchars($persons['array'][$ch['institutions']][0])) . ' ' .
                                        stripslashes(htmlspecialchars($persons['array'][$ch['institutions']][1])) . ' ' .
                                        stripslashes(htmlspecialchars($persons['array'][$ch['institutions']][2])) . ' ' .
                                        (strlen(trim($persons['array'][$ch['institutions']][3])) > 0 ?
                                            $date->correctDateFormatFromMysql($persons['array'][$ch['institutions']][3]) : '');

                                    if ($task->done == 1) {
                                        $icon = 'task_alt';
                                        $status = 'Выполнена';
                                        $class = 'green';
                                    } else {
                                        $icon = 'radio_button_unchecked';
                                        $status = 'Ожидает выполнения';
                                        $class = 'grey';
                                    }


                                    echo '
                            <tr data-id="' . $check_number . '" tabindex="0" class="noclick">
                                <td>' . $check_number . '</td>
                                <td class="status ' . $class . '"><span class="material-icons ' . $class . '">' . $icon . '</span> ' . $status . '</td>
                                <td>' . $object .
                                        '</td>
                                <td>' . $executorFio . '</td>
                                <td>с ' . $date->correctDateFormatFromMysql($dateArr[0]) . ' по ' . $date->correctDateFormatFromMysql($dateArr[1]) . '</td>
                                <td>Назначена</td>
                                <td>' . $tasks_templates['array'][$task->task_id] . '</td>
                                <td class="link">
                                    <span class="material-icons view_task" data-id="' . $task->id . '" title="Просмотр задачи">pageview</span>
                                </td>
                            </tr>';
                                    $task_number++;
                                    $check_number++;
                                }
                                $taskIds[] = $task->id;
                            }

                        }

                    }
                }
            }
            ?>
            </tbody>
        </table>
    </form>
    <?
    echo $gui->paging();
    ?>

</div>
<script>
    <?php
    $open_dialog = 0;
    if(isset($_POST['params'])){
        $postArr = explode('=', $_POST['params']);
        if($postArr[0] == 'open_dialog'){
            $open_dialog = intval($postArr[1]);
        }
    }elseif(isset($_GET['open_dialog']) && intval($_GET['open_dialog']) > 0){
        $open_dialog = intval($_GET['open_dialog']);
    }
    if($open_dialog > 0){
        echo 'el_app.dialog_open("view_task", {"taskId": '.$open_dialog.', view_result: 0}, "calendar");';
    }
        ?>
</script>
<script src="/modules/assigned/js/registry_items.js?v=<?= $gui->genpass() ?>"></script>