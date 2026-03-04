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
    $planId = intval($_GET['plan']);
    $_GET['url'] = $_POST['url'];
}

$gui = new Gui;
$db = new Db;
$auth = new Auth();
$date = new Date();


$plan = $db->selectOne('checksplans', ' where id = ?', [$planId]);
$ins = $db->getRegistry('institutions');
$units = $db->getRegistry('units');
$insp = $db->getRegistry('inspections');
$users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name']);

$checks = $gui->getTableData('checkinstitutions', " AND plan_uid = '" . $plan->uid . "' AND plan_version = '" . $plan->version . "'");

$check_number = 1 + $gui->currentPageNumber * $gui->rowsLimit;
$row_numbers = [];
if (count($checks) > 0) {
    foreach ($checks as $ch) {
        $row_numbers[$ch->id] = $check_number;
        $check_number++;
    }
}
reset($checks);

//$checks = json_decode($plan->addinstitution, true) ?? [];
$gui->set('module_id', 1);

?>
<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
                'title' => 'План на ' . $plan->year . ' год',
                'renew' => 'Сбросить все фильтры',
                //'create' => 'Новая запись',
                'plans' => 'Планы проверок',
                'filter_panel' => 'Открыть панель фильтров',
                //'switch_plan' => 'Показать',
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
    <ul class='breadcrumb'>
        <li><a href='/plans'>Все планы</a></li>
        <li><a href='/plans?id=<?= $planId ?>'><?= $plan->short ?></a></li>
    </ul>
    <form method="post" id="registry_items_delete" class="ajaxFrm">
        <input type="hidden" name="registry_id" id="registry_id" value="<?= $planId ?>">
        <table class="table_data" id="tbl_registry_items">
            <thead>
            <tr class="fixed_thead">
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'checkinstitutions',
                        '№',
                        'id',
                        'constant',
                        $row_numbers
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'institutions',
                        'Объект проверки',
                        'institution',
                        'el_data',
                        $ins['array'],
                        'suggest',
                        'text',
                        true,
                        'institution',
                        ' AND active = 1',
                        'short'
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'checkinstitutions',
                        'Предмет проверки',
                        'inspections',
                        'constant',
                        $insp['array']
                    );
                    ?>
                </th>
                <th class='sort'>
                    <?
                    echo $gui->buildSortFilter(
                        'checkinstitutions',
                        'Период проверки',
                        'periods',
                        'el_data',
                        [],
                        'date'
                    );
                    ?>
                </th>
                <th class='sort'>
                    <?
                    echo $gui->buildSortFilter(
                        'checkinstitutions',
                        'Проверяемый период',
                        'check_periods_start',
                        'el_data',
                        [],
                        'date'
                    );
                    ?>
                </th>
            </tr>
            </thead>


            <tbody>
            <!-- row -->
            <?

            $check_number = 1 + $gui->currentPageNumber * $gui->rowsLimit;
            if (count($checks) > 0) {
                foreach ($checks as $ch) {

                    echo '<tr data-id="' . $ch->id . '" tabindex="0" class="noclick">
                    <td>' . /*$ch->id*/
                        $row_numbers[$ch->id] . '</td>
                    <td>' . stripslashes(htmlspecialchars($ins['result'][$ch->institution]->short)) .
                        '</td>
                    <td class="group">' . stripslashes($insp['array'][$ch->inspections]) . '</td>
                    <td>' . $ch->periods . '</td>
                    <td>' . $date->correctDateFormatFromMysql($ch->check_periods_start) . ' - ' .
                        $date->correctDateFormatFromMysql($ch->check_periods_end) . '</td>';
                    if ($plan->active == 1) {
                        echo '<td class="link"><span class="material-icons assign" title="Назначить исполнителей и сроки"
                    data-id="' . $plan->uid . '_' . $ch->institution . '">assignment_ind</span></td>';
                    }
                    echo '</tr>';
                    $check_number++;
                }
            }
            ?>
            </tbody>
        </table>
    </form>
    <?
    echo $gui->paging();
    ?>
    <div id='gantt' style="width:100%; display: none; min-height: 500px;"></div>
</div>
<? /*script>

    tasks = [
        <?
        $tasks = $db->getRegistry('checkstaff');
        $taskArr = []; //print_r($ins);
        foreach($tasks['result'] as $task){
            $dateArr = explode(' - ', $task->dates);
            $taskArr[] = '{
                id: "'.$task->id.'",
                name: "'.htmlspecialchars($ins['result'][$task->institution]->short).'  <br>Проверяющий: '.trim($users['array'][$task->user][0])
                .' '.trim($users['array'][$task->user][1]).' '.trim($users['array'][$task->user][2]).'",
                start: "'.$dateArr[0].'",
                end: "'.$dateArr[1].'",
                progress: 20
            }';
        }
        echo implode(",\n", $taskArr);
        ?>
    ]
    gantt = new Gantt('#gantt', tasks, {
        language: "ru",
        view_mode: "Day",
        view_mode_select: false,
        //holidays: 'weekend',
        container_height: 500,
        auto_move_label: true
    });
</script*/ ?>
<script src="/modules/plans/js/registry_items.js?v=<?= $gui->genpass() ?>"></script>