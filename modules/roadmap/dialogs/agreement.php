<?php

use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Date;
use Core\Templates;
use Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$gui  = new Gui;
$db   = new Db;
$auth = new Auth();
$temp = new Templates();
$date = new Date();
$reg  = new Registry();

$html       = '';
$user_signs = [];
$docId      = intval($_POST['params']['docId']);
$is_object  = $auth->haveUserRole(5);

$tmpl  = $db->selectOne('agreement', ' WHERE id = ?', [$docId]);
$signs = $db->select('signs', " WHERE table_name = 'agreement' AND doc_id = ?", [$docId]);

if (count($signs) > 0) {
    foreach ($signs as $s) {
        $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
    }
}

$users         = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name', 'position']);
$urgent_types  = $db->getRegistry('urgent_types');

$initiator_fio      = ($users['array'][$tmpl->initiator][0] ?? '') . ' ' .
    ($users['array'][$tmpl->initiator][1] ?? '') . ' ' .
    ($users['array'][$tmpl->initiator][2] ?? '');
$initiator_position = $users['array'][$tmpl->initiator][3] ?? '';

$agreementlist = json_decode($tmpl->agreementlist ?? '[]', true) ?: [];

// Если это акт или документ с листом согласования — строим таблицу
$html .= '<div style="margin-bottom:10px">
    <strong>' . htmlspecialchars($tmpl->name) . '</strong><br>
    <small>Инициатор: ' . $initiator_fio . ' ' . $initiator_position . '</small>
</div>';

if (is_array($agreementlist) && count($agreementlist) > 0 && isset($agreementlist[0][0]['list_type'])) {
    // Есть полноценный лист согласования
    $list_types = [];
    foreach ($agreementlist as $section) {
        if (isset($section[0]['list_type']) && !in_array($section[0]['list_type'], $list_types)) {
            $list_types[] = $section[0]['list_type'];
        }
    }

    $html .= '<div id="agreement_block">';
    $html .= '<div class="agreement_list"><h4>ЛИСТ СОГЛАСОВАНИЯ</h4>
        <div class="list_type">Тип согласования: <strong>' .
        (count($list_types) > 1 ? 'смешанное' :
            ($list_types[0] == '1' ? 'последовательное' : 'параллельное')) .
        '</strong></div>
        <table class="agreement-table">
        <thead><tr>
            <th>№</th>
            <th style="width:30%">ФИО</th>
            <th>Срок согласования</th>
            <th style="width:30%">Результат согласования</th>
            <th>Комментарии</th>
        </tr></thead>';

    for ($i = 0; $i < count($agreementlist); $i++) {
        $itemArr = $agreementlist[$i];
        if (!is_array($itemArr) || count($itemArr) === 0) continue;

        $stageComplete = true;
        foreach (array_slice($itemArr, 1) as $item) {
            if (empty($item['result']['id']) || !in_array(intval($item['result']['id']), [1, 2, 3])) {
                $stageComplete = false;
                break;
            }
        }

        $html .= '<tbody' . ($stageComplete ? '' : ' class="notComplete"') . '>
            <tr><td class="divider" colspan="5">' .
            (isset($itemArr[0]['stage']) && intval($itemArr[0]['stage']) > 0
                ? '<strong>Этап ' . $itemArr[0]['stage'] . '</strong><br>' : '') .
            'Тип согласования: <strong>' .
            (isset($itemArr[0]['list_type']) && $itemArr[0]['list_type'] == '1'
                ? 'последовательное' : 'параллельное') . '</strong>' .
            '<input type="hidden" name="addAgreement" id="ag' . $i . '" value=\'' .
            json_encode($itemArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '\'></td></tr>';

        $html .= $reg->buildAgreementList($itemArr, $i, $users, $urgent_types, $user_signs, $reg);
        $html .= '</tbody>';
    }

    $html .= '</table></div></div>';
}

// Блок возражений — только для ОК (role=5), только для актов
if ($is_object && intval($tmpl->documentacial) === 2) {
    // Берём учреждение из акта
    $insId = intval($tmpl->ins_id ?: $tmpl->source_id);
    $tids  = [];
    if ($insId > 0) {
        $taskRows = $db->select('checkstaff', ' WHERE institution = ?', [$insId]);
        foreach ($taskRows as $task) {
            $tids[] = $task->id;
        }
    }

    if (count($tids) > 0) {
        $violations = $db->db::getAll(
            'SELECT * FROM ' . TBL_PREFIX . 'checksviolations WHERE tasks IN (' . implode(',', $tids) . ') ORDER BY id'
        );
        if (!empty($violations)) {
            $html .= '<div style="margin-top:20px"><strong>Возражения к нарушениям</strong></div><hr>';
            $num = 1;
            foreach ($violations as $vi) {
                $html .= '<div class="item w_100">
                    <strong>№' . $num . '.</strong> ' . htmlspecialchars($vi['name']) . '
                    <p>&nbsp;</p>
                    <div class="item w_100">
                        <div class="el_data">
                            <label>Возражения</label>
                            <input type="hidden" name="violation_id[]" value="' . intval($vi['id']) . '">
                            <textarea class="el_textarea" name="objections[]">' .
                    stripslashes(htmlspecialchars($vi['objections'] ?? '')) .
                    '</textarea>
                        </div>
                    </div>
                </div><hr>';
                $num++;
            }
        }
    }
}
?>
<style>
    .violations_list { display: none; }
    .agreement_list { margin-top: 10px; font-size: 95%; }
    .agreement_list h4 { font-weight: 600; font-size: 14px; margin: 0 0 4px; }
    .agreement_list table { width: 100%; margin: 0; }
    .agreement_list table, .agreement_list table td, .agreement_list table th {
        background-color: #fff;
        border-collapse: collapse;
        border: 1px solid #c5d4fc;
        font-size: 95%;
        vertical-align: middle;
    }
    .agreement_list table td { padding: 5px; }
    .agreement_list table th { padding: 0 15px; background-color: #e5e5e5; color: #4f6396; text-align: center; }
    .agreement_list table td.divider { font-size: 80%; background-color: #dde8f7; padding: 0 2px; line-height: 14px; }
    .agreement_list table td.center { text-align: center; }
    .agreement_list .list_type { text-align: right; margin-top: -25px; }
    .button_registry { display: none; }
    .agreement_list .item.w_50 { width: 100%; margin-top: 20px; }
    .agreement_list button { margin: 5px 0; padding: 5px 10px; }
    .agreement_list tr.redirected td:nth-child(2) { padding-left: 15px; }
</style>

<div class="pop_up drag" style="width: 60vw; min-height: 70vh;">
    <div class="title handle">
        <div class="name">Согласование документа</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <ul class="tab-pane">
            <li id="tab_agreement" class="active">Согласование</li>
            <li id="tab_preview">Предпросмотр</li>
        </ul>

        <div class="agreement_block tab-panel" id="tab_agreement-panel">
            <form class="ajaxFrm noreset" id="objections" onsubmit="return false">
                <?= $html ?>
                <?php if ($is_object && intval($tmpl->documentacial) === 2): ?>
                    <div class="confirm">
                        <button class="button icon text save">
                            <span class="material-icons">send</span>Отправить возражения
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <div class="preview_block tab-panel" id="tab_preview-panel" style="display:none">
            <iframe id="pdf-viewer" width="100%" height="600px"></iframe>
        </div>

        <div class="confirm">
            <button class="button icon text close">
                <span class="material-icons">close</span>Закрыть
            </button>
        </div>
    </div>

    <script src="/js/assets/cades_sign.js"></script>
    <script>
        (function () {
            var DOC_ID = <?= $docId ?>;

            el_app.mainInit();
            el_app.initTabs();

            function dateNow() {
                const now = new Date();
                return `${String(now.getDate()).padStart(2, '0')}.` +
                    `${String(now.getMonth() + 1).padStart(2, '0')}.` +
                    `${now.getFullYear()} ` +
                    `${String(now.getHours()).padStart(2, '0')}:` +
                    `${String(now.getMinutes()).padStart(2, '0')}`;
            }

            function getAgreementList(agj, section, result_type, fallowIds = [], level = 0) {
                let $comment  = $('.actions[data-section=' + section + ']').closest('td').next('td').find('[name=comment]'),
                    $redirect = $('.actions[data-section=' + section + '] [name="redirect[]"]'),
                    currentDateTime = dateNow();

                for (let i = 0; i < agj.length; i++) {
                    if (parseInt(agj[i].id) === <?= $_SESSION['user_id'] ?>) {
                        if (parseInt(result_type) === 0) {
                            agj[i].comment = $comment.val();
                        } else if (parseInt(result_type) === 4) {
                            let vals = $redirect.val();
                            for (let v = 0; v < vals.length; v++) {
                                if (typeof agj[i].redirect != 'undefined') {
                                    if (typeof agj[i].redirect.find(item => parseInt(item.id) === parseInt(vals[v])) == 'undefined') {
                                        agj[i].redirect.push({id: parseInt(vals[v]), type: agj[i].type});
                                    }
                                } else {
                                    agj[i].result = {id: 4, date: currentDateTime};
                                    agj[i].redirect = [{id: parseInt(vals[v]), type: agj[i].type}];
                                    if (level === 0) {
                                        fallowIds.push({index: i, id: agj[i].id, type: agj[i].type});
                                    }
                                }
                            }
                        } else {
                            agj[i].result = {id: result_type, date: currentDateTime};
                        }
                    } else if (typeof agj[i].redirect != 'undefined') {
                        agj.concat(getAgreementList(agj[i].redirect, section, result_type, fallowIds, level + 1));
                    }
                }
                if (fallowIds.length > 0) {
                    for (let f = 0; f < fallowIds.length; f++) {
                        agj.splice(fallowIds[f].index + 1, 0, {id: fallowIds[f].id, type: fallowIds[f].type});
                    }
                }
                return agj;
            }

            function getAgreementData(section, result_type) {
                let $ag    = $('#ag' + section),
                    agj    = JSON.parse($ag.val()),
                    result = getAgreementList(agj, section, result_type);

                $ag.val(JSON.stringify(result));

                let $agInputs = $('[name=addAgreement]'),
                    agList    = [];
                for (let a = 0; a < $agInputs.length; a++) {
                    agList.push($($agInputs[a]).val());
                }

                $.post('/', {
                    ajax: 1, action: 'updateAgreement', agreementList: agList, docId: DOC_ID
                }, function (data) {
                    let answer = JSON.parse(data);
                    if (answer.result) {
                        inform('Отлично!', answer.resultText);
                        el_app.reloadMainContent();
                    } else {
                        el_tools.notify('error', 'Ошибка', answer.resultText);
                    }
                });
            }

            // Кнопки действий в листе согласования
            function reinitEvents() {
                $('.setSign, .setAgreeSign, .setAgree, .setComment, .setRedirect').each(function () {
                    $(this).off('click').on('click', function () {
                        let section     = $(this).closest('.actions').data('section'),
                            result_type = $(this).data('result');
                        if (result_type == 1 || result_type == 2) {
                            // Подпись через КриптоПро — событие doc_signed обрабатывается ниже
                        } else {
                            getAgreementData(section, result_type);
                            $('.actions[data-section=' + section + ']').hide();
                        }
                    });
                });
                $('.chosen-select').chosen({search_contains: true, no_results_text: 'Ничего не найдено.', group_search: false, allowInput: true});
            }
            reinitEvents();

            // Предпросмотр PDF
            $('#agreement #tab_preview, #tab_preview').on('click', function () {
                $('.preloader').fadeIn('fast');
                $.post('/', {
                    ajax: 1, mode: 'popup', module: 'roadmap', url: 'pdf', outputType: 0,
                    params: {docId: DOC_ID}
                }, function (data) {
                    if (data.length > 0) {
                        $('#pdf-viewer').attr('src', 'data:application/pdf;base64,' + data);
                        $('.preloader').fadeOut('fast');
                    }
                });
            });

            // Подпись через КриптоПро
            $(document).off('doc_signed').on('doc_signed', function (e, param) {
                if (param.class.includes('setAgreeSign')) {
                    $('.actions[data-section=' + param.section + ']').hide();
                    getAgreementData(param.section, 2);
                    $('#agResult' + param.section).html("<span style='color:#086a9b'>Согласовано с ЭП<br>" + param.date + '</span>');
                }
                if (param.class.includes('setSign')) {
                    $('.actions[data-section=' + param.section + ']').hide();
                    getAgreementData(param.section, 1);
                    $('#agResult' + param.section).html("<span style='color:#086a9b'>Подписано с ЭП<br>" + param.date + '</span>');
                }
            });

            // Отправка возражений
            $('#objections').on('submit', function () { return false; });
            $('#objections .save').off('click').on('click', function () {
                let $form = $('#objections'),
                    data  = $form.serializeArray();
                data.push({name: 'ajax', value: 1});
                data.push({name: 'action', value: 'objections'});
                data.push({name: 'path', value: 'roadmap'});
                data.push({name: 'act_id', value: DOC_ID});
                data.push({name: 'user_id', value: <?= $_SESSION['user_id'] ?>});
                $('.preloader').fadeIn('fast');
                $.post('/', data, function (resp) {
                    let answer = JSON.parse(resp);
                    $('.preloader').fadeOut('fast');
                    inform(answer.result ? 'Отлично!' : 'Ошибка', answer.resultText);
                });
            });

        })();
    </script>
</div>