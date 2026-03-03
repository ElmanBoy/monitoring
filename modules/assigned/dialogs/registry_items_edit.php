<?php
use \Core\Db;
use Core\Gui;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$db = new Db;
$gui = new Gui();
$reg = new Registry();
$reg_id = 48;
$row_id = intval($_POST['params'][0]);

$table = $db->selectOne('registry', ' where id = ?', [$reg_id]);
$registry = $db->selectOne($table->table_name, ' where id = ?', [$row_id]);


//Открываем транзакцию
$busy = $db->transactionOpen('roles', intval($_POST['params']));
$trans_id = $busy['trans_id'];

if($busy != []){

$registrys = $db->getRegistry('registry');

?>
<div class="pop_up drag">
    <div class="title handle">

        <div class="name">Редактирование задачи &laquo;<?=trim($registry->name)?>&raquo;</div>
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
        el_registry.create_init();
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