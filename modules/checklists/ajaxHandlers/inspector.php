<?php
use \Core\Db;
use Core\Gui;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db;
$gui = new Gui();
$reg = new Registry();
$field_id = intval($_POST['field']);
$regData = json_decode($_POST['regData'], true);
$ids = [];

foreach($regData as $item){
    $ids[] = $item['id'];
}

$field = $db->select("checkitems", ' WHERE id IN ('.implode(', ', $ids).') ORDER BY sort, id');

/*$field = $db->db::getAll('SELECT
        ' . TBL_PREFIX . 'checkitems.prop_id AS id, 
        ' . TBL_PREFIX . 'checkitems.name AS label, 
        ' . TBL_PREFIX . 'checkitems.type AS type
        FROM ' . TBL_PREFIX . 'checkfields, ' . TBL_PREFIX . 'checkitems
        WHERE ' . TBL_PREFIX . 'checkfields.prop_id = ' . TBL_PREFIX . 'checkitems.id AND ' . TBL_PREFIX . 'checkitems.id  IN ('.implode(', ', $ids).')'
);*/
//print_r($field);
$editField = $field[$field_id];

?>
<div class='group'>
    <div class='item w_100'>
        <div class='el_data'>
            <label style='margin-left: 30px' class='is_claimLabel' for='elem_visible'>По умолчанию элемент скрыт</label>
            <div class='custom_checkbox'>
                <label class='container'><input type='checkbox' name='elem_visible' id='elem_visible' class='is_claim'
                                                tabindex='-1' value='1'>
                    <span class='checkmark'></span></label>
            </div>
        </div>
    </div>
    <div class='item w_100 hidden' id="selectField">
        <select data-label="Отобразить, если в пункте:" name="parentField">
            <option value="0">&nbsp;</option>
            <?
            foreach($field as $el){
                echo '<option value="'.intval($el->id).'" data-type="'.$el->type.'">'.htmlspecialchars($el->label).'</option>'."\n";
            }
            ?>
        </select>
    </div>
    <div class='item w_100 hidden' id="fromSelectType">
        <div class='el_data vertical'>
            <label></label>
            <div class='custom_checkbox toggle'>
                <label class='container'>
                    <span class='label-text'>Выбрано любое значение</span>
                    <input type='radio' class='for_self' name='any_values' value='1'>
                    <span class='checkmark radio'></span>
                </label>
            </div>
            <div class='custom_checkbox toggle'>
                <label class='container'>
                    <span class='label-text'>Выбраны определённые значения</span>
                    <input type='radio' class='for_self' name='any_values' value='0'>
                    <span class='checkmark radio'></span>
                </label>
            </div>
        </div>
    </div>
    <div class="item w_100 hidden" id="fromSelectValues">
        <select multiple data-label='Выбрать значения' name='parentFieldItems'>

        </select>
    </div>

    <div class='confirm' style="width: 100%">

        <button class='button icon text apply' disabled="disabled"><span class='material-icons'>download_done</span>Применить</button>

    </div>
</div>
<script>
    el_registry.inspectorInit(<?=$field_id?>);
</script>