<?php
use \Core\Db;
use Core\Gui;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'].'/core/connect.php';
$db = new Db;
$gui = new Gui();
$reg = new Registry();

$checks_type = $db->getRegistry('checks');
$subject_type = $db->getRegistry('subject');
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
        <div class="name">Импорт плана проверок из Excel</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form class="ajaxFrm noreset" id="plan_import" onsubmit="return false">
            <div class="group">
                <div class="item w_50 required">
                    <div class='el_data'>
                        <label>Строка с полным наименованием плана</label>
                        <input required class='el_input' type='number' name='plan_name' value="9">
                    </div>
                </div>
                <div class='item w_50 required'>
                    <div class='el_data'>
                        <label>Таблица начинается со строки</label>
                        <input required class='el_input' type='number' name='table_begin' value='13'>
                    </div>
                </div>
                <?/*div class='item w_50 required'>
                    <select required data-label='Тип проверок' name='checks_type'>
                        <?
                        echo $gui->buildSelectFromRegistry($checks_type['result']);
                        ?>
                    </select>
                </div>
                <div class='item w_50 required'>
                    <select required data-label='Предмет проверок' name='subject_type'>
                        <?
                        echo $gui->buildSelectFromRegistry($subject_type['result']);
                        ?>
                    </select>
                </div*/?>
            </div>
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
                <input type='file' accept='.xlsx' name='file' id="selectFileXLS" style="display: none">
                <button class='button icon text' id="selectXLSbtn">
                    <span class='material-icons'>upload</span>Выбрать файл XLSX
                </button>
            </div>

            <div class="confirm" id="importButton">
                <button class="button icon text" disabled="disabled" id="importComplete"><span class="material-icons">save</span>Импорт</button>
            </div>
        </form>
    </div>
</div>
<!--script src='/modules/registry/js/registry.js'></script-->
<script>
    $(document).ready(function(){
        el_app.mainInit();
        $('#selectXLSbtn').off('click').on('click', function (e) {
            e.preventDefault();
            $('#selectFileXLS').trigger('click');
        });
        $('#selectFileXLS').on('change', function (event) {
            const file = event.target.files[0];
            if (file) {
                $('#uploadStatus').html('Выбран файл: &laquo;' + file.name + '&raquo;');
                el_registry.showLoadingIndicator();
                el_registry.uploadFile(file).then(r => console.log(r));
            }
        });
    });
</script>