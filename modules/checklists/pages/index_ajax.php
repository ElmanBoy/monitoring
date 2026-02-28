<?php

use Core\Gui;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

if (isset($_POST['params']) && substr_count($_POST['params'], 'id=') > 0) {
	include_once $_SERVER['DOCUMENT_ROOT'] . '/modules/checklists/pages/index_id_ajax.php';
} else {
	$gui = new Gui;
	$module_props = $gui->getModuleProps('checklists');
	$regs = $gui->getTableData('checklists');
	?>
	<div class="nav">
		<div class="nav_01">
			<?
			echo $gui->buildTopNav([
					'title' => 'Чек-листы',
					'renew' => 'Сбросить все фильтры',
					'create' => 'Новый чек-лист',
					'clone' => 'Копия чек-листа',
					'delete' => 'Удалить выделенные',
					'check_items' => 'Пункты чек-листов',
					'logout' => 'Выйти'
			]);
			?>
		</div>

	</div>
	<div class="scroll_wrap">
		<form method="post" id="registry_delete" class="ajaxFrm">
			<table class="table_data" id="tbl_registry">
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
				//Выводим созданные справочники
				$tab = 10;
				foreach ($regs as $reg) {
					$tab++;
					echo '<tr data-id="' . $reg->id . '" tabindex="0">
                                        <td>
                                            <div class="custom_checkbox">
                                                <label class="container"><input type="checkbox" name="reg_id[]" tabindex="-1" value="' . $reg->id . '">
                                                <span class="checkmark"></span></label>
                                            </div>
                                        </td>
                                        <td>' . $reg->id . '</td>
                                        <td class="status">
                                        <span class="material-icons">' . (($reg->active == 1) ? 'task_alt' : 'radio_button_unchecked') . '</span></td>
                                        <td>' . $reg->name . '</td>
                                        <td>' . $reg->comment . '</td>
                                        <td class="link">
                                        <span class="material-icons reg_settings" title="Редактирование чек-листа">edit</span>
                                        </td>
                                    </tr>';
				}
				?>
				</tbody>
			</table>
		</form>
        <?php
        echo $gui->paging();
        ?>
	</div>
	<script src="/modules/checklists/js/registry.js?v=<?= $gui->genpass() ?>"></script>
	<?php
}
?>
