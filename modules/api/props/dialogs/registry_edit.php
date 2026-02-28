<?php

use \Core\Db;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$db = new Db;
$reg = new Registry();

$registry = $db->selectOne('regprops', ' where id = ?', [intval($_POST['params'])]);


//Открываем транзакцию
$busy = $db->transactionOpen('roles', intval($_POST['params']));
$trans_id = $busy['trans_id'];

if ($busy != []) {

    $registrys = $db->getRegistry('registry');
    ?>
    <div class='pop_up drag'>
        <div class='title handle'>
            <!-- <div class='button icon move'><span class='material-icons'>drag_indicator</span></div>-->
            <div class='name'>Редактирование поля</div>
            <div class='button icon close'><span class='material-icons'>close</span></div>
        </div>
        <div class='pop_up_body'>
            <form class='ajaxFrm' id='registry_edit' onsubmit='return false'>
                <input type='hidden' name='path' value='registry/props'>
                <div class='tab-panel' id='tab_main-panel'>
                    <div class='group'>
                        <input type='hidden' name='reg_id' value="<?= $registry->id ?>">
                        <input type='hidden' name='trans_id' value="<?= $trans_id ?>">
                        <div class='item w_50 required'>
                            <div class='el_data'>
                                <label>Название &lt;label&gt;</label>
                                <input required class='el_input' type='text' name='prop_name' value="<?= $registry->name ?>">
                            </div>
                        </div>
                        <div class='item w_50 required'>
                            <div class='el_data'>
                                <label>Имя поля в базе данных на английском языке &lt;name&gt;</label>
                                <input required class='el_input' type='text' name='field_name' maxlength='60' value="<?= $registry->field_name ?>">
                            </div>
                        </div>

                        <div class='item w_50'>
                            <div class='el_data'>
                                <label>Подсказка в поле &lt;placeholder&gt;</label>
                                <input class='el_input' type='text' name='placeholder' value="<?= $registry->placeholder ?>">
                            </div>
                        </div>

                        <div class='item w_50 required'>
                            <select data-label='Тип поля' name='field_types' required='required'>
                                <option value=''>Выберите</option>
                                <?
                                foreach($reg->props_array as $key => $val){
                                    echo '<option value="'.$key.'"'.($registry->type == $key ? ' selected' : '').'>'.$val.'</option>'."\n";
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
                                <textarea class="el_textarea" name="comment"><?= str_replace('<br>', "\n", $registry->comment) ?></textarea>
                            </div>
                        </div>


                        <div class='item w_100 field_option' id='sizefield'>
                            <div class='el_data'>
                                <label>Максимальная длина значения (символов)</label>
                                <input name='size' class="el_input" type='number' id='size' value="<?= $registry->size ?>">
                            </div>
                        </div>
                        <div class='item w_100 field_option' id='minmax'>
                            <div class="item w_100">
                                <div class='el_data'>
                                    <label>Минимальное значение:</label>
                                    <input name='min_value' class='el_input' type='number' value="<?= $registry->min_value ?>">
                                </div>
                            </div>
                            <div class="item w_100">
                                <div class='el_data'>
                                    <label>Максимальное значение:</label>
                                    <input name='max_value' class='el_input' type='number' value="<?= $registry->max_value ?>">
                                </div>
                            </div>
                            <div class="item w_100">
                                <div class='el_data'>
                                    <label>Шаг:</label>
                                    <input name='step' class='el_input' type='number' id='step' value="<?= $registry->step ?>">
                                </div>
                            </div>
                        </div>
                        <div class='item w_100 field_option' id='default_value'>
                            <div class='el_data'>
                                <label>Значение по умолчанию</label>
                                <input name='default_value' class='el_input' type='text' value="<?= $registry->default_value ?>">
                            </div>
                        </div>
                        <div class='item w_100 field_option' id='default_date'>
                            <div class='el_data' style="margin-top: 30px;">
                                <label for='curr_date' style='margin-left: 30px;'>По умолчанию текущая дата</label>
                                <div class='custom_checkbox'>
                                    <label class='container'>
                                        <input type='checkbox' name='curr_date' id='curr_date' tabindex='-1' value='1'
                                            <?= ($registry->curr_date == 1 ? ' checked' : '') ?>>
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
                                        <input type='checkbox' name='curr_time' id='curr_time' tabindex='-1' value='1'
                                            <?= ($registry->curr_time == 1 ? ' checked' : '') ?>>
                                        <span class='checkmark'></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class='item w_100 field_option' id='default_datetime'>
                            <div class='el_data' style='margin-top: 30px;'>
                                <label for='curr_datetime' style='margin-left: 40px;'>По умолчанию текущие дата и
                                    время</label>
                                <div class='custom_checkbox'>
                                    <label class='container'>
                                        <input type='checkbox' name='curr_datetime' id='curr_datetime' tabindex='-1'
                                               value='1'<?= ($registry->curr_datetime == 1 ? ' checked' : '') ?>>
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
                                        <input type='radio' name='calendar_type' value='dropdown'
                                            <?= ($registry->calendar_type == 'dropdown' ? ' checked' : '') ?>>
                                        <span class="checkmark radio"></span></label>
                                </div>
                                <div class="custom_checkbox">
                                    <label class="container" style="margin-left: 160px;"> Встроенный
                                        <input type="radio" name="calendar_type" value="inline"
                                            <?= ($registry->calendar_type == 'inline' ? ' checked' : '') ?>>
                                        <span class="checkmark radio"></span></label>
                                </div>
                            </div>
                        </div>
                        <div class='item w_100 field_option' id='area'>
                            <div class='item w_100'>
                                <div class='el_data'>
                                    <label>Ширина (в символах):</label>
                                    <input name='cols' class='el_input' type='number' value="<?= $registry->cols ?>">
                                </div>
                            </div>
                            <div class='item w_100'>
                                <div class='el_data'>
                                    <label>Высота (количество строк):</label>
                                    <input name='rows' class='el_input' type='number' value="<?= $registry->rows ?>">
                                </div>
                            </div>
                        </div>
                        <?
                        $checkbox_value = json_decode($registry->checkbox_values, true)[0];
                        ?>
                        <div class='item w_100 field_option' id='check'>
                            <div class='check_button group'>
                                <div class='item w_50'>
                                    <div class='el_data'>
                                        <label>Название кнопки:</label>
                                        <input name='checkbox_label[]' class='el_input' type='text' value="<?=$checkbox_value['label']?>">
                                    </div>
                                </div>
                                <div class='item w_50'>
                                    <div class='el_data'>
                                        <label>Значение:</label>
                                        <input name='checkbox_value[]' class='el_input' type='text' value="<?=$checkbox_value['value']?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?
                        $radio_values = json_decode($registry->radio_values, true);
                        ?>
                        <div class='item w_100 field_option' id='radio'>
                            <div class='el_data'>
                                <label>Заголовок:</label>
                                <input name='radio_title' class='el_input' type='text' value="<?=$radio_values[0]['title']?>">
                            </div>
                            <div class='check_button group'>
                                <?
                                if(is_array($radio_values)){
                                    for($r = 1; $r < count($radio_values); $r++){
                                    ?>
                                    <h5 class='item w_100 check_number'>Кнопка №<?=$r?></h5>
                                    <div class='item w_50'>
                                        <div class='el_data'>
                                            <label>Название кнопки:</label>
                                            <input name='radio_label[]' class='el_input' type='text' value="<?=$radio_values[$r]['label']?>">
                                        </div>
                                    </div>
                                    <div class='item w_50'>
                                        <div class='el_data'>
                                            <label>Значение:</label>
                                            <input name='radio_value[]' class='el_input' type='text' value="<?=$radio_values[$r]['value']?>">
                                        </div>
                                    </div>
                                    <?
                                    }
                                }else{
                                    ?>
                                    <h5 class='item w_100 check_number'>Кнопка №1</h5>
                                    <div class='item w_50'>
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
                                <?
                                }
                                ?>
                            </div>
                            <button class='button icon text new_check'><span class='material-icons'>add</span>
                                Еще кнопка
                            </button>
                        </div>
                        <div class='item w_100 field_option' id='oplist'>
                            <?
                            $options = json_decode($registry->options_list, true);
                            ?>
                            <div class='option_button group'>
                                <?
                                if(is_array($options)){
                                    for($r = 1; $r < count($options); $r++){
                                        ?>
                                        <h5 class='item w_100 option_number'>Опция №<?=$r?></h5>
                                        <div class='item w_50'>
                                            <div class='el_data'>
                                                <label>Название опции:</label>
                                                <input name='option_label[]' class='el_input' type='text' value="<?=$options[$r]['label']?>">
                                            </div>
                                        </div>
                                        <div class='item w_50'>
                                            <div class='el_data'>
                                                <label>Значение:</label>
                                                <input name='option_value[]' class='el_input' type='text' value="<?=$options[$r]['value']?>">
                                            </div>
                                        </div>
                                        <?
                                    }
                                }else{
                                ?>
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
                                <?
                                }
                                ?>
                            </div>
                            <button class='button icon text new_option'><span class='material-icons'>add</span>
                                Еще опция
                            </button>
                        </div>
                        <div class='item w_100 field_option' id='fromdb'>
                            <div class='item w_100 field_option' id='from_db_view'>
                                <div class='el_data' style='margin-top:40px'>
                                    <label style='top: -50px;'>Вид списка:</label>
                                    <div class='custom_checkbox'>
                                        <label class='container'> Выпадающий список (одиночный выбор)
                                            <input type='radio' name='from_db_view' value='0'
                                                <?= ($registry->from_db_view == '0' ? ' checked' : '') ?>>
                                            <span class="checkmark radio"></span></label>
                                    </div>
                                    <div class='custom_checkbox'>
                                        <label class='container'> Выпадающий список (множественный выбор)
                                            <input type='radio' name='from_db_view' value='1'
                                                <?= ($registry->from_db_view == '1' ? ' checked' : '') ?>>
                                            <span class="checkmark radio"></span></label>
                                    </div>
                                    <div class="custom_checkbox">
                                        <label class="container" style="margin-left: 160px;"> Группа чек-кнопок
                                            <input type="radio" name="from_db_view" value="2"
                                                <?= ($registry->from_db_view == '2' ? ' checked' : '') ?>>
                                            <span class="checkmark radio"></span></label>
                                    </div>
                                    <div class='custom_checkbox'>
                                        <label class='container' style='margin-left: 160px;'> Группа радио-кнопок
                                            <input type='radio' name='from_db_view' value='3'
                                                <?= ($registry->from_db_view == '3' ? ' checked' : '') ?>>
                                            <span class="checkmark radio"></span></label>
                                    </div>
                                </div>
                            </div>
                            <div class='el_data'>
                                <select data-label='Из справочника' name='fromdb'>
                                    <option value="">&nbsp;</option>
                                    <? foreach ($registrys['array'] as $id => $name) { ?>
                                        <option value="<?= $id ?>"<?=$registry->from_db == $id ? ' selected' : ''?>><?= $name ?></option>
                                    <? } ?>
                                </select>
                            </div>
                            <div class='el_data'>
                                <input type="hidden" name="selected_field" value="<?=$registry->from_db_text?>">
                                <select data-label='Поле справочника' name='fromdb_fields'>
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
        $("select[name=field_types], select[name=fromdb]").trigger("change");
        $("#registry_edit .close").on("click", function () {
            $.post("/", {ajax: 1, action: "transaction_close", id: <?=$trans_id?>}, function () {
            })
        });
        $(window).on("beforeunload", function(){
            $.post("/", {ajax: 1, action: "transaction_close", id: <?=$trans_id?>}, function(){})
        });
    </script>
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