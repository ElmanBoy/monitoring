<?php

use \Core\Db;
use Core\Gui;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$db = new Db;
$gui = new Gui();
$reg = new Registry();
$reg_id = 38;
$row_id = isset($_POST['params']['plan_id']) ? intval($_POST['params']['plan_id']) : intval($_POST['params']);

$table = $db->selectOne('registry', ' where id = ?', [$reg_id]);
$registry = $db->selectOne($table->table_name, ' where id = ?', [$row_id]);
$plans = $db->selectOne('checksplans', ' ORDER BY id DESC LIMIT 1');
$doc = $db->selectOne('agreement', " WHERE source_table = 'checksplans' AND source_id = ? ORDER BY id DESC LIMIT 1", [$row_id]);

// Загружаем данные об учреждениях для этого плана с названиями
$checksQuery = "
    SELECT ci.*, i.short as institution_name
    FROM " . TBL_PREFIX . "checkinstitutions ci
    LEFT JOIN " . TBL_PREFIX . "institutions i ON i.id = ci.institution
    WHERE ci.plan_uid = ? AND ci.plan_version = ?
";
$checks = R::getAll($checksQuery, [trim($registry->uid), intval($registry->version)]);


//Открываем транзакцию
$busy = $db->transactionOpen('roles', intval($_POST['params']));
$trans_id = $busy['trans_id'];

$addinstitution = json_decode($registry->addinstitution, true);

if ($busy != []) {

    ?>
    <div class="pop_up drag" style="width: 60vw;">
        <div class="title handle">

            <div class="name"><?= ($registry->active == 0 && $registry->approved == 0) ?
                    'Редактирование плана &laquo;'.$registry->short.'&raquo;' : 'Создание новой версии плана' ?></div>
            <div class="button icon close"><span class="material-icons">close</span></div>
        </div>
        <div class="pop_up_body">
            <form class="ajaxFrm noreset" id="registry_edit" onsubmit="return false">
                <input type="hidden" name="reg_id" value="<?= $registry->id ?>">
                <input type='hidden' name='path' value="plans">
                <input type='hidden' name='documentacial' value='3'>
                <input type="hidden" name="trans_id" value="<?= $trans_id ?>">
                <input type="hidden" name="parent" value="<?= intval($_POST['params'][1]) ?>">
                <input type='hidden' name='uid' value="<?= $registry->uid ?>">
                <ul class='tab-pane'>
                    <li id='tab_plan' class='active'>План</li>
                    <li id='tab_agreement'>Согласование</li>
                    <li id='tab_preview' style="display: none">Предпросмотр</li>
                </ul>
                <div class='plan_block tab-panel' id='tab_plan-panel'>
                    <?= $reg->buildForm(38, [], (array)$registry); ?>
                    <div class='group'>
                    <?
                    echo $reg->renderFileInput([], ['document_id' => $registry->id], 'edit');
                    echo $reg->showTaskLog($registry->id, 'plans', 'registry_edit');
                    ?>
                    </div>
                </div>
                <div class='agreement_block tab-panel' id='tab_agreement-panel' style='display: none'>
                    <div class="group"><h3 class="item"><strong>ЛИСТ СОГЛАСОВАНИЯ</strong></h3></div>
                    <?= $reg->buildForm(67, [], (array)$doc); ?>
                </div>
                <div class='preview_block tab-panel' id='tab_preview-panel' style='display: none'>
                    <iframe id='pdf-viewer' width='100%' height='600px'></iframe>
                </div>
                <div class="confirm">
                    <button class='button icon text red' id='clear_form' title='Очистить форму'><span
                                class='material-icons'>cleaning_services</span>Очистить
                    </button>
                    <button class='button icon text' id='save_plan_template' title='Сохранить план как шаблон'><span
                                class='material-icons'>library_books</span>Сохранить как шаблон
                    </button>

                    <button class="button icon text save" id='save_doc'><span
                                class="material-icons">save</span><?= ($registry->active == 0 && $registry->approved == 0) ? 'Сохранить' : 'Создать новую версию' ?>
                    </button>
                    <?/*button class="button icon text"><span class="material-icons">control_point_duplicate</span>Клонировать</button>
                <button class="button icon text"><span class="material-icons">delete_forever</span>Удалить</button*/
                    ?>
                </div>
            </form>
        </div>

    </div>
    <script src='/js/assets/agreement_list.js'></script>
    <script src='/modules/plans/js/registry.js'></script>
    <script>
        // Данные об учреждениях для загрузки в форму
        window.existingChecks = <?= json_encode($checks) ?>;

        $(document).ready(function(){
            el_app.mainInit();
            el_plans_registry.create_init();
            agreement_list.agreement_list_init();
            $('[name=initiator]').val("<?=$_SESSION['user_id']?>").trigger('chosen:updated');

            // Загружаем существующие учреждения в форму
            if (window.existingChecks && window.existingChecks.length > 0) {
                var existingChecks = window.existingChecks;
                // Удаляем пустой первый блок, если он есть
                let $firstInstitution = $(".pop_up_body .institutions:first");
                if ($firstInstitution.find("[name='institutions[]']").val() == "0" ||
                    $firstInstitution.find("[name='institutions[]']").val() == "") {
                    $firstInstitution.remove();
                }

                // Добавляем каждое учреждение
                for (let i = 0; i < existingChecks.length; i++) {
                    let check = existingChecks[i];

                    if (i > 0) {
                        // Клонируем блок для всех кроме первого
                        el_plans_registry.cloneInstitution(true, false);
                    }

                    let $currentInstitution = $(".pop_up_body .institutions").eq(i);

                    // Устанавливаем значения
                    setTimeout(function() {
                        let $select = $currentInstitution.find("[name='institutions[]']");

                        // Проверяем, есть ли в селекте нужная опция
                        if (check.institution && $select.find('option[value="' + check.institution + '"]').length === 0) {
                            // Если опции нет - добавляем её (получаем название из скрытого поля или загружаем через AJAX)
                            let institutionName = check.institution_name || 'ID: ' + check.institution;
                            $select.append('<option value="' + check.institution + '">' + institutionName + '</option>');
                        }

                        $select.val(check.institution).trigger("chosen:updated");
                        $currentInstitution.find("[name='check_types[]']").val(check.check_types).trigger("chosen:updated");

                        // Для inspections может быть JSON-массив
                        if (check.inspections) {
                            try {
                                let inspectionsValue = typeof check.inspections === 'string' ? JSON.parse(check.inspections) : check.inspections;
                                $currentInstitution.find("[name='inspections[]']").val(inspectionsValue).trigger("chosen:updated");
                            } catch(e) {
                                $currentInstitution.find("[name='inspections[]']").val(check.inspections).trigger("chosen:updated");
                            }
                        }

                        // Устанавливаем период проверки
                        if (check.check_periods_start && check.check_periods_end) {
                            let periodValue = check.check_periods_start + ' - ' + check.check_periods_end;
                            $currentInstitution.find("[name='check_periods[]']").val(periodValue);
                            // Обновляем alt-input flatpickr
                            let flatpickrInstance = $currentInstitution.find("[name='check_periods[]']")[0]._flatpickr;
                            if (flatpickrInstance) {
                                flatpickrInstance.setDate([check.check_periods_start, check.check_periods_end]);
                            }
                        }
                    }, 300 * (i + 1));
                }
            }

            $('select[name=agreementtemplate]').off('change').on('change', function () {
                let val = parseInt($(this).val());
                if (val === 0) {
                    el_app.clearAgreement();
                } else {
                    $.post('/', {ajax: 1, action: 'getDocTemplate', temp_id: $(this).val()}, function (data) {
                        let answer = JSON.parse(data);
                        if (typeof answer == 'object' && answer != null) {
                            el_app.clearAgreement();
                            let agreementlist = JSON.parse(answer.agreementlist);
                            $('[name=brief]').val(answer.brief);
                            $('[name=initiator]').val(answer.initiator).trigger('chosen:updated');
                            $.post('/', {
                                ajax: 1,
                                action: 'buildAgreement',
                                agreementlist: answer.agreementlist
                            }, function (data) {
                                $('.agreement_list_group').html(data);
                                el_app.mainInit();
                                agreement_list.agreement_list_init();
                            });
                        } else {
                            alert('Шаблон пуст');
                        }
                    });
                }
            });

            $('[name=doc_number]').attr('readonly', true);


            $('#save_doc').off("click").on('click', async function (event) {
                event.preventDefault();
                let $form = $(this).closest('form'),
                    isAvailable = await el_plans_registry.check_institution_availability($('.institutions'), 'confirm');
                console.log(isAvailable)
                if (!isAvailable) {
                    $form.addClass("rejected");
                    event.preventDefault();
                    return false;
                } else {
                    $form.removeClass('rejected');
                    $('form#registry_edit').trigger('submit');
                }
            });

            $('#registry_edit .close, #registry_edit #close').on('click', function () {
                $.post('/', {ajax: 1, action: 'transaction_close', id: <?=$trans_id?>});
                $.post('/', {ajax: 1, action: 'task_close', task_id: <?=$registry->id?>,
                    module: 'plans', form_id: 'registry_edit', log_action: 'Закрытие окна плана'});
            });
            $(window).on("beforeunload", function () {
                $.post("/", {ajax: 1, action: "transaction_close", id: <?=$trans_id?>});
                $.post('/', {ajax: 1, action: 'task_close', task_id: <?=$registry->id?>,
                    module: 'plans', form_id: 'registry_edit', log_action: 'Закрытие окна плана'});
            });

            let $document = $('[name=document]'),
                $tab_preview = $('#tab_preview');
            $document.on("change", function(){
                if($(this).val() > 0){
                    $tab_preview.show();
                }else{
                    $tab_preview.hide();
                }
            });
            if($document.val() > 0){
                $tab_preview.show();
            }
            $tab_preview.on("click", function(){
                let formData = $('form#registry_edit').serialize();
                $('.preloader').fadeIn('fast');
                $.post("/", {ajax: 1, action: "planPdf", data: formData}, function(data){
                    if(data.length > 0){
                        $('#pdf-viewer').attr("src", "data:application/pdf;base64," + data);
                        $('.preloader').fadeOut('fast');
                    }
                })
            });

            $('[name="check_periods[]"] ~ input').mask('99.99.9999 - 99.99.9999');

            $("#tab_plan-panel > .group").nestedSortable({
                axis: 'y',
                cursor: 'grabbing',
                listType: 'div',
                handle: '.drag_handler',
                items: '.institutions',
                stop: function (event, ui) {
                    el_plans_registry.setItemsNumbers($('.pop_up_body .institutions'), 'Учреждение');
                }
            });

            el_plans_registry.check_institution_availability($('.institutions'));

        });

    </script>
    <?php
    $reg->insertTaskLog($registry->id, 'План открыт для редактирования', 'plans', 'registry_edit');
} else {
    ?>
    <script>
        alert("Эта запись редактируется пользователем <?=$busy['user_name']?>");
        el_app.dialog_close("role_edit");


    </script>
    <?
}
?>