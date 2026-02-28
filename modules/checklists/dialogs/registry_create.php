<?php

use \Core\Gui;
use \Core\Db;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$db = new Db;
$gui = new Gui;
$reg = new Registry();
$roles = $db->getRegistry('roles');
$props = $db->getRegistry('checkitems', ' where 
 is_block = 1 ORDER BY id DESC', [], ['id', 'name', 'comment', 'label', 'is_block', 'block_id']);
$regs = $db->getRegistry('checklists');
?>
<div class="pop_up drag" style='min-width: 90vw; min-height: 90vh;'>
    <div class="title handle">
        <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
        <div class="name">Создать чек-лист</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form class="ajaxFrm checkForm" id="checklist_create" onsubmit="return false">
            <ul class='tab-pane'>
                <li id='tab_main' class='active'>Общее</li>
                <li id='tab_structure'>Набор полей</li>
                <li id='tab_form'>Форма</li>
            </ul>
            <div class='tab-panel' id='tab_main-panel'>
                <div class="group">
                    <div class="item w_50 required">
                        <div class="el_data">
                            <label>Наименование</label>
                            <input required class="el_input" type="text" name="reg_name">
                        </div>
                    </div>
                    <div class='item w_50 required'>
                        <div class='el_data'>
                            <label>Название таблицы в базе данных на англиской языке</label>
                            <input required class='el_input' type='text' name='table_name'
                                   placeholder="Вводите только латинские буквы" maxlength="60">
                        </div>
                    </div>
                    <div class='item w_50'>
                        <select required data-label='Статус' name='active'>
                            <option value='1' selected="selected">
                                Активен
                            </option>
                            <option value="0">
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
                            <textarea class="el_textarea" name="comment"></textarea>
                        </div>
                    </div>
                </div>

                <?/*div class="group">
                    <div class="item w_350">
                        <div class='el_data'>
                            <label for='in_menu' style="margin-left: 30px;">Разместить в левом меню</label>
                            <div class='custom_checkbox'>
                                <label class='container'>
                                    <input type='checkbox' name='in_menu' id='in_menu' tabindex='-1' value='1'>
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
                                           placeholder='Выбрать иконку' autocomplete="off"/>
                                    <div class='icons-grid'></div>
                                </div>
                                <div class='tm-icon-picker-append'></div>
                            </div>
                        </div>
                    </div>
                    <div class='item w_30'>
                        <div class='el_data short_name' style='visibility: hidden'>
                            <label>Короткое название</label>
                            <input class='el_input' type='text' name='short_name'>
                        </div>
                    </div>
                </div>

                <div class='group'>
                    <div class='item w_100'>
                        <select data-label='Родительский справочник' name='parent'>
                            <option value='0'>Без родителя</option>
                            <?
                            foreach ($regs['array'] as $value => $text) {
                                echo '<option value="' . $value . '">' . $text . '</option>';
                            }
                            ?>
                        </select>

                    </div>
                </div*/?>

                <div class='group'>
                    <div class='item w_100 required'>
                        <select multiple data-label='Доступ на редактирование' name='roles[]'>
                            <option value='0' selected="selected">Только суперадминистратор</option>
                            <?
                            foreach ($roles['array'] as $value => $text) {
                                echo '<option value="' . $value . '">' . $text . '</option>';
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
                                    <input type="text" id="search_all_props" placeholder="Введите название искомого поля">
                                    <span class="material-icons search_zoom" title="Поиск полей">search</span>
                                    <span class='material-icons search_clear hidden' title="Очистить">close</span>
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
                            <div class="button icon text short" id="addProps" title="Создать поле"><span
                                        class="material-icons">add</span>
                                Добавить поле
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
                        <div style='width: 100%'>
                            <label>Состав справочника</label>
                            <ol id='reg_props_list'>
                                <div class='search_props'>
                                    <input type='text' id='search_reg_props'
                                           placeholder='Введите название искомого поля'>
                                    <span class='material-icons search_zoom' title='Поиск полей'>search</span>
                                    <span class='material-icons search_clear hidden' title='Очистить'>close</span>
                                </div>
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
    el_app.mainInit();
    el_registry.create_init();
</script>