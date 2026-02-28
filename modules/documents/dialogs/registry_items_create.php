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
<style>
    .agreement_list_group, .upload_link, #typical_doc .item{
        display: none;
    }
    #typical_doc .group .item:nth-of-type(1),
    #typical_doc .group .item:nth-of-type(2),
    .agreement_list_group .item:not(.new_signer){
        display: flex;
    }

</style>
<div class="pop_up drag" style="width: 60vw;">
    <div class="title handle">
        <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
        <div class="name">Новый документ</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form class="ajaxFrm noreset" id="registry_items_create" onsubmit="return false">
            <div id="typical_doc">
            <?
            echo $reg->buildForm(66);
            ?>
            </div>

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
<script src='/js/assets/agreement_list.js'></script>
<script>

    $(document).ready(function(){
        el_app.mainInit();
        el_registry.create_item_init();
        $(".el_input[name=name]").focus();

        agreement_list.agreement_list_init();

        $('[name="agreementtemplate"], [name="initiator"], [name="initiation"], [name="brief"]')
            .closest('.item').prependTo('.agreement_list_group');


        $("#registry_items_create [name='documentacial']").off("change").on("change", function(){
            let val = $(this).val(),
                $switcher = $('#typical_doc .item:nth-of-type(1),' +
                    '#typical_doc .item:nth-of-type(2)'),
                $typical_doc = $("#typical_doc .item"),
                $agreement_group = $('.agreement_list_group'),
                $agreement_list = $('.agreement_list_group .item:not(.new_signer)');
            $switcher.css('display', 'flex');
            switch(parseInt(val)){
                case 0:
                    $typical_doc.hide();
                    $agreement_group.hide();
                    $agreement_list.hide();
                    $switcher.css('display', 'flex');
                    break;
                case 6:
                    $typical_doc.hide();
                    $agreement_group.css('display', 'flex');
                    $agreement_list.css('display', 'flex');
                    $switcher.css('display', 'flex');
                    break;
                default:
                    $typical_doc.css('display', 'flex');
                    $agreement_group.hide();
                    $agreement_list.hide();
                    $switcher.css('display', 'flex');
                    break;
            }
            $.post('/', {ajax: 1, action: 'getTemplateByDocType', docType: val}, function (data) {
                $("#registry_items_create [name='document']").html(data).trigger('chosen:updated');
            });
        });

        $("select[name=agreementtemplate]").off("change").on("change", function(){
            $.post("/", {ajax: 1, action: "getDocTemplate", temp_id: $(this).val()}, function(data){
                let answer = JSON.parse(data),
                    agreementlist = JSON.parse(answer.agreementlist);
                $('[name="agreementtemplate"], [name="initiator"]').chosen('destroy');
                let $preField = $('[name="agreementtemplate"], [name="initiator"], [name="initiation"], [name="brief"]')
                    .closest('.item');
                $.post("/", {ajax: 1, action: "buildAgreement", agreementlist: answer.agreementlist}, function(data){
                    $(".agreement_list_group").html(data);
                    $preField.prependTo('.agreement_list_group');
                    $('[name="agreementtemplate"], [name="initiator"]').chosen({
                        search_contains: true,
                        no_results_text: 'Ничего не найдено.'
                    });
                    $('[name=brief]').val(answer.brief);
                    $('[name=initiator]').val(answer.initiator).trigger('chosen:updated');
                    $('.agreement_list_group .item:not(.new_signer)').css('display', 'flex');
                    el_app.mainInit();
                    agreement_list.agreement_list_init();
                });
            });
        });
    });
</script>