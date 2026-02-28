<?php

use Core\Gui;
use Core\Db;
use Core\Auth;

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


$plan = $db->selectOne('checksplans', ' where id = ?', [$planId]);
$ins = $db->getRegistry('institutions');
$units = $db->getRegistry('units');
$users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name']);

$checks = json_decode($plan->addinstitution, true);
$gui->set('module_id', 14);

?>
<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
            'title' => 'План на '.$plan->year.' год',
            'renew' => 'Сбросить все фильтры',
            'create' => 'Новая запись',
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
    <form method="post" id="registry_items_delete" class="ajaxFrm">
        <input type="hidden" name="registry_id" id="registry_id" value="<?= $planId ?>">
        <table class="table_data" id="tbl_registry_items">
            <thead>
            <tr class="fixed_thead">
                <th class="sort">
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
                        'Предмет проверки',
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
                        'Период проверки',
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
                        'Проверяемый период',
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
            $check_number = 1;
            foreach ($checks as $ch) {

                echo '<tr data-id="' . $check_number . '" tabindex="0" class="noclick">
                    <td>' . $check_number . '</td>
                    <td>' . stripslashes(htmlspecialchars($ins['array'][$ch['institutions']])) .
                    stripslashes(htmlspecialchars($units['array'][$ch['units']])) .
                    '</td>
                    <td class="group">' . stripslashes($insp['array'][$ch['inspections']]) . '</td>
                    <td>'.$ch['periods'].'</td>
                    <td>'.$ch['check_periods'].'</td>
                    <td class="link"><span class="material-icons assign" title="Назначить исполнителей и сроки"
                    data-uid="'.$plan->uid.'" 
                    data-ins="'.$ch['institutions'].'">assignment_ind</span></td>
                </tr>';
                $check_number++;
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
<script>

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
</script>
<script src="/modules/plans/js/registry_items.js?v=<?= $gui->genpass() ?>"></script>