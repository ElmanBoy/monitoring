<?php

use \Core\Db;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$db = new Db;
$reg = new Registry();

$registry = $db->getRegistry('registry');
?>
<div class="pop_up drag" style='width: 60vw;'>
    <div class="title handle">
        <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
        <div class="name">Создать блок чек-листа</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form class="ajaxFrm" id="block_create" onsubmit="return false">
            <input type="hidden" name="path" value="checklists/items">
            <div class='tab-panel' id='tab_main-panel'>
                <div class="group">
                    <div class="item w_50 required">
                        <div class='el_data'>
                            <label>Название &lt;label&gt;</label>
                            <input required class='el_input' type='text' name='block_name'>
                        </div>
                    </div>
                    <div class='item w_50 required'>
                        <div class='el_data'>
                            <label>Имя поля в базе данных на английском языке &lt;name&gt;</label>
                            <input required class='el_input' type='text' name='field_name' maxlength='60'>
                        </div>
                    </div>



                    <div class='item w_100'>
                        <div class='el_data'>
                            <label>Примечания</label>
                            <textarea class='el_textarea' name='comment'></textarea>
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
