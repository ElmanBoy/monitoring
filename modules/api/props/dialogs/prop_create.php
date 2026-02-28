<?php

use \Core\Db;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$db = new Db;
$reg = new Registry();

$registry = $db->getRegistry('registry');
?>
<div class="pop_up drag">
    <div class="title handle">
        <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
        <div class="name">Создать поле</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form class="ajaxFrm" id="prop_create" onsubmit="return false">
            <input type="hidden" name="path" value="registry/props">
            <div class='tab-panel' id='tab_main-panel'>
                <div class="group">
                    <div class="item w_50 required">
                        <div class="el_data">
                            <label>Название &lt;label&gt;</label>
                            <input required class="el_input" type="text" name="prop_name">
                        </div>
                    </div>
                    <div class='item w_50 required'>
                        <div class='el_data'>
                            <label>Имя поля в базе данных на английском языке &lt;name&gt;</label>
                            <input required class='el_input' type='text' name='field_name' maxlength="60">
                        </div>
                    </div>

                    <div class='item w_50'>
                        <div class='el_data'>
                            <label>Подсказка в поле &lt;placeholder&gt;</label>
                            <input class='el_input' type='text' name='placeholder'>
                        </div>
                    </div>

                    <div class='item w_50 required'>
                        <select data-label='Тип поля' name='field_types' required="required">
                            <?
                            foreach($reg->props_array as $key => $val){
                                echo '<option value="'.$key.'">'.$val.'</option>'."\n";
                            }
                            ?>
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


                    <div class='item w_100 field_option' id='sizefield'>
                        <div class='el_data'>
                            <label>Максимальная длина значения (символов)</label>
                            <input name='size' class="el_input" type='number' id='size'>
                        </div>
                    </div>
                    <div class='item w_100 field_option' id='minmax'>
                        <div class="item w_100">
                            <div class='el_data'>
                                <label>Минимальное значение:</label>
                                <input name='min_value' class='el_input' type='number'>
                            </div>
                        </div>
                        <div class="item w_100">
                            <div class='el_data'>
                                <label>Максимальное значение:</label>
                                <input name='max_value' class='el_input' type='number'>
                            </div>
                        </div>
                        <div class="item w_100">
                            <div class='el_data'>
                                <label>Шаг:</label>
                                <input name='step' class='el_input' type='number' id='step'>
                            </div>
                        </div>
                    </div>
                    <div class='item w_100 field_option' id='default_value'>
                        <div class='el_data'>
                            <label>Значение по умолчанию</label>
                            <input name='default_value' class='el_input' type='text'>
                        </div>
                    </div>
                    <div class='item w_100 field_option' id='default_date'>
                        <div class='el_data' style="margin-top: 30px;">
                            <label for='curr_date' style='margin-left: 30px;'>По умолчанию текущая дата</label>
                            <div class='custom_checkbox'>
                                <label class='container'>
                                    <input type='checkbox' name='curr_date' id='curr_date' tabindex='-1' value='1'>
                                    <span class='checkmark'></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class='item w_100 field_option' id='default_time'>
                        <div class='el_data' style='margin-top: 30px;'>
                            <label for='curr_time' style='margin-left: 40px;'>По умолчанию текущее время</label>
                            <div class='custom_checkbox'>
                                <label class='container'>
                                    <input type='checkbox' name='curr_time' id='curr_time' tabindex='-1' value='1'>
                                    <span class='checkmark'></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class='item w_100 field_option' id='default_datetime'>
                        <div class='el_data' style='margin-top: 30px;'>
                            <label for='curr_datetime' style='margin-left: 40px;'>По умолчанию текущие дата и время</label>
                            <div class='custom_checkbox'>
                                <label class='container'>
                                    <input type='checkbox' name='curr_datetime' id='curr_datetime' tabindex='-1' value='1'>
                                    <span class='checkmark'></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class='item w_100 field_option' id='calendar_type'>
                        <div class='el_data' style="margin-top:40px">
                            <label style="top: -50px;">Тип календаря:</label>
                            <div class='custom_checkbox'>
                                <label class='container'> Выпадающий
                                    <input type='radio' name='calendar_type' value='dropdown'>
                                    <span class="checkmark radio"></span></label>
                            </div>
                            <div class="custom_checkbox">
                                <label class="container" style="margin-left: 160px;"> Встроенный
                                    <input type="radio" name="calendar_type" value="inline">
                                    <span class="checkmark radio"></span></label>
                            </div>
                        </div>
                    </div>
                    <div class='item w_100 field_option' id='area'>
                        <div class='item w_100'>
                            <div class='el_data'>
                                <label>Ширина (в символах):</label>
                                <input name='cols' class='el_input' type='number'>
                            </div>
                        </div>
                        <div class='item w_100'>
                            <div class='el_data'>
                                <label>Высота (количество строк):</label>
                                <input name='rows' class='el_input' type='number'>
                            </div>
                        </div>
                    </div>
                    <div class='item w_100 field_option' id='check'>
                        <div class='check_button group'>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <label>Название кнопки:</label>
                                    <input name='checkbox_label[]' class='el_input' type='text'>
                                </div>
                            </div>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <label>Значение:</label>
                                    <input name='checkbox_value[]' class='el_input' type='text'>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='item w_100 field_option' id='radio'>
                        <div class='el_data'>
                            <label>Заголовок:</label>
                            <input name='radio_title' class='el_input' type='text'>
                        </div>
                        <div class="check_button group">
                            <h5 class='item w_100 check_number'>Кнопка №1</h5>
                            <div class="item w_50">
                                <div class='el_data'>
                                    <label>Название кнопки:</label>
                                    <input name='radio_label[]' class='el_input' type='text'>
                                </div>
                            </div>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <label>Значение:</label>
                                    <input name='radio_value[]' class='el_input' type='text'>
                                </div>
                            </div>
                        </div>
                        <button class='button icon text new_check'><span class='material-icons'>add</span>
                            Еще кнопка
                        </button>
                    </div>
                    <div class='item w_100 field_option' id='oplist'>
                        <div class='option_button group'>
                            <h5 class='item w_100 option_number'>Опция №1</h5>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <label>Название опции:</label>
                                    <input name='option_label[]' class='el_input' type='text'>
                                </div>
                            </div>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <label>Значение:</label>
                                    <input name='option_value[]' class='el_input' type='text'>
                                </div>
                            </div>
                        </div>
                        <button class='button icon text new_option'><span class='material-icons'>add</span>
                            Еще опция
                        </button>
                    </div>
                    <div class='item w_100 field_option' id='fromdb'>
                        <div class='el_data'>
                            <select data-label='Из справочника' name='fromdb'>
                                <option value="">&nbsp;</option>
                                <? foreach ($registry['array'] as $id => $name) { ?>
                                    <option value="<?= $id ?>"><?= $name ?></option>
                                <? } ?>
                            </select>
                        </div>
                        <div class='el_data'>
                            <input type='hidden' name='selected_field' value="<?= $registry->from_db_text ?>">
                            <select data-label='Поле справочника (текст)' name='fromdb_fields'>
                                <option value=''>&nbsp;</option>
                            </select>
                        </div>
                        <div class='el_data'>
                            <input type='hidden' name='selected_value' value="<?= $registry->from_db_value ?>">
                            <select data-label='Поле справочника (значение)' name='fromdb_value'>
                                <option value=''>&nbsp;</option>
                            </select>
                        </div>
                    </div>

                </div>
            </div>
            <div class='tab-panel' id='tab_structure-panel'>

            </div>
            <div class='tab-panel' id='tab_form-panel'>

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
