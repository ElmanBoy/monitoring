<?php

use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Reports;

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
$report = new Reports();

$table = $db->selectOne('reports', ' where id = ?', [$regId]);

$gui->set('module_id', 16);


//$regs = $gui->getTableData('reports', " AND id = ".$regId);
?>
<div class="nav">
	<div class="nav_01">
		<?
		echo $gui->buildTopNav([
				'title' => 'Отчёты',
				//'registryList' => '',
				'renew' => 'Сбросить все фильтры',
				//'create' => 'Новая запись',
                //'registry' => 'Все справочники',
				//'export' => 'Экспорт отчёта в Excel',
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
        <li><a href='/results'>Все отчёты</a></li>
        <li><a href='/results?id=<?=$regId?>'><?=$table->name?></a></li>
    </ul>
    <?
    $data = $report->getDataById($regId);
    if(is_array($data['data']) && count($data['data']) > 0) {
        try {
            echo $report->buildCharByData($data['data']);
        } catch (Exception $e) {
        }
    ?>
	<form method="post" id="registry_items_delete" class="ajaxFrm">
		<input type="hidden" name="registry_id" id="registry_id" value="<?= $regId ?>">
		<table class="table_data report" id="tbl_registry_items">
			<thead>
			<tr class="fixed_thead">
				<th style="width: 20px;">№</th>
                <?
                foreach($data['columns'] as $col){
                    echo '<th>'.$col.'</th>';
                }
                ?>
			</tr>
			</thead>


			<tbody>
			<!-- row -->
			<?
			foreach ($data['data'] as $number => $value) {
				echo '<tr tabindex="0" class="noclick">
                    <td>' . ($number + 1) . '</td>
                    <td>' . $value['name'] . '</td>
                    <td>' . $value['value'] . '</td>
                </tr>';
			}
			?>
			</tbody>
		</table>
	</form>
	<?
	echo $gui->paging();
    }else{
        echo '<div class="item w_50">За выбранный период нет данных.</div>';
    }
	?>
</div>
<script src="/modules/results/js/registry_items.js?v=<?= $gui->genpass() ?>"></script>