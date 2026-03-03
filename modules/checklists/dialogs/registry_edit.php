<?php

use \Core\Db;
use \Core\Gui;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db;
$gui = new Gui;
$roles = $db->getRegistry('roles');
$reg_fields = $db->getRegistry('checkfields', 'where reg_id = ? AND is_block = 1 ORDER BY is_block DESC, block_id DESC, sort',
    [intval($_POST['params'])], ['id', 'prop_id', 'label', 'required', 'unique', 'row_behaviour', 'is_block', 'parent_id']);
$fields_is = [];
$subQuery = "";
if(count($reg_fields['array']) > 0) {
    foreach ($reg_fields['array'] as $item) {
        $fields_is[] = $item[1];
    }
    $subQuery = "id not in (".implode(', ', $fields_is).") AND ";
}
$props = $db->getRegistry('checkitems', ' where '.$subQuery.'
 is_block = 1 ORDER BY id DESC', [], ['id', 'name', 'comment', 'label', 'is_block', 'block_id']);

$registry = $db->selectOne('checklists', ' where id = ?', [intval($_POST['params'])]);


//Открываем транзакцию
$busy = $db->transactionOpen('roles', intval($_POST['params']));
$trans_id = $busy['trans_id'];

if ($busy != []) {

    $regs = $db->getRegistry('checklists', ' where id <> ?', [intval($_POST['params'])]);
    ?>
    <div class='pop_up drag' style="width: 90vw; min-height: 90vh;">
        <div class='title handle'>
            <!-- <div class='button icon move'><span class='material-icons'>drag_indicator</span></div>-->
            <div class='name'>Редактирование чек-листа &laquo;<?= $registry->name ?>&raquo;</div>
            <div class='button icon close'><span class='material-icons'>close</span></div>
        </div>
        <div class='pop_up_body'>
            <form class='ajaxFrm checkForm' id='checklist_edit' onsubmit='return false'>
                <input type = 'hidden' name = 'reg_id' value="<?= $registry->id ?>" >
                <input type = 'hidden' name = 'trans_id' value="<?= $trans_id ?>" >
                <ul class='tab-pane'>
                    <li id='tab_main' class='active'>Общее</li>
                    <li id='tab_structure'>Набор полей</li>
                    <li id='tab_form'>Форма</li>
                </ul>
                <div class='tab-panel' id='tab_main-panel'>
                    <div class='group'>
                        <div class='item w_50 required'>
                            <div class='el_data'>
                                <label>Наименование</label>
                                <input required class='el_input' type='text' name='reg_name' value="<?= $registry->name ?>">
                            </div>
                        </div>
                        <div class='item w_50 required'>
                            <div class='el_data'>
                                <label>Название таблицы в базе данных на англиской языке</label>
                                <input required class='el_input' type='text' name='table_name'
                                       placeholder='Вводите только латинские буквы' maxlength='60' value="<?= $registry->table_name ?>">
                            </div>
                        </div>
                        <div class='item w_50'>
                            <select required data-label='Статус' name='active'>
                                <option value='1' <?= $registry->active == 1 ? ' selected="selected"' : '' ?>>
                                    Активен
                                </option>
                                <option value='0' <?= $registry->active == 0 ? ' selected="selected"' : '' ?>>
                                    Заблокирован
                                </option>
                            </select>
                        </div>
                        <? /*div class="item w_50">
                    <select data-label="Родительский справочник" name="parent">
                        <option value="0">Без родителя</option>
                        <?
                        foreach($registry['array'] as $value => $text){
                            echo '<option value="'.$value.'">'.$text.'</option>';
                        }
                        ?>
                    </select>

                </div*/ ?>
                        <div class="item w_100">
                            <div class="el_data">
                                <label>Примечания</label>
                                <textarea class="el_textarea" name="comment"><?= str_replace('<br>', "\n", $registry->comment) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="group">
                        <div class="item w_350">
                            <div class='el_data'>
                                <label for='in_menu' style="margin-left: 30px;">Разместить в левом меню</label>
                                <div class='custom_checkbox'>
                                    <label class='container'>
                                        <input type='checkbox' name='in_menu' id='in_menu' tabindex='-1' value='1'
                                            <?= $registry->in_menu == 1 ? ' checked="checked"' : '' ?>>
                                        <span class='checkmark'></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="item w_30">
                            <div class='el_data tm-icon-picker' style="visibility: hidden">
                                <label>Иконка в меню</label>
                                <div class='tm-icon-picker-input-wrapper'>
                                    <div class='tm-icon-picker-input'>
                                        <input type='text' name='icon' class='el_input tm-icon-picker-input-text'
                                               placeholder='Выбрать иконку' autocomplete="off" value="<?= $registry->icon ?>"/>
                                        <div class='icons-grid'></div>
                                    </div>
                                    <div class='tm-icon-picker-append'>
                                        <i class='material-icons mdi mdi-<?= $registry->icon ?>'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='item w_30'>
                            <div class='el_data short_name' style='visibility: hidden'>
                                <label>Короткое название</label>
                                <input class='el_input' type='text' name='short_name' value="<?= $registry->short_name ?>">
                            </div>
                        </div>
                    </div>

                    <div class='group'>
                        <div class='item w_100'>
                            <select data-label='Родительский справочник' name='parent'>
                                <option value='0'>Без родителя</option>
                                <?
                                foreach ($regs['array'] as $value => $text) {
                                    echo '<option value="' . $value . '"' . ($registry->parent == $value ? ' selected' : '') . '>' . $text . '</option>';
                                }
                                ?>
                            </select>

                        </div>
                    </div>

                    <div class='group'>
                        <div class='item w_100 required'>
                            <select multiple data-label='Доступ на редактирование' name='roles[]'>
                                <option value='0' selected="selected">Только суперадминистратор</option>
                                <?
                                foreach ($roles['array'] as $value => $text) {
                                    echo '<option value="' . $value . '' . ($registry->roles == $value ? ' selected' : '') . '">' . $text . '</option>';
                                }
                                ?>
                            </select>

                        </div>
                    </div>
                </div>
                <div class='tab-panel' id='tab_structure-panel' style='display: none'>
                    <div class='group'>
                        <div class='item w_50'>
                            <div style="width: 100%">
                                <label>Все поля</label>

                                <ol id="all_props_list">
                                    <div class='search_props'>
                                        <input type='text' id='search_all_props'
                                               placeholder='Введите название искомого поля'>
                                        <span class='material-icons search_zoom' title='Поиск полей'>search</span>
                                        <span class='material-icons search_clear hidden' title='Очистить'>close</span>
                                    </div>
                                    <?
                                    $item = [];
                                    $blockNumber = 1;
                                    foreach ($props['array'] as $block) {
                                        $prefix = $class = '';
                                        if ($block[4] == '1') { //is_block
                                            $opened = $_COOKIE['rowItem' . $block[0]] == 'open';
                                            $class = ' blockItem';
                                            if ($_COOKIE['rowItem' . $block[0]] == 'open') {
                                                $opened = true;
                                                $title = 'Свернуть';
                                                $iconText = 'expand_more';
                                            } else {
                                                $opened = false;
                                                $title = 'Развернуть';
                                                $iconText = 'chevron_right';
                                            }
                                            echo '
                                            <li data-id="' . $block[0] . '" class="item'.$class.'">
                                                <div class="el_data block_data" data-id="' . $block[0] . '">
                                                    <div class="custom_checkbox">
                                                        <label class="container">
                                                            <input type="hidden" name="props[]" value="' . $block[0] . '">
                                                            <input class="block_check" type="checkbox" name="prop' . $block[0] . '" id="prop' . $block[0] . '" tabindex="-1" value="' . $block[0] . '">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                    </div>
                                                    <label class="fieldName block" for="prop' . $block[0] . '" ><div class="blockNumber">' . $blockNumber . '</div>. Блок: ' . $block[1] . '</label>
                                                    <span class="material-icons show_block" data-id="' . $block[0] . '" title="' . $title . '">' . $iconText . '</span>
                                                    <span title="Добавить пункт" class="material-icons add_item">playlist_add_check</span>
                                                </div>';


                                            $items = $db->db::getAll('SELECT * FROM ' . TBL_PREFIX . 'checkitems WHERE block_id = ' .
                                                intval($block[0]) . ' ORDER BY sort, id'
                                            );
                                            if (count($items) > 0) { //list of children
                                                $itemNumber = 1;
                                                echo '<ol>';
                                                foreach ($items as $item) {
                                                    echo '
                                                        <li class="el_data" data-parent="'.$block[0].'"'.($opened ? '' : ' style="display:none"').' data-itemId="'.$item['id'].'">
                                                            <div class="custom_checkbox">
                                                                <label class="container">
                                                                    <input type="hidden" name="props[]" value="' . $item['id'] . '">
                                                                    <input type="checkbox" name="prop' . $item['id'] . '" data-parent="'.$block[0].'" id="prop' .
                                                                    $item['id'] . '" tabindex="-1" value="' . $item['id'] . '">
                                                                    <span class="checkmark"></span>
                                                                </label>
                                                            </div>
                                                            <label class="fieldName" for="prop' . $item['id'] . '" ><div class="itemNumber">' .
                                                    ($item['block_id'] != '0' ? $blockNumber . '.' . $itemNumber : $item['id']).'</div> ' . $item['name'] . '</label>
                                                        </li>';
                                                    $itemNumber++;
                                                }
                                                echo '</ol>';
                                            }
                                            echo '</li>';
                                            $blockNumber++;
                                        }elseif(intval($block['block_id']) == 0){
                                            echo '<li class="item">
                                                    <div class="el_data" data-id="' . $block[0] . '">
                                                        <div class="custom_checkbox">
                                                            <label class="container">
                                                                <input type="hidden" name="props[]" value="' . $block[0] . '">
                                                                <input class="block_check" type="checkbox" name="prop' . $block[0] . '" id="prop' . $block[0] . '" tabindex="-1" value="' . $block[0] . '">
                                                                <span class="checkmark"></span>
                                                            </label>
                                                        </div>
                                                        <label class="fieldName" for="prop' . $block[0] . '" ><div class="itemNumber">' . $block[0]. '</div>. ' . $block[1] . '</label>
                                                    </div>
                                                </li>';
                                        }
                                    }
                                    ?>
                                </ol>
                                <div class="button icon text short" id="addItem" title="Создать пункт чек-листа"><span
                                            class="material-icons">playlist_add_check</span>Добавить пункт
                                </div>
                                <div class="props_actions">
                                    <div class="button icon short" id="add_props" title="Добавить поля в справочник">
                                        <span class='material-icons'>chevron_right</span></div>
                                    <div class="button icon short" id="remove_props"
                                         title="Удалить поля из справочника"><span
                                                class='material-icons'>chevron_left</span></div>
                                </div>
                                <? /*div class='button icon removeProps' title='Удалить поле'><span
                                    class='material-icons'>remove</span></div*/ ?>
                                <input type="hidden" name="reg_prop" value="">
                            </div>
                        </div>
                        <div class='item w_50'>
                            <div style="width:100%">
                                <label>Состав справочника</label>
                                <ol id='reg_props_list'>
                                    <div class='search_props'>
                                        <input type='text' id='search_reg_props'
                                               placeholder='Введите название искомого поля'>
                                        <span class='material-icons search_zoom' title='Поиск полей'>search</span>
                                        <span class='material-icons search_clear hidden' title='Очистить'>close</span>
                                    </div>
                                    <?
                                    reset($reg_fields['array']);
                                    $blockNumber = 1;
                                    foreach ($reg_fields['array'] as $block) {//print_r($block);
                                        if($block[6] == '1') {
                                            $opened = $_COOKIE['rowItem' . $block[0]] == 'open';
                                            if ($_COOKIE['rowItem' . $block[1]] == 'open') {
                                                $opened = true;
                                                $title = 'Свернуть';
                                                $iconText = 'expand_more';
                                            } else {
                                                $opened = false;
                                                $title = 'Развернуть';
                                                $iconText = 'chevron_right';
                                            }
                                            echo '<li class="item blockItem" data-id="' . $block[1] . '">
                                                    <div class="el_data block_data">
                                                        <div class="custom_checkbox">
                                                            <label class="container">
                                                                <input type="hidden" name="prop[]" value="' . $block[1] . '">
                                                                <input class="block_check" type="checkbox" name="prop' . $block[1] . '" id="prop' . $block[1] . '" tabindex="-1" value="' . $block[1] . '">
                                                                <span class="checkmark"></span>
                                                            </label>
                                                        </div>
                                                        <label class="fieldName block" for="prop' . $block[1] . '"><div class="blockNumber">'.$blockNumber.'</div>. Блок: ' . $block[2] . '</label>
                                                        <span class="material-icons show_block" data-id="' . $block[1] . '" title="'.$title.'">'.$iconText.'</span>
                                                        <span class="material-icons drag_handler" title="Переместить">drag_handle</span>
                                                    </div>
                                                  ';

                                            $items = $db->db::getAll('SELECT * FROM ' . TBL_PREFIX . 'checkitems WHERE block_id = ' .
                                                intval($block[1]) . ' ORDER BY sort, id'
                                            );

                                            if (count($items) > 0) {
                                                $itemNumber = 1;
                                                echo '<ol>';
                                                foreach ($items as $item) {
                                                    echo "
                                                    <li class='el_data' data-parent='".$block[1]."' data-itemid='" . $item['id'] . "' data-row-behaviour='" . $item['row_behaviour'] . "'".
                                                        ($opened ? '' : ' style="display:none"').">
                                                        <div class='custom_checkbox'>
                                                            <label class='container'>
                                                                <input type='hidden' name='prop[]' value='" . $item['id'] . "'>
                                                                <input type='checkbox' name='prop" . $item['id'] . "' id='prop" . $item['id'] . "' tabindex='-1'
                                                                       value='" . $item['id'] . "'>
                                                                <span class='checkmark'></span>
                                                            </label>
                                                        </div>
                                                        <label class='fieldName' for='prop" . $item['id'] . "'><div class='itemNumber'>".$blockNumber.".".$itemNumber."</div> " . $item['label'] . "</label>
                                                    </li>
                                            <!--span class='material-icons required' title='Обязательное поле'>" . ($item[3] == '1' ? 'check_circle' : 'panorama_fish_eye') . "</span-->";
                                                    $itemNumber++;
                                                }
                                                echo '</ol></li>';
                                            }

                                        }else{
                                            echo '
                                                <li class="el_data">
                                                    <div class="custom_checkbox">
                                                        <label class="container">
                                                            <input type="hidden" name="prop[]" value="' . $block[1] . '">
                                                            <input class="block_check" type="checkbox" name="prop' . $block[1] . '" id="prop' . $block[1] . '" tabindex="-1" value="' . $block[1] . '">
                                                            <span class="checkmark"></span>
                                                        </label>
                                                    </div>
                                                    <label class="fieldName" for="prop' . $block[1] . '"><div class="blockNumber">'.$blockNumber.'</div>. ' . $block[2] . '</label>
                                                    <span class="material-icons drag_handler" title="Переместить">drag_handle</span>
                                                </li>';
                                        }
                                        $blockNumber++;
                                    }
                                        ?>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='tab-panel' id='tab_form-panel' style='display: none'>

                </div>
                <div class="confirm">

                    <button class="button icon text"><span class="material-icons">save</span>Сохранить</button>

                </div>
            </form>
        </div>

    </div>
    <script>
        $(document).ready(function(){
            el_app.mainInit();
            el_registry.create_init();
            $('.custom_checkbox input#in_menu').trigger("change");
            $('#reg_props_list').nestedSortable({
                axis: 'y',
                cursor: 'grabbing',
                listType: 'ol',
                handle: '.drag_handler',
                items: 'li',
                stop: function (event, ui) {
                    el_registry.setNewRegistryData();
                }
            });
            $("#registry_edit .close").on("click", function () {
                $.post("/", {ajax: 1, action: "transaction_close", id: <?=$trans_id?>}, function () {
                })
            });
            $(window).on("beforeunload", function(){
            $.post("/", {ajax: 1, action: "transaction_close", id: <?=$trans_id?>}, function(){})
        });
        });
    </script>
    <script type='text/javascript' src='/js/assets/icon-picker/js/scripts.js?v=<?= $gui->genpass() ?>'></script>
    <?php
} else {
    ?>
    <script>
        alert("Эта запись редактируется пользователем <?=$busy['user_name']?>");
        el_app.dialog_close("role_edit");
    </script>
    <?
}
?>