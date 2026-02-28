<?php

use Core\Gui;
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

if (isset($_GET['id']) && intval($_GET['id']) > 0 && !isset($_POST['params'])) {
	$regId = intval($_GET['id']);
} else {
	parse_str($_POST['params'], $paramArr);
	foreach ($paramArr as $name => $value) {
		$_GET[$name] = $value;
	}
	$regId = intval($_GET['id']);
	$_GET['url'] = $_POST['url'];
}

$gui = new Gui;
$db = new Db;
$auth = new Auth();

$table = $db->selectOne('registry', ' where id = ?', [$regId]);
$parent_item = $db->selectOne('registryitems', 'where parent='.$regId." LIMIT 1");
$parents = $db->getRegistry('registry');
$items = $db->getRegistry($table->table_name);

$subQuery = "";

$gui->set('module_id', 20);


$regs = $gui->getTableData($table->table_name);//print_r($_GET);
?>
<div class="nav">
	<div class="nav_01">
		<?
		echo $gui->buildTopNav([
				'title' => 'Справочники',
				'registryList' => '',
				'renew' => 'Сбросить все фильтры',
				'create' => 'Новая запись',
                'registry' => 'Все справочники',
			//'clone' => 'Копия записи',
				'delete' => 'Удалить выделенные',
				'import' => 'Импорт значений в справочник',
				'filter_panel' => 'Открыть панель фильтров',
				'logout' => 'Выйти'
		]);
		?>

		<?/*div class="button icon text" title="Журнал работ">
			<span class="material-icons">fact_check</span>Журнал работ
		</div*/?>
	</div>

</div>
<div class="scroll_wrap">
    <ul class='breadcrumb'>
        <li><a href='/registry'>Все справочники</a></li>
        <li><a href='/registry?id=<?=$regId?>'><?=$table->name?></a></li>
    </ul>
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
							'registryitems',
							'Родительские справочники',
							'parent_items',
							'constant',
							$items['array']
					);
					?>
				</th>
				<th class="sort">
					<?
					echo $gui->buildSortFilter(
							'registryitems',
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
			    if($regId == 14 && ($auth->haveUserRole(3) || $auth->haveUserRole(1))){
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

				if(strlen(trim($reg->name)) == 0){
                    $reg->name = $reg->surname.' '.$reg->first_name.' '.$reg->middle_name;
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
                    <td>' . $parents['array'][$reg->parent_registry] . $itemStr . '</td>
                    <td class="group"><a>' . stripslashes($reg->name) .'</a>'. (($auth->haveUserRole(3) || $auth->haveUserRole(1)) && $aCount > 0 ?
                        '<br><button class="button icon text showAnswers">
                    <span class="material-icons">feedback</span>'.$aCount .
                        ' альтернативн'.$gui->postfix($aCount, 'ый', 'ых', 'ых').' ответ'.
                        $gui->postfix($aCount, '', 'а', 'ов').'</button>' : '').'</td>
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
<script src="/modules/registry/js/registry_items.js?v=<?= $gui->genpass() ?>"></script>