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
$regId = 69;

$gui = new Gui;
$db = new Db;
$auth = new Auth();

$table = $db->selectOne('registry', ' where id = ?', [$regId]);
$parent_item = $db->selectOne('documents', 'where parent=' . $regId . ' LIMIT 1');
$parents = $db->getRegistry('registry');
$items = $db->getRegistry($table->table_name);

$subQuery = '';

$gui->set('module_id', 20);


$regs = $gui->getTableData($table->table_name);
?>
<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
                'title' => 'API',
                //'registryList' => '',
                'renew' => 'Сбросить все фильтры',
                'create' => 'Новый документ',
                //'clone' => 'Копия записи',
                //'delete' => 'Удалить выделенные',
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
                        'documents',
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
                        'documents',
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
                        'documents',
                        'Наименование',
                        'name',
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
                if($reg->status == 1){
                    $icon = 'task_alt';
                    $status = 'Согласован';
                    $class = 'green';
                }else{
                    $icon = 'radio_button_unchecked';
                    $status = 'На согласовании';
                    $class = 'grey';
                }

                echo '<tr data-id="' . $reg->id . '" data-parent="' . $regId . '" tabindex="0">
                    <td>
                        <div class="custom_checkbox">
                            <label class="container"><input type="checkbox" name="reg_id[]" tabindex="-1" value="' . $reg->id . '">
                            <span class="checkmark"></span></label>
                        </div>
                    </td>
                    <td>' . $reg->id . '</td>
                    <td class="status '.$class.'"><span class="material-icons '.$class.'">' . $icon . '</span> '.$status.'</td>
                    <td class="group">' . stripslashes($reg->name) . '</td>
                    <td>' . $reg->comment . '</td>
                    <td class="link">
                        <!--span class="material-icons agreementDoc" data-id="'.$reg->id.'" title="Согласование документа">verified</span>
                        <span class="material-icons viewDoc" data-id="'.$reg->id.'" title="Просмотр документа">picture_as_pdf</span-->
                    </td>
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
<script src='/js/assets/agreement_list.js'></script>
<script src="/modules/documents/js/registry_items.js?v=<?= $gui->genpass() ?>"></script>
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
        echo 'el_app.dialog_open("agreement", {"docId": '.$open_dialog.'}, "documents");';
    }
    ?>
</script>