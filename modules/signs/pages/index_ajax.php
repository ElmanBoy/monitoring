<?php

use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Date;

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
}
$regId = 69;*/

$gui = new Gui;
$db = new Db;
$auth = new Auth();
$date = new Date();

$users = $db->getRegistry('users');
$items = $db->getRegistry('signs');
$agreement = $db->getRegistry('agreement');
$ins = $db->getRegistry('institutions', '', [], ['id', 'short']);
$checkstaff = $db->getRegistry('checkstaff', '', [],['id', 'institution']);

function getDocumentName($table, $doc_id)
{
    global $agreement, $ins, $checkstaff;
    if($table == 'agreement'){
        return $agreement['result'][$doc_id]->name;
    }
    if($table == 'checkstaff'){
        return $ins['result'][$checkstaff['result'][$doc_id]->institution]->short;
    }
    return 'Документ отсутствует';
}

$subQuery = '';

$gui->set('module_id', 21);


$regs = $gui->getTableData('signs');
?>
<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
                'title' => 'Журнал ЭЦП',
                //'registryList' => '',
                'renew' => 'Сбросить все фильтры',
                //'create' => 'Новый документ',
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
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'signs',
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
                        'signs',
                        'Дата и время',
                        'created_at',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'signs',
                        'Пользователь',
                        'active',
                        'constant',
                        [$users['array']]
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'signs',
                        'Документ',
                        'doc_id',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class='sort'>
                    <?
                    echo $gui->buildSortFilter(
                        'signs',
                        'Режим',
                        'type',
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
            foreach ($regs as $reg) {


                echo '<tr data-id="' . $reg->id . '" data-parent="' . $regId . '" tabindex="0">
                    <td>' . $reg->id . '</td>
                    <td>' . $date->formatPostgresDate($reg->created_at) . '</td>
                    <td> '.$gui->getUserFio($reg->user_id, 'short').'</td>
                    <td>' . getDocumentName($reg->table_name, $reg->doc_id) . '</td>
                    <td>' . ($reg->type == 1 ? 'Подписание' : 'Согласование') . '</td>
                    <td class="link">
                        <span class="material-icons viewSign" data-id="'.$reg->id.'" title="Просмотр подписи">pageview</span>
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
    $(".viewSign").off("click").on("click", function(){
        $(this).closest("td").trigger("dblclick");
    })
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