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
<div class="pop_up drag" style='width: 65vw;'>
    <div class="title handle">

        <div class="name">Редактировать шаблон документа &laquo;<?=$registry->name?>&raquo;</div>
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
            </div>
        </form>
    </div>

</div>
    <script>

        $(window).on("beforeunload", function(){
            $.post("/", {ajax: 1, action: "transaction_close", id: <?=$trans_id?>}, function(){})
        });

        $(document).ready(function(){
            el_app.mainInit();
            el_registry.create_item_init();
            $('#parent_registry').trigger('change');
            $('#registry_items_edit .close').on('click', function () {
                $.post('/', {ajax: 1, action: 'transaction_close', id: <?=$trans_id?>}, function () {
                })
            });
            agreement_list.agreement_list_init();
            let $registry_items = $('#registry_items_edit .item'),
                $registry_agreement_list_group = $('#registry_items_edit .agreement_list_group .item, ' +
                '#registry_items_edit .agreement_list_group .institutions');
            <?
                if($registry->documentacial == 6){
                    ?>
            $registry_items.hide();
            $('#registry_items_edit .item:nth-of-type(1),' +
                '#registry_items_edit .item:nth-of-type(2), ' +
                '#registry_items_edit .item:nth-child(n+10)').show();
            $registry_agreement_list_group.show();
            $(".pop_up_body [name='approve_types[]']").closest('.item').hide();
            <?
                }else{
            ?>
            $registry_items.hide();
            $('#registry_items_edit .item:nth-of-type(1),' +
                '#registry_items_edit .item:nth-of-type(2), ' +
                '#registry_items_edit .item:nth-child(-n+9)').show();
            $registry_agreement_list_group.hide();
            <?
            }
                ?>

            $("#registry_items_edit [name='documentacial']").off('change').on('change', function () {
                let val = $(this).val();
                switch (parseInt(val)) {
                    case 6:
                        $registry_items.hide();
                        $('#registry_items_edit .item:nth-of-type(1),' +
                            '#registry_items_edit .item:nth-of-type(2), ' +
                            '#registry_items_edit .item:nth-child(n+10)').show();
                        $registry_agreement_list_group.show();
                        $(".pop_up_body [name='approve_types[]']").closest('.item').hide();
                        break;
                    default:
                        $registry_items.hide();
                        $('#registry_items_edit .item:nth-of-type(1),' +
                            '#registry_items_edit .item:nth-of-type(2), ' +
                            '#registry_items_edit .item:nth-child(-n+9)').show();
                        $registry_agreement_list_group.hide();
                        break;
                }
            });
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