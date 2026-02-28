<?php

use Core\Gui;
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

/*if (isset($_GET['id']) && intval($_GET['id']) > 0 && !isset($_POST['params'])) {
	$regId = intval($_GET['id']);
} else {
	parse_str($_POST['params'], $paramArr);
	foreach ($paramArr as $name => $value) {
		$_GET[$name] = $value;
	}
	$regId = intval($_GET['id']);
	$_GET['url'] = $_POST['url'];
}*/
$regId = 40;

$gui = new Gui;
$db = new Db;
$auth = new Auth();

$table = $db->selectOne('registry', ' where id = ?', [$regId]);
$parent_item = $db->selectOne('users', 'where parent=' . $regId . ' LIMIT 1');
$roles = $db->getRegistry('roles');
$parents = $db->getRegistry('registry');
$institution = $db->getRegistry('institutions');
$ministries = $db->getRegistry('ministries');
$units = $db->getRegistry('units');
$items = $db->getRegistry($table->table_name);

$subQuery = '';

$gui->set('module_id', 7);


$regs = $gui->getTableData($table->table_name);
?>
<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
                'title' => 'Пользователи',
                //'registryList' => '',
                'renew' => 'Сбросить все фильтры',
                'create' => 'Новый пользователь',
                //'clone' => 'Копия записи',
                'delete' => 'Удалить пользователя',
                'filter_panel' => 'Открыть панель фильтров',
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
        <input type="hidden" name="registry_id" id="registry_id" value="<?= $regId ?>">
        <table class="table_data" id="tbl_registry_items">
            <thead>
            <tr class="fixed_thead">
                <th>
                    <div class="custom_checkbox">
                        <label class="container" title="Выделить все">
                            <input type="checkbox" id="check_all"><span class="checkmark"></span>
                        </label>
                    </div>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'users',
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
                        'users',
                        'Статус',
                        'active',
                        'constant',
                        ['1' => 'Активный', '0' => 'Заблокирован']
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'users',
                        'Роль',
                        'roles',
                        'constant',
                        $items['array']
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'users',
                        'Ф.И.О.',
                        'surname',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class='sort'>
                    <?
                    echo $gui->buildSortFilter(
                        'users',
                        'Учреждение',
                        'institution',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class='sort'>
                    <?
                    echo $gui->buildSortFilter(
                        'users',
                        'Управление',
                        'ministries',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class='sort'>
                    <?
                    echo $gui->buildSortFilter(
                        'users',
                        'Отдел',
                        'division',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class='sort'>
                    <?
                    echo $gui->buildSortFilter(
                        'users',
                        'Должность',
                        'position',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th>
                    <div class="head_sort_filter">Примечания</div>
                </th>
            </tr>
            </thead>


            <tbody>
            <!-- row -->
            <?
            //Выводим созданные роли
            $tab = 10;
            foreach ($regs as $reg) {
                if ($regId == 14 && ($auth->haveUserRole(3) || $auth->haveUserRole(1))) {
                    $reg = (object)$reg;
                }
                $itemArr = explode(',', $reg->parent_items);
                $itemList = [];
                $itemStr = '';
                $aCount = $reg->ext_answers;
                $tab++;
                foreach ($itemArr as $i) {
                    $itemList[] = $items['array'][$i];
                }
                if (count($itemList) > 0 && strlen($itemList[0]) > 0) {
                    $itemStr = ' - ' . implode(', ', $itemList);
                }

                $user_roles_array = json_decode($reg->roles);
                $user_roles_arr = [];
                if(is_array($user_roles_array) && count($user_roles_array) > 0) {
                    foreach ($user_roles_array as $ua) {
                        $user_roles_arr[] = $roles['array'][$ua];
                    }
                }

                echo '<tr data-id="' . $reg->id . '" data-parent="' . $regId . '" tabindex="0">
                    <td>
                        <div class="custom_checkbox">
                            <label class="container"><input type="checkbox" name="reg_id[]" tabindex="-1" value="' . $reg->id . '">
                            <span class="checkmark"></span></label>
                        </div>
                    </td>
                    <td>' . $reg->id . '</td>
                    <td class="status">
                    <span class="material-icons">' . (($reg->active == 1) ? 'task_alt' : 'radio_button_unchecked') . '</span></td>
                    <td>' . implode(';<br>', $user_roles_arr) . '</td>
                    <td class="group">' . trim($reg->surname) . ' ' . trim($reg->name) . ' ' . trim($reg->middle_name) . '</td>
                    <td>' . $institution['result'][$reg->institution]->short . '</td>
                    <td>' . $ministries['array'][$reg->ministries] . '</td>
                    <td>' . $units['array'][$reg->division] . '</td>
                    <td>' . $reg->position . '</td>
                    <td>' . $reg->comment . '</td>
                </tr>';
            }
            ?>
            </tbody>
        </table>
    </form>
    <?
    echo $gui->paging();
    ?>
</div>
<script src='/modules/users/js/registry.js?v=<?= $gui->genpass() ?>'></script>
<script src="/modules/users/js/registry_items.js?v=<?= $gui->genpass() ?>"></script>
<script src='/modules/users/js/users.js?v=<?= $gui->genpass() ?>'></script>