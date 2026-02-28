<?php
/*
use \Core\Gui;*/
use \Core\Db;
use \Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$db = new Db;
/*$gui = new Gui;
$reg = new Registry();*/
/*$roles = $db->getRegistry('roles');
$props = $db->getRegistry('regprops', 'ORDER BY id DESC', [], ['id', 'name', 'comment', 'label']);
$regs = $db->getRegistry('registry');
$plans = $db->selectOne('checksplans', ' ORDER BY doc_number DESC LIMIT 1');
$new_plan_number = '';
$new_plan_num = 1000;
if(strlen($plans->doc_number) > 0) {
    $plan_number = $plans->doc_number;
    $plan_numberArr = explode('-', $plan_number);
    if($plan_numberArr[1] == date('Y')) {
        $new_plan_num = intval(str_replace('ПЛП', '', $plan_numberArr[0])) + 1;
    }
}
$new_plan_number = 'ПЛП' . $new_plan_num . '-' . date('Y');*/
$reg = new Registry();
?>
<div class="pop_up drag" style='width: 60vw;'>
    <div class="title handle">
        <!-- <div class="button icon move"><span class="material-icons">drag_indicator</span></div>-->
        <div class="name">Создать план проверок</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form class="ajaxFrm" id="registry_create" onsubmit="return false">
            <input type='hidden' name='documentacial' value='3'>
            <ul class='tab-pane' style=''>
                <li id='tab_plan' class='active'>План</li>
                <li id='tab_agreement'>Согласование</li>
                <li id='tab_preview' style='display: none'>Предпросмотр</li>
            </ul>
            <div class="plan_block tab-panel" id='tab_plan-panel'>
                <?=$reg->buildForm(38);?>
                <div class='group'>
                    <?
                    echo $reg->renderFileInput([], ['document_id' => 0], 'edit');
                    ?>
                </div>
            </div>
            <div class="agreement_block tab-panel" id='tab_agreement-panel' style="display: none">
                <div class="group"><h3 class="item"><strong>ЛИСТ СОГЛАСОВАНИЯ</strong></h3></div>
                <?=$reg->buildForm(67);?>

            </div>
            <div class='preview_block tab-panel' id='tab_preview-panel' style='display: none'>
                <iframe id='pdf-viewer' width='100%' height='600px'></iframe>
            </div>
            <div class="confirm">
                <button class='button icon text red' id='clear_form' title="Очистить форму"><span class='material-icons'>cleaning_services</span>Очистить</button>
                <button class='button icon text' id="save_plan_template" title='Сохранить план как шаблон'><span class='material-icons'>library_books</span>Сохранить как шаблон</button>
                <button class="button icon text save disabled" id="save_doc" style="display: none"><span class="material-icons">save</span>Создать план</button>
                <button class='button icon text green' title='Составить лист согласования' id="agreement_open"><span class='material-icons'>verified</span>Согласование</button>
            </div>
        </form>
    </div>

</div>
<script src="/js/assets/agreement_list.js"></script>
<script>
    $(document).ready(function(){
        el_app.mainInit();
        el_registry.create_init();
        agreement_list.agreement_list_init();

        function save_doc_available(){
            let $save_doc = $('#save_doc');
            $('.sections.signers ruby ~ select.role').removeClass("error");
            if(!check_doc_available(true)){
                $save_doc.addClass('disabled');
            }else {
                $save_doc.removeClass('disabled');
            }
        }

        function check_doc_available(silent = false) {
            let $signers = $('.sections.signers ruby'),
                $roles = $('.sections.signers ruby ~ select.role'),
                errors = 0;
            $roles.removeClass('error');
            if ($signers.length < 2) {
                if(!silent)
                    alert('Назначьте подписантов с указанием их роли (&laquo;Утверждает&raquo; или &laquo;Подписывает&raquo;).');
                return false;
            } else if ($signers.length >= 2) {
                if ($signers.length !== 2){
                    if(!silent)
                        alert('Подписантов должно быть двое.');
                }
                for (let i = 0; i < $roles.length; i++) {
                    if ($($roles[i]).val() === '') {
                        $($roles[i]).addClass('error');
                        if(!silent)
                            alert('Укажите роли подписантов.');
                        errors++;
                    }
                }
                return errors === 0;
            }
            return true;
        }
        $('[name=initiator]').val("<?=$_SESSION['user_id']?>").trigger('chosen:updated');

        $('select[name=agreementtemplate]').off('change').on('change', function () {
            let temp_id = parseInt($(this).val());
            if(temp_id > 0) {
                $.post('/', {ajax: 1, action: 'getDocTemplate', temp_id: $(this).val()}, function (data) {
                    if(data.length > 0) {
                        let answer = JSON.parse(data),
                            agreementlist = JSON.parse(answer.agreementlist);
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
                    }
                });
            }
        });

        $("#agreement_open").off("click").on("click", function(e){
            e.preventDefault();
            $("#tab_agreement").trigger("click");
            $(this).hide();
            $('#save_doc').css("display", "flex");
            save_doc_available();
        });

        $("#tab_agreement, button.new_signer").on("click", function(){
            save_doc_available();
        });
        $("button.new_signer").on("mouseup keyup", function () {
            setTimeout(function(){save_doc_available()}, 300);
        });

        $('form#registry_create').on('change input', 'input, textarea, select', function(e) {
            save_doc_available();
        });

        $("#save_doc").on("mousedown keypress", function(){
            check_doc_available();
        });

        $('#save_doc').off('click').on('click', async function (event) {
            event.preventDefault();
            let $form = $(this).closest('form'),
                isAvailable = await el_registry.check_institution_availability($('.institutions'), 'confirm');
            console.log(isAvailable)
            if (!isAvailable) {
                $form.addClass('rejected');
                event.preventDefault();
                return false;
            } else {
                $form.removeClass('rejected');
                $('form#registry_create').trigger('submit');
            }
        });

        $('[name=doc_number]').attr('readonly', true);

        let $document = $('[name=document]'),
            $tab_preview = $('#tab_preview');
        $document.on('change', function () {
            if ($(this).val() > 0) {
                $tab_preview.show();
            } else {
                $tab_preview.hide();
            }
        });
        if ($document.val() > 0) {
            $tab_preview.show();
        }
        $tab_preview.on('click', function () {
            let formData = $('form#registry_create').serialize();
            $('.preloader').fadeIn('fast');
            $.post('/', {ajax: 1, action: 'planPdf', data: formData}, function (data) {
                if (data.length > 0) {
                    $('#pdf-viewer').attr('src', 'data:application/pdf;base64,' + data);
                    $('.preloader').fadeOut('fast');
                }
            })
        });

        $('[name="check_periods[]"] ~ input').mask('99.99.9999 - 99.99.9999');

    });
</script>