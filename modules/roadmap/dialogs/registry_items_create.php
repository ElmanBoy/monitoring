<?php
use \Core\Db;
use Core\Gui;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$db = new Db;
$gui = new Gui();
$reg = new Registry();

$registrys = $db->getRegistry('registry');
$types = $db->getRegistry('registryitems', ' where parent = 7');

$parent = $db->selectOne('registry', ' where id = ?', [$_POST['params']]);
?>
<div class="pop_up drag" style='width: 60vw;'>
    <div class="title handle">
        <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
        <div class="name">Новый документ</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form class="ajaxFrm noreset" id="registry_items_create" onsubmit="return false">
            <?
            echo $reg->buildForm($_POST['params']);
            ?>

            <?php
            if(isset($_POST['module'])){
                echo '<input type="hidden" name="path" value="registry">';
            }
            ?>
            <input type="hidden" name="parent" value="<?=$_POST['params']?>">
            <div class="confirm">
                <button class="button icon text"><span class="material-icons">save</span>Сохранить</button>
            </div>
        </form>
        <div style="height: 200px"></div>
    </div>
</div>

<script>
    $('#registry_items_create .item:nth-child(n+3), #registry_items_create .institutions').hide();
    $(document).ready(function(){
        el_app.mainInit();
        el_registry.create_item_init();
        $(".el_input[name=name]").focus();

        agreement_list.agreement_list_init();


        $("#registry_items_create [name='documentacial']").off("change").on("change", function(){
            let val = $(this).val(),
                $items = $('#registry_items_create .item'),
                $agreement_list_group = $('#registry_items_create .agreement_list_group .item, ' +
                    '#registry_items_create .agreement_list_group .institutions');
            switch(parseInt(val)){
                case 6:
                    $items.hide();
                    $('#registry_items_create .item:nth-of-type(1),' +
                       '#registry_items_create .item:nth-of-type(2), ' +
                        '#registry_items_create .item:nth-child(n+12)').show();
                    $agreement_list_group.show();
                    $(".pop_up_body [name='approve_types[]']").closest('.item').hide();
                    break;
                default:
                    $items.hide();
                    $('#registry_items_create .item:nth-of-type(1),' +
                        '#registry_items_create .item:nth-of-type(2), ' +
                        '#registry_items_create .item:nth-child(-n+11)').show();
                    $agreement_list_group.hide();
                    break;
            }
        });

        $("select[name=agreementtemplate]").off("change").on("change", function(){
            $.post("/", {ajax: 1, action: "getDocTemplate", temp_id: $(this).val()}, function(data){
                let answer = JSON.parse(data),
                    agreementlist = JSON.parse(answer.agreementlist);
                $("[name=brief]").val(answer.brief);
                $("[name=initiator]").val(answer.initiator).trigger('chosen:updated');
                $.post("/", {ajax: 1, action: "buildAgreement", agreementlist: answer.agreementlist}, function(data){
                    $(".agreement_list_group").html(data);
                    el_app.mainInit();
                    agreement_list.agreement_list_init();
                });
            });
        });
    });
</script>