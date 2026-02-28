<?php

use \Core\Db;
use \Core\Gui;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db;
$gui = new Gui;
$roles = $db->getRegistry('roles');
$reg_fields = $db->getRegistry('regfields', 'where reg_id = ? ORDER BY sort',
    [intval($_POST['params'])], ['id', 'prop_id', 'label', 'required', 'unique']);
$fields_is = [];
foreach ($reg_fields['array'] as $item) {
    $fields_is[] = $item[1];
}
$props = $db->getRegistry('regprops', ' where id not in ('.implode(', ', $fields_is).') ORDER BY id DESC',
    [], ['id', 'name', 'comment', 'label']);

$registry = $db->selectOne('registry', ' where id = ?', [intval($_POST['params'])]);


//Открываем транзакцию
$busy = $db->transactionOpen('roles', intval($_POST['params']));
$trans_id = $busy['trans_id'];

if ($busy != []) {

    $regs = $db->getRegistry('registry', ' where id <> ?', [intval($_POST['params'])]);
    ?>
    <link rel='stylesheet'
          href='https://cdnjs.cloudflare.com/ajax/libs/MaterialDesign-Webfont/7.2.96/css/materialdesignicons.min.css'
          integrity='sha512-LX0YV/MWBEn2dwXCYgQHrpa9HJkwB+S+bnBpifSOTO1No27TqNMKYoAn6ff2FBh03THAzAiiCwQ+aPX+/Qt/Ow=='
          crossorigin='anonymous' referrerpolicy='no-referrer'/>

    <link type='text/css' rel='stylesheet' href='/js/assets/icon-picker/css/style.css?v=<?= $gui->genpass() ?>'/>
    <div class='pop_up drag'>
        <div class='title handle'>
            <!-- <div class='button icon move'><span class='material-icons'>drag_indicator</span></div>-->
            <div class='name'>Редактирование справочника &laquo;<?= $registry->name ?>&raquo;</div>
            <div class='button icon close'><span class='material-icons'>close</span></div>
        </div>
        <div class='pop_up_body'>
            <form class='ajaxFrm' id='registry_edit' onsubmit='return false'>
                <input type = 'hidden' name = 'reg_id' value="<?= $registry->id ?>" >
                <input type = 'hidden' name = 'trans_id' value="<?= $trans_id ?>" >
                <input type="hidden" name="path" value="registry">
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
                            <div class=''>
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
                                    foreach ($props['array'] as $item) {
                                        echo '
                                <li class="item"' . (strlen($item[2]) > 0 ? 'title="' . $item[2] . '"' : '') . '>
                                <div class="el_data"><label class="fieldName" for="prop' . $item[0] . '" style="margin-left: 40px;">' . $item[1] . '</label>
                                <div class="custom_checkbox">
                                    <label class="container">
                                        <input type="hidden" name="props[]" value="' . $item[0] . '">
                                        <input type="checkbox" name="prop' . $item[0] . '" id="prop' . $item[0] . '" tabindex="-1" value="' . $item[0] . '">
                                        <span class="checkmark"></span>
                                    </label>
                                </div></div>
                                </li>';
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
                            <div>
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
                                    foreach ($reg_fields['array'] as $item) {
                                        echo "
                                    <li class='item' data-required='".$item[3]."' data-unique='".$item[4]."'>
                                        <div class='el_data'><label class='fieldName' for='prop" . $item[1] . "'
                                                                    style='margin-left: 40px;'>" . $item[2] . "</label>
                                            <div class='custom_checkbox'>
                                                <label class='container'>
                                                    <input type='hidden' name='prop[]' value='" . $item[1] . "'>
                                                    <input type='checkbox' name='prop" . $item[1] . "' id='prop" . $item[1] . "' tabindex='-1'
                                                           value='" . $item[1] . "'>
                                                    <span class='checkmark'></span>
                                                </label>
                                            </div>
                                        </div>
                                        <span class='material-icons required' title='Обязательное поле'>".($item[3] == '1' ? 'check_circle' : 'panorama_fish_eye')."</span>
                                        <span class='material-icons unique' title='Уникальное поле'>".($item[4] == '1' ? 'check_circle' : 'panorama_fish_eye')."</span>
                                        <span class='material-icons rename' title='Переименовать поле'>drive_file_rename_outline</span>
                                        <span class='material-icons rename_done' title='Переименовать поле'>done</span>
                                        <span class='material-icons drag_handler ui-sortable-handle'
                                              title='Переместить'>drag_handle</span></li>";
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
    <script src="/modules/registry/js/registry.js"></script>
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