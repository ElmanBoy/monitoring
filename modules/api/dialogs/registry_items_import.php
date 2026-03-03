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
    #loading-indicator {
        display: none;
        margin-top: 20px;
        min-width: 100%;
    }

    .loading-text {
        font-size: 16px;
        color: #333;
    }
    .progress-bar{
        height: 3px;
        background: var(--blue);
    }
    #result{
        min-width: 100%;
        text-align: center;
    }
    #importButton{
        display: none;
    }
    #importFields .w_50 {
        border-bottom: 1px solid var(--color_06);
        height: 90px;
    }
</style>
<div class="pop_up drag" style='width: 60vw;'>
    <div class="title handle">
        <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
        <div class="name">Импорт элементов справочника &laquo;<?=$parent->name?>&raquo;</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form class="ajaxFrm noreset" id="registry_items_import" onsubmit="return false">
            <input type="hidden" name="reg_id" value="<?=intval($_POST['params'])?>">
            <input type='hidden' name='parent' value="<?= $_POST['params'] ?>">
            <div class='group' id="importFields">

            </div>
            <div class='group'>
                <div id='loading-indicator'>
                    <div class='loading-text'>Загрузка файла...</div>
                    <div class='progress'>
                        <div class='progress-bar' role='progressbar' aria-valuenow='0' aria-valuemin='0' aria-valuemax='100'
                             style='width: 0%;'></div>
                    </div>
                </div>
                <div id='result'></div>
            </div>
            <div class='confirm' id="uploadButton">
                <input type='file' accept='.csv' name='file' id="selectFileCSV" style="display: none">
                <button class='button icon text selectFile'>
                    <span class='material-icons'>upload</span>Выбрать файл CSV
                </button>
            </div>

            <?php
            if(isset($_POST['module'])){
                echo '<input type="hidden" name="path" value="registry">';
            }
            ?>

            <div class="confirm" id="importButton">
                <button class="button icon text" disabled="disabled"><span class="material-icons">save</span>Импорт</button>
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