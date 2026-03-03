<?php

use Core\Gui;
use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

if (isset($_POST['params']) && substr_count($_POST['params'], 'id=') > 0) {
	include_once $_SERVER['DOCUMENT_ROOT'] . '/modules/checklists/items/pages/index_id_ajax.php';
} else {
	$gui = new Gui;
	$db = new Db();
	$module_props = $gui->getModuleProps('checklists');
	//$regs = $gui->getTableData('checkitems', ' ORDER BY is_block ASC, block_id ASC, sort ASC, id ASC');
    $blocksTotal = $db->db::getAll('SELECT * FROM ' . TBL_PREFIX . 'checkitems WHERE is_block = 1');
    $gui->set("totalRows", count($blocksTotal));
    $gui->set('rowsLimit', 20);
    $gui->set('currentPageNumber', 0);
    parse_str($_POST['params'], $params);
    if (intval($params['pn']) > 0) {
        $gui->set('currentPageNumber', intval($params['pn']));
    }
    $offset = $gui->currentPageNumber * $gui->rowsLimit;

    $blocks = $db->db::getAll("SELECT * FROM ".TBL_PREFIX."checkitems 
    ORDER BY is_block ASC, block_id DESC, id DESC, sort LIMIT {$gui->rowsLimit} OFFSET $offset");
	?>
	<div class="nav">
		<div class="nav_01">
			<?
			echo $gui->buildTopNav([
					'title' => 'Пункты чек-листов',
					'renew' => 'Сбросить все фильтры',
                    'create_block' => 'Новый блок',
					'create' => 'Новый пункт',
					'clone' => 'Копия пункта',
					'delete' => 'Удалить выделенные',
					'checklists' => 'Чек-листы',
					'logout' => 'Выйти'
			]);
			?>
		</div>

	</div>
	<div class="scroll_wrap">
		<form method="post" id="item_delete" class="ajaxFrm">
			<table class="table_data" id="tbl_items" style="min-width: 80%;">
				<thead>
				<tr>
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
								'registry',
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
								'registry',
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
								'registry',
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
                $blockNumber = 1;
                foreach ($blocks as $block) {
                    if($block['is_block'] == 1) {
                        $opened = $_COOKIE['rowItem'.$block['id']] == 'open';
                        if($_COOKIE['rowItem'.$block['id']] == 'open'){
                            $opened = true;
                            $title = 'Свернуть';
                            $iconText = 'expand_more';
                        }else{
                            $opened = false;
                            $title = 'Развернуть';
                            $iconText = 'chevron_right';
                        }
                        echo '<tr data-id="' . $block['id'] . '" tabindex="0" class="block_row">
                            <td>
                                <div class="custom_checkbox">
                                    <label class="container"><input type="checkbox" name="reg_id[]" tabindex="-1" value="' . $block['id'] . '">
                                    <span class="checkmark"></span></label>
                                </div>
                            </td>
                            <td>' . $blockNumber . '</td>
                            <td class="status">
                            <span class="material-icons">' . (($block['active'] == 1) ? 'task_alt' : 'radio_button_unchecked') . '</span></td>
                            <td><span>Блок:</span> ' . $block['name'] . '
                            <i class="material-icons" data-id="'.$block['id'].'" title="'.$title.'">'.$iconText.'</i>
                             <a class="button icon text">
                                <span class="material-icons">playlist_add_check</span>Добавить пункт</a>
                            </td>
                            <td>' . $block['comment'] . '</td>
                        </tr>';


                        $items = $db->db::getAll('SELECT * FROM ' . TBL_PREFIX . 'checkitems WHERE block_id = ' .
                            intval($block['id'])." ORDER BY sort, id");
                        if (count($items) > 0) {
                            $itemNumber = 1;
                            foreach ($items as $item) {
                                echo '<tr data-id="' . $item['id'] . '" data-parent="'.$block['id'].'" tabindex="0"'.($opened ? 'expand_more' : ' style="display:none"').'>
                                <td>
                                    <div class="custom_checkbox">
                                        <label class="container"><input type="checkbox" name="reg_id[]" tabindex="-1" value="' . $item['id'] . '">
                                        <span class="checkmark"></span></label>
                                    </div>
                                </td>
                                <td>' . ($item['block_id'] != '0' ? $blockNumber . '.' . $itemNumber : $item['id']) . '</td>
                                <td class="status">
                                <span class="material-icons">' . (($item['active'] == 1) ? 'task_alt' : 'radio_button_unchecked') . '</span></td>
                                <td>' . $item['name'] . '</td>
                                <td>' . $item['comment'] . '</td>
                                </tr>';
                                $itemNumber++;
                            }
                        }
                        //echo '<tr class="divider noclick"><td colspan="5"></td> </tr>';
                        $blockNumber++;
                    }elseif(intval($block['block_id']) == 0){
                        echo '<tr data-id="' . $block['id'] . '" tabindex="0">
                                <td>
                                    <div class="custom_checkbox">
                                        <label class="container"><input type="checkbox" name="reg_id[]" tabindex="-1" value="' . $block['id'] . '">
                                        <span class="checkmark"></span></label>
                                    </div>
                                </td>
                                <td>' . $block['id'] . '</td>
                                <td class="status">
                                <span class="material-icons">' . (($block['active'] == 1) ? 'task_alt' : 'radio_button_unchecked') . '</span></td>
                                <td>' . $block['name'] . '</td>
                                <td>' . $block['comment'] . '</td>
                                </tr>';
                    }
                }

				?>
				</tbody>
			</table>
		</form>
        <?php
        echo $gui->paging();
        ?>
	</div>
	<script src="/modules/checklists/items/js/registry.js?v=<?= $gui->genpass() ?>"></script>
	<?php
}
?>
