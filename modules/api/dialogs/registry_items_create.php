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
<div class="pop_up drag">
    <div class="title handle">
        <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
        <div class="name">Новый элемент справочника &laquo;<?=$parent->name?>&raquo;</div>
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
    </div>
</div>
<!--script src='/modules/registry/js/registry.js'></script-->
<script>
    $(document).ready(function(){
        el_app.mainInit();
        el_registry.create_item_init();
        $(".el_input[name=name]").focus();
    });
</script>