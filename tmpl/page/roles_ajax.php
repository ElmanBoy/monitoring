<?
require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';

$sortQuery = ' ORDER BY id DESC';
$sortFields = array();
$filterQuery = '';
$filterSlots = array();

if(isset($_POST['params'])){
    parse_str($_POST['params'], $out);
    $_GET['sort'] = $out['sort'];
    $_GET['filter'] = $out['filter'];
}

if(isset($_GET['sort'])){
    $sortArr = explode(':', $_GET['sort']);
    $sortQueryArr = array();

    for($i = 0; $i < count($sortArr); $i++) {
        $direction = 'ASC';
        if (substr_count($sortArr[$i], '_r') > 0) {
            $direction = 'DESC';
            $sortArr[$i] = str_replace('_r', '', $sortArr[$i]);
            $sortFields[$sortArr[$i]]['arrow'] = 'north';
            $sortFields[$sortArr[$i]]['value'] = $sortArr[$i];
        }else{
            $sortFields[$sortArr[$i]]['arrow'] = 'south';
            $sortFields[$sortArr[$i]]['value'] = $sortArr[$i].'_r';
        }
        $sortQueryArr[] = $sortArr[$i].' '.$direction;
    }
    if(count($sortQueryArr) > 0){
        $sortQuery = ' ORDER BY '.implode(', ', $sortQueryArr);
    }
}

if(isset($_GET['filter'])){
    $filterArr = explode(';', $_GET['filter']);

    $filterSection = array();
    $filterFields = array();

    //Листаем фильтруемые поля
    for($f = 0; $f < count($filterArr); $f++){
        //Листаем фильтруемые значения
        $filterValuesArr = explode(':', $filterArr[$f]);
        $filterValues = explode('|', $filterValuesArr[1]);
        $filterQueryArr = array();
        for($v = 0; $v < count($filterValues); $v++) {
            $filterQueryArr[] = $filterValuesArr[0]." = ?";
            $filterSlots[] = $filterValues[$v];
            $filterFields[$filterValuesArr[0]][] = $filterValues[$v];
        }
        $filterSection[] = '('.implode(' OR ', $filterQueryArr).')';
    }
    $filterQuery = implode(' AND ', $filterSection);
}
//print_r($filterFields);
//echo $filterQuery; print_r($filterSlots);
$modules = R::findAll(TBL_PREFIX.'modules');
$roles = R::find(TBL_PREFIX.'roles', $filterQuery.$sortQuery, $filterSlots);
?>
            <div class="nav">
                <div class="nav_01">
                    <div class="title">Роли</div>
                    <div class="button icon" id="button_role_refresh" title="Сбросить все фильтры">
                        <span class="material-icons">autorenew</span></div>
                    <div class="button icon text" id="button_role_create" title="Новая роль">
                        <span class="material-icons">control_point</span>Создать
                    </div>
                    <div class="button icon text disabled group_action" id="button_role_clone" title="Копия роли">
                        <span class="material-icons">control_point_duplicate</span>Дублировать
                    </div>
                    <div class="button icon text disabled group_action" id="button_role_delete" title="Удалить выделенные">
                        <span class="material-icons">delete_forever</span>Удалить
                    </div>
                    <div class="button icon right" title="Выйти">
                        <span class="material-icons" id="logout">logout</span>
                    </div>
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
                            $filter_active_selected = (is_array($filterFields['active']) && count($filterFields['active']) > 0);
                            ?>
                            <div class="head_sort_filter">
                                <div class="button icon text sorter" title="Сортировать"
                                     data-field="<?=(is_array($sortFields['active'])) ? $sortFields['active']['value'] : 'active'?>">
                                    Статус<span class="material-icons"><?=(is_array($sortFields['active'])) ? $sortFields['active']['arrow'] : 'north'?></span></div>
                                <div class="button icon filterer<?=($filter_active_selected) ? ' active' : ''?>" title="Фильтр">
                                    <span class="material-icons">filter_alt</span></div>
                            </div>
                            <div class="data_filter_select constant" style="display:<?=($filter_active_selected && $_COOKIE['role_show_filter_active'] == 'open') ? 'block' : 'none'?>">
                                <div class="el_suggest_list bottom">
                                <div class="el_option"><label class="container">Активный
                                        <input type="checkbox"<?=(is_array($filterFields['active']) && in_array('1', $filterFields['active'])) ? ' checked' : ''?>
                                         name="filter_active[]" value="1" class="filterer">
                                        <span class="checkmark"></span></label></div>
                                <div class="el_option"><label class="container">Заблокирован
                                        <input type="checkbox"<?=(is_array($filterFields['active']) && in_array('0', $filterFields['active'])) ? ' checked' : ''?>
                                         name="filter_active[]" value="0" class="filterer">
                                        <span class="checkmark"></span></label></div>
                                </div>
                            </div>
                        </th>
                        <th class="sort">
                            <?
                            $filter_name_selected = (is_array($filterFields['name']) && count($filterFields['name']) > 0);
                            ?>
                            <div class="head_sort_filter">
                                <div class="button icon text sorter" title="Сортировать"
                                     data-field="<?=(is_array($sortFields['name'])) ? $sortFields['name']['value'] : 'name'?>">
                                    Наименование<span class="material-icons"><?=(is_array($sortFields['name'])) ? $sortFields['name']['arrow'] : 'north'?></span></div>
                                <div class="button icon filterer<?=($filter_name_selected) ? ' active' : ''?>" title="Фильтр">
                                    <span class="material-icons">filter_alt</span></div>
                            </div>
                            <div class="data_filter_select el_data" style="display:<?=($filter_active_selected &&
                                $_COOKIE['role_show_filter_name'] == 'open') ? 'block' : 'none'?>">
                                <input type="text" class="el_input el_suggest" autocomplete="off"
                                       data-src='{"source": "roles", "value": "name", "column": "name"}'
                                       multiple name="filter_name[]" placeholder="Начните вводить...">
                                <?
                                if(is_array($filterFields['name']) && count($filterFields['name']) > 0){
                                    echo '<div class="el_suggest_list bottom">
                                    <div class="el_multi_bar" style="">
                                    <div class="button icon uncheck_all"><span class="material-icons">remove_done</span></div>
                                    <div class="button icon done close_select"><span class="material-icons">highlight_off</span></div>
                                    </div>';
                                    $filterFields['name'] = array_unique($filterFields['name']);
                                    foreach($filterFields['name'] as $fItem){
                                        echo '<div class="el_option" data-value="'.$fItem.'"><label class="container">
                                        '.$fItem.'<input type="checkbox" name="filter_name[]" value="'.$fItem.'" checked>
                                        <span class="checkmark"></span></label></div>';
                                    }
                                    echo '</div>';
                                }

                                ?>

                            </div>
                        </th>

                        <?
                        foreach ($modules as $module){
                            echo '<th>
                            <div class="head_sort_filter">
                                '.$module->name.'
                            </div>
                        </th>'."\n";
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
                    foreach ($roles as $role){
                        echo '<tr data-id="'.$role->id.'">
                        <td>
                            <div class="custom_checkbox">
                                <label class="container"><input type="checkbox" name="role_id[]" value="'.$role->id.'">
                                <span class="checkmark"></span></label>
                            </div>
                        </td>
                        <td class="status">
                        <span class="material-icons">'.(($role->active == 1) ? 'task_alt' : 'radio_button_unchecked').'</span></td>
                        <td>'.$role->name.'</td>';
                        //Выводим в цикле права для каждого модуля у текущей роли
                        reset($modules);
                        foreach ($modules as $module){
                            $perms = json_decode($role->permissions, true);
                            $p = $perms[$module->id];
                            echo '<td>
                            <div class="icon_role">
                                        <span class="material-icons'.(($p['view']) ? ' enable' : '').'">
                                            visibility
                                        </span>
                                <span class="material-icons'.(($p['edit']) ? ' enable' : '').'">
                                            edit
                                        </span>
                                <span class="material-icons'.(($p['delete']) ? ' enable' : '').'">
                                            delete
                                        </span>
                            </div>
                        </td>';
                        }
                        echo '
                        <td>'.$role->comment.'</td>
                    </tr>';
                    }
                    ?>
                    </tbody>
                </table>
                </form>
            </div>
<script src="/modules/roles/js/roles.js"></script>