<?php
use \Core\Db;
use Core\Gui;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$db = new Db;
$gui = new Gui();
$reg = new Registry();
$reg_id = intval($_POST['params'][1]);
$row_id = intval($_POST['params'][0]);

$table = $db->selectOne('registry', ' where id = ?', [$reg_id]);
$registry = $db->selectOne($table->table_name, ' where id = ?', [$row_id]);


//Открываем транзакцию
$busy = $db->transactionOpen('roles', intval($_POST['params']));
$trans_id = $busy['trans_id'];

if($busy != []){

$registrys = $db->getRegistry('registry');

?>
<div class="pop_up drag" style='width: 60vw;'>
    <div class="title handle">

        <div class="name">Элемент справочника</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form class="ajaxFrm noreset" id="registry_items_edit" onsubmit="return false">
            <input type="hidden" name="reg_id" value="<?=$registry->id?>">
            <input type="hidden" name="trans_id" value="<?=$trans_id?>">
            <input type="hidden" name="parent" value="<?=intval($_POST['params'][1])?>">
            <?
            echo $reg->buildForm($reg_id, [], (array)$registry);
            ?>
            <?/*div class="group">
                <div class="item w_58">
                    <div class="el_data">
                        <label>Наименование</label>
                        <input required class="el_input" type="text" name="name" value="<?=$registry->name?>">
                    </div>
                </div>
                <div class="item w_41">
                    <select required data-label="Статус" name="active">
                        <option value="1"<?=($registry->active == 1) ? ' selected="selected"' : ''?>>
                            Активен
                        </option>
                        <option value="0"<?=($registry->active == 0) ? ' selected="selected"' : ''?>>
                            Заблокирован
                        </option>
                    </select>
                </div>
                <div class="item w_50">
                    <select data-label="Родительский справочник"
                            id="parent_registry" name="parent_registry" data-selected="<?=$registry->parent_items?>">
                        <option value="0"<?=($registry->parent == 0) ? ' selected' : ''?>>Без родителя</option>
                        <?
                        foreach($registrys['array'] as $value => $text){
                            if($value != $registry->parent){
                                $sel = ($registry->parent_registry == $value) ? ' selected' : '';
                                echo '<option value="'.$value.'"'.$sel.'>'.$text.'</option>';
                            }

                        }
                        ?>
                    </select>
                </div>
                <?
                if(intval($_POST['params'][1]) == 10){
                    ?>
                    <div class="item w_50">
                        <select required data-label="Тип" name="type">
                            <?
                            echo $gui->buildSelectFromRegistry($types['result'], [$registry->type]);
                            ?>
                        </select>
                        <div class="button icon button_registry" title="Добавить элемент справочника" data-reg="7">
                            <span class="material-icons">folder</span></div>
                    </div>
                    <?
                }
                ?>
                <div class="item w_50" id="depend_registry" style="display: none" id="depend_registry">
                    <select data-label="Элементы справочника" multiple name="parent_items[]">
                        <option value="0"<?=($registry->parent == 0) ? ' selected' : ''?>>Без родителя</option>
                        <?
                        foreach($registrys['array'] as $value => $text){
                            $sel = ($registry->parent == $value) ? ' selected' : '';
                            echo '<option value="'.$value.'"'.$sel.'>'.$text.'</option>';
                        }
                        ?>
                    </select>

                </div>
                <div class="item w_100">
                    <div class="el_data">
                        <label>Примечания</label>
                        <textarea class="el_textarea" name="comment"><?=$registry->comment?></textarea>
                    </div>
                </div>
            </div*/?>
            <div class="confirm">
                <button class="button icon text"><span class="material-icons">save</span>Сохранить</button>
                <?/*button class="button icon text"><span class="material-icons">control_point_duplicate</span>Клонировать</button>
                <button class="button icon text"><span class="material-icons">delete_forever</span>Удалить</button*/?>
            </div>
        </form>
    </div>

</div>
    <script>
        el_app.mainInit();
        el_registry.create_item_init();
        $("#parent_registry").trigger("change");
        $("#registry_items_edit .close").on("click", function(){
            $.post("/", {ajax: 1, action: "transaction_close", id: <?=$trans_id?>}, function(){})
        });
        $(window).on("beforeunload", function(){
            $.post("/", {ajax: 1, action: "transaction_close", id: <?=$trans_id?>}, function(){})
        });
    </script>
    <?php
}else{
    ?>
    <script>
        alert("Эта запись редактируется пользователем <?=$busy->user_name?>");
        el_app.dialog_close("role_edit");
    </script>
    <?
}
?>