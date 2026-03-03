<?
use Core\Gui;
use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$gui = new Gui;
$module_props = $gui->getModuleProps('roles');
$roles = $gui->getTableData('roles');

$db = new Db;
$mods = $db->getRegistry('modules', ' order by id');
$modules = $mods['result'];
?>
<style>
    .scroll_wrap {
        scrollbar-width: auto;
        overflow: auto;
    }
</style>
<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
            'title' => 'Роли',
            'renew' => 'Сбросить все фильтры',
            'create' => 'Новая роль',
            //'clone' => 'Копия роли',
            'delete' => 'Удалить выделенные',
            'logout' => 'Выйти'
        ]);
        ?>
    </div>

</div>
<div class="scroll_wrap">
    <form method="post" id="role_delete" class="ajaxFrm">
        <table class="table_data" id="tbl_role">
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
							'roles',
							'№',
							'id',
							'el_data',
							array()
					);
					?>
				</th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'roles',
                        'Статус',
                        'active',
                        'constant',
                        array('0' => 'Активный', '1' => 'Заблокирован')
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'roles',
                        'Наименование',
                        'name',
                        'el_data',
                        array()
                    );
                    ?>
                </th>

                <?
                foreach ($modules as $module) {
                    echo '<th>
                            <div class="head_sort_filter">
                                ' . $module->name . '
                            </div>
                        </th>' . "\n";
                }
                ?>
                <th>
                    <div class="head_sort_filter">Примечания

                    </div>
                </th>
            </tr>
            </thead>


            <tbody>
            <!-- row -->
            <?
            //Выводим созданные роли
            foreach ($roles as $role) {
                echo '<tr data-id="' . $role->id . '">
                        <td>
                            <div class="custom_checkbox">
                                <label class="container"><input type="checkbox" name="role_id[]" value="' . $role->id . '">
                                <span class="checkmark"></span></label>
                            </div>
                        </td>
                        <td>' . $role->id . '</td>
                        <td class="status">
                        <span class="material-icons">' . (($role->active == 1) ? 'task_alt' : 'radio_button_unchecked') . '</span></td>
                        <td>' . $role->name . '</td>';
                //Выводим в цикле права для каждого модуля у текущей роли
                reset($modules);
                foreach ($modules as $module) {
                    $perms = json_decode($role->permissions, true);
                    $p = $perms[$module->id];
                    echo '<td>
                            <div class="icon_role">
                                        <span class="material-icons' . (($p['view']) ? ' enable' : '') . '">
                                            visibility
                                        </span>
                                <span class="material-icons' . (($p['edit']) ? ' enable' : '') . '">
                                            edit
                                        </span>
                                <span class="material-icons' . (($p['delete']) ? ' enable' : '') . '">
                                            delete
                                        </span>
                            </div>
                        </td>';
                }
                echo '
                        <td>' . $role->comment . '</td>
                    </tr>';
            }
            ?>
            </tbody>
        </table>
    </form>
</div>
<script src="/modules/roles/js/roles.js"></script>