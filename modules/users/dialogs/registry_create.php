<?php
/*
use \Core\Gui;
use \Core\Db;*/
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
/*$db = new Db;
$gui = new Gui;
$reg = new Registry();*/
/*$roles = $db->getRegistry('roles');
$props = $db->getRegistry('regprops', 'ORDER BY id DESC', [], ['id', 'name', 'comment', 'label']);
$regs = $db->getRegistry('registry');*/
$reg = new Registry();
?>
<div class="pop_up drag" style='width: 60vw;'>
    <div class="title handle">
        <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
        <div class="name">Создать пользователя</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form class="ajaxFrm" id="registry_create" onsubmit="return false">
            <?=$reg->buildForm(40);?>
            <div class="confirm">

                <button class="button icon text"><span class="material-icons">save</span>Сохранить</button>

            </div>
        </form>
    </div>

</div>
<script>
    $(document).ready(function(){
        el_app.mainInit();
        el_registry.create_init();
        el_users.create_init();
    });
</script>