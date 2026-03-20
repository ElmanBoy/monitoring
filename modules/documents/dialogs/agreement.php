<?php

use Core\Gui;
use Core\Db;
use Core\Auth;
use Core\Date;
use Core\Templates;
use Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$gui = new Gui;
$db = new Db;
$auth = new Auth();
$temp = new Templates();
$date = new Date();
$reg = new Registry();

if (isset($_POST['params'])) {
    if (is_array($_POST['params'])) {
        $docId = intval($_POST['params']['docId']);
    } else {
        $docId = intval($_POST['params']);
    }
}
if(isset($_POST['docId'])){
    $docId = intval($_POST['docId']);
}

// БАГ #3 ИСПРАВЛЕН: intval гарантирует корректное число для вставки в JS
$currentUserId = intval($_SESSION['user_id']);

$tmpl = $db->selectOne('agreement', ' where id = ?', [$docId]);
$docs = $db->selectOne('agreement', ' WHERE id = ?', [$tmpl->consultation]);

$users = $db->getRegistry('users', '', [],
    ['surname', 'name', 'middle_name', 'institution', 'ministries', 'division', 'position']
);
$initiatorUserId = !empty($tmpl->initiator) ? $tmpl->initiator : $tmpl->author;
$initiator_fio = trim($users['array'][$initiatorUserId][0]) . ' ' .
    trim($users['array'][$initiatorUserId][1]) . ' ' .
    trim($users['array'][$initiatorUserId][2]);
$initiator_position = $users['array'][$initiatorUserId][6] ?? '';

$initiationFormatted = '';
if (!empty($tmpl->initiation)) {
    try {
        $initiationFormatted = (new DateTime($tmpl->initiation))->format('d.m.Y H:i');
    } catch (Exception $e) {
        $initiationFormatted = $tmpl->initiation;
    }
}

$urgent_types = [
    1 => 'Обычный',
    2 => '<span style="color: #d8720b">Срочный</span>',
    3 => '<span style="color: #d8110b">Незамедлительно</span>'
];

// Формируем шапку листа согласования
$docName = $tmpl->documentacial == 6 ? ($docs->name ?? $tmpl->name) : $tmpl->name;
$docNumber = strlen($tmpl->doc_number) > 0 ? ' № ' . htmlspecialchars($tmpl->doc_number) : '';
$headerHtml = '<div id="agreement_block">Лист согласования к документу &laquo;' . htmlspecialchars($docName) . $docNumber . '&raquo;<br>' .
    'Инициатор согласования: ' . htmlspecialchars($initiator_fio) . ' ' . htmlspecialchars($initiator_position) . '<br>' .
    'Согласование инициировано: ' . htmlspecialchars($initiationFormatted) . '</div>';

// Начальный agreementList для первичной загрузки таблицы
$initialAgreementList = json_decode($tmpl->agreementlist, true) ?? [];

?>
<style>
    @font-face {
        font-family: "Times New Roman";
        font-style: normal;
        font-weight: 400;
        src: url("/fonts/timesnrcyrmt.ttf") format("truetype");
    }
    @font-face {
        font-family: "Times New Roman";
        font-style: italic;
        font-weight: 400;
        src: url("/fonts/timesnrcyrmt_inclined.ttf") format("truetype");
    }
    @font-face {
        font-family: "Times New Roman";
        font-style: normal;
        font-weight: 700;
        src: url("/core/vendor/dompdf/dompdf/lib/fonts/Times-Bold.ttf") format("truetype");
    }
    @font-face {
        font-family: "Times New Roman";
        font-style: italic;
        font-weight: 700;
        src: url("/fonts/timesnrcyrmt_boldinclined.ttf") format("truetype");
    }
    @font-face {
        font-family: "Jost";
        src: url("/fonts/Jost-Light.ttf") format("truetype");
        font-weight: 300;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: "Jost";
        src: url("/fonts/Jost-Regular.ttf") format("truetype");
        font-weight: 400;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: "Jost";
        src: url("/fonts/Jost-Medium.ttf") format("truetype");
        font-weight: 500;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: "Jost";
        src: url("/fonts/Jost-SemiBold.ttf") format("truetype");
        font-weight: 600;
        font-style: normal;
        font-display: swap;
    }
    @font-face {
        font-family: "Jost";
        src: url("/fonts/Jost-Bold.ttf") format("truetype");
        font-weight: 700;
        font-style: normal;
        font-display: swap;
    }
    #agreement_block {
        font-family: "Jost", sans-serif;
        font-weight: normal;
        font-size: 14px;
        font-kerning: auto;
        hyphens: auto;
        line-height: 20px;
    }
    main {
        font-family: "Jost", sans-serif;
        font-weight: normal;
        font-size: 17px;
        font-kerning: auto;
        hyphens: auto;
        line-height: 20px;
        margin: 0 1cm;
        padding: 1cm 0 0 0;
    }
    #agreement table.table_data { margin: 25px 0; }
    #agreement table.table_data,
    #agreement table.table_data tr,
    #agreement table.table_data tr td,
    #agreement table.table_data tr th {
        border: 1px solid #000;
        border-collapse: collapse;
        padding: 10px;
        cursor: default;
    }
    #agreement table tr td.group { border: none; margin: 0; }
    .table_data .fixed_thead { top: 46px; }
    footer {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        text-align: left;
        font-size: 11px;
        color: #000;
        padding: 5px 0 0 12px;
        border-top: 1px solid #000;
        height: .1cm;
    }
    footer img { position: absolute; bottom: -30px; right: 10px; width: 100px; }
    @page { margin: 1cm 0 1cm 0; }
    .agreement_list {
        background-color: #dde8f7;
        padding: 10px;
        margin: 10px 0;
        font-size: 95%;
    }
    .agreement_list h4 { font-weight: 600; font-size: 14px; margin: 15px 0; }
    .agreement_list table { width: 100%; margin: 0; }
    .agreement_list table,
    .agreement_list table td,
    .agreement_list table th {
        background-color: #fff;
        border-collapse: collapse;
        border: 1px solid #c5d4fc;
        font-size: 95%;
        vertical-align: middle;
    }
    .agreement_list table tbody.notComplete ~ tbody {
        opacity: .2;
        cursor: n-resize;
    }
    .agreement_list table tbody.notComplete ~ tbody td .actions,
    .agreement_list table tbody.notComplete ~ tbody td .el_data { display: none; }
    .agreement_list table td { padding: 5px; }
    .agreement_list table th { padding: 0 15px; }
    .agreement_list table th {
        background-color: #e5e5e5;
        color: #4f6396;
        font-size: 95%;
        text-align: center;
    }
    .agreement_list table td.divider {
        font-size: 80%;
        background-color: #dde8f7;
        padding: 0 2px;
        line-height: 14px;
    }
    .agreement_list table td.center { text-align: center; }
    .agreement_list .list_type { text-align: right; margin-top: -25px; }
    .button_registry, .button_registry_edit { display: none; }
    .agreement_list .item.w_50 { width: 100%; margin-top: 20px; }
    .agreement_list button { margin: 5px 0; padding: 5px 10px; }
    .agreement_list tr.redirected td:nth-child(2) { padding-left: 15px; }
</style>

<div class="pop_up drag" style="width: 60vw; min-height: 70vh;">
    <div class="title handle">
        <div class="name">Согласование документа &laquo;<?= htmlspecialchars($tmpl->name) ?>&raquo;</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <ul class="tab-pane">
            <li id="tab_agreement" class="active">Согласование</li>
            <li id="tab_preview">Предпросмотр</li>
        </ul>
        <div class="agreement_block tab-panel" id="tab_agreement-panel">
            <main>
                <?= $headerHtml ?>
                <div id="agreement_list_container"></div>
            </main>
        </div>
        <div class="preview_block tab-panel" id="tab_preview-panel" style="display: none">
            <iframe id="pdf-viewer" width="100%" height="600px"></iframe>
        </div>
        <div class="confirm">
            <button class="button icon close"><span class="material-icons">close</span>Закрыть</button>
        </div>
    </div>

    <script src="/js/assets/cades_sign.js"></script>
    <script>
        (function () {
            // БАГ #3 ИСПРАВЛЕН: intval на сервере гарантирует корректное число
            const DOC_ID = <?= $docId ?>;
            const CURRENT_USER_ID = <?= $currentUserId ?>;
            const INITIATOR_ID = <?= intval($tmpl->initiator) ?>;
            // Список пользователей для формы редактирования
            const AG_USERS_OPTIONS = <?= (function() use ($users) {
                $opts = '<option value=""></option>';
                foreach ($users['result'] as $u) {
                    $fio = trim($u->surname) . ' ' . mb_substr(trim($u->name), 0, 1) . '. ' .
                        mb_substr(trim($u->middle_name), 0, 1) . '.';
                    $opts .= '<option value="' . $u->id . '">' . htmlspecialchars($fio) . '</option>';
                }
                return json_encode($opts, JSON_UNESCAPED_UNICODE);
            })() ?>;

            function dateNow() {
                // Fallback на клиентское время если серверное недоступно
                const now = new Date();
                return `${String(now.getDate()).padStart(2, '0')}.` +
                    `${String(now.getMonth() + 1).padStart(2, '0')}.` +
                    `${now.getFullYear()} ` +
                    `${String(now.getHours()).padStart(2, '0')}:` +
                    `${String(now.getMinutes()).padStart(2, '0')}`;
            }

            // Серверное время — обновляется при каждом ответе updateAgreement
            let _serverTime = null;
            function getActionTime() {
                return _serverTime || dateNow();
            }

            // Рекурсивная проверка: есть ли в redirect-цепочке возвращённый элемент (result.id=6)
            function hasReturnedInRedirect(redirectArr) {
                if (!Array.isArray(redirectArr)) return false;
                for (let r of redirectArr) {
                    if (!r.id) continue;
                    if (r.result && parseInt(r.result.id) === 6) return true;
                    if (r.redirect && hasReturnedInRedirect(r.redirect)) return true;
                }
                return false;
            }

            /**
             * Рекурсивно обходит agreementList и применяет действие текущего пользователя.
             * БАГ #2 ИСПРАВЛЕН: повторная запись добавляется ТОЛЬКО на клиенте,
             * сервер её не дублирует (см. updateAgreement.php).
             */
            function applyUserAction(agj, section, result_type, pendingRepeats, level) {
                level = level || 0;
                pendingRepeats = pendingRepeats || [];

                const $actions = $('.actions[data-section=' + section + ']');
                const $comment = $actions.closest('td').next('td').find('[name=comment]');
                const $redirect = $actions.find('[name="redirect[]"]');
                const currentDateTime = getActionTime();

                // Флаг: действие уже применено через redirect[] на этом уровне
                let appliedViaRedirect = false;

                for (let i = 0; i < agj.length; i++) {
                    const isCurrentUser = parseInt(agj[i].id) === CURRENT_USER_ID;
                    const hasResult = agj[i].result && agj[i].result !== '';
                    const isRepeat = agj[i]._is_redirector_repeat;

                    // Обрабатываем действие текущего пользователя
                    // _is_redirector_repeat строки пропускаем только при перенаправлении (result_type=4)
                    // При подписании/согласовании они должны обрабатываться
                    const skipRepeat = isRepeat && parseInt(result_type) === 4;

                    if (isCurrentUser && !skipRepeat && !appliedViaRedirect) {
                        // Для result_type=6 (возврат) — только если у строки НЕТ result
                        const skipForReturn = (parseInt(result_type) === 6 && hasResult);
                        // Не трогаем не-repeat строки если уже есть result (кроме возврата)
                        // Но для repeat строк — применяем даже если hasResult=false (они pending)
                        const skipNonRepeat = (!isRepeat && hasResult && parseInt(result_type) !== 6);

                        if (!skipForReturn && !skipNonRepeat) {
                            if (parseInt(result_type) === 0) {
                                agj[i].comment = (agj[i].comment || '') +
                                    '<p class="agreementComment"><small>' + getActionTime() + '</small><br>' +
                                    $comment.val() + '</p>';

                            } else if (parseInt(result_type) === 4) {
                                const vals = $redirect.val() || [];
                                if (vals.length > 0) {
                                    const originalData = {
                                        id: agj[i].id, type: agj[i].type,
                                        vrio: agj[i].vrio || '0', urgent: agj[i].urgent || '0',
                                        role: agj[i].role || '0'
                                    };
                                    agj[i].result = {id: 4, date: currentDateTime};
                                    if (!agj[i].redirect) agj[i].redirect = [];
                                    for (let v = 0; v < vals.length; v++) {
                                        const exists = agj[i].redirect.some(
                                            item => parseInt(item.id) === parseInt(vals[v])
                                        );
                                        if (!exists) agj[i].redirect.push({id: parseInt(vals[v]), type: agj[i].type});
                                    }
                                    if (level === 0) pendingRepeats.push({index: i, data: originalData});
                                }
                            } else if (parseInt(result_type) === 6) {
                                // Возврат — применяем к pending-строке
                                agj[i].result = {id: 6, date: currentDateTime};
                            } else {
                                agj[i].result = {id: parseInt(result_type), date: currentDateTime};
                                if (parseInt(result_type) === 5 && agj[i].redirect) delete agj[i].redirect;
                            }
                        } // end if (!skipForReturn && !skipNonRepeat)
                    } // end if (isCurrentUser)

                    // ВСЕГДА обрабатываем redirect[] — независимо от того наш ли это элемент
                    // Это критично для многоуровневых перенаправлений
                    if (agj[i].redirect && Array.isArray(agj[i].redirect)) {
                        const userPendingInRedirect = agj[i].redirect.some(
                            r => parseInt(r.id) === CURRENT_USER_ID && !r._is_redirector_repeat
                                && (r.result === null || r.result === undefined || r.result === '')
                        );

                        applyUserAction(agj[i].redirect, section, result_type, pendingRepeats, level + 1);

                        if (parseInt(result_type) === 6) {
                            // После рекурсии проверяем: появился ли result.id=6 где-то в цепочке
                            // Если да — сбрасываем result текущего элемента (перенаправившего)
                            if (hasResult && hasReturnedInRedirect(agj[i].redirect)) {
                                agj[i].result = null;
                            }
                        } else if (userPendingInRedirect && parseInt(result_type) !== 4) {
                            appliedViaRedirect = true;
                        }
                    }
                }

                // Вставляем повторные записи только на верхнем уровне
                if (level === 0 && pendingRepeats.length > 0) {
                    pendingRepeats.sort((a, b) => b.index - a.index);
                    for (let f = 0; f < pendingRepeats.length; f++) {
                        const pr = pendingRepeats[f];
                        const alreadyExists = agj.some(
                            (item, idx) => idx > pr.index && parseInt(item.id) === parseInt(pr.data.id) && item._is_redirector_repeat
                        );
                        if (!alreadyExists) {
                            agj.splice(pr.index + 1 + f, 0, {
                                id: pr.data.id, type: pr.data.type,
                                vrio: pr.data.vrio, urgent: pr.data.urgent,
                                role: pr.data.role, result: null,
                                _is_redirector_repeat: true
                            });
                        }
                    }
                }

                return agj;
            }

            /**
             * Собирает весь agreementList со страницы, применяет действие и отправляет на сервер.
             */
            function getAgreementData(section, result_type) {
                const $ag = $('#ag' + section);
                let agj = JSON.parse($ag.val());

                agj = applyUserAction(agj, section, result_type, [], 0);
                $ag.val(JSON.stringify(agj));

                // Собираем все секции
                const agList = [];
                $('[name=addAgreement]').each(function () {
                    try {
                        agList.push(JSON.parse($(this).val()));
                    } catch (e) {
                        agList.push($(this).val());
                    }
                });

                $.post('/', {
                    ajax: 1,
                    action: 'updateAgreement',
                    path: 'documents',
                    agreementList: agList,
                    docId: DOC_ID
                }, function (data) {
                    let answer;
                    try {
                        answer = JSON.parse(data);
                    } catch (e) {
                        el_tools.notify('error', 'Ошибка', 'Некорректный ответ сервера');
                        return;
                    }
                    if (answer.result) {
                        inform('Отлично!', answer.resultText);
                        // Обновляем серверное время
                        if (answer.serverTime) { _serverTime = answer.serverTime; }
                        // Обновляем значение скрытого поля актуальными данными с сервера
                        $ag.val(JSON.stringify(answer.resultAgreement[section]));
                        refreshAgreementTable(answer.resultAgreement);
                    } else {
                        el_tools.notify('error', 'Ошибка', answer.resultText);
                    }
                });
            }

            /**
             * Перерисовывает таблицу согласования через AJAX.
             */
            function refreshAgreementTable(agreementList) {
                if (!Array.isArray(agreementList)) {
                    console.error('agreementList не является массивом', agreementList);
                    return;
                }
                $('.preloader').fadeIn('fast');
                $.post('/', {
                    ajax: 1,
                    action: 'renderAgreementTable',
                    path: 'documents',
                    agreementList: agreementList,
                    docId: DOC_ID
                }, function (data) {
                    let answer;
                    try {
                        answer = JSON.parse(data);
                    } catch (e) {
                        console.error('Ошибка разбора ответа renderAgreementTable', e);
                        return;
                    }
                    if (answer.result) {
                        $('#agreement_list_container').html(answer.html);
                        $('select[name="redirect[]"]').chosen({
                            search_contains: true,
                            no_results_text: 'Ничего не найдено.',
                            group_search: false,
                            allowInput: true
                        });
                        reinitEvents();
                    }
                    $('.preloader').fadeOut('fast');
                }).fail(function (xhr, status, error) {
                    console.error('Ошибка AJAX renderAgreementTable:', error, xhr.responseText);
                });
            }

            /**
             * Переинициализирует все обработчики событий после перерисовки таблицы.
             * БАГ #7 ИСПРАВЛЕН: единственное место привязки обработчиков — эта функция.
             * $(document).ready вызывает только её.
             */
            function reinitEvents() {
                $(document).off('click', '.setAgree').on('click', '.setAgree', function (e) {
                    e.preventDefault();
                    const section = $(this).closest('.actions').data('section');
                    const currentDateTime = getActionTime();
                    getAgreementData(section, 3);
                    $('.actions[data-section=' + section + ']').hide();
                    $('#agResult' + section).html("<span style='color: #086a9b'>Согласовано<br>" + currentDateTime + '</span>');
                });

                $(document).off('mousedown keydown', '.setReject').on('mousedown keydown', '.setReject', function (e) {
                    e.preventDefault();
                    const $comment = $(this).closest('td').next('td').find('[name=comment]');
                    const comment = $comment.val();
                    if ($.trim(comment) === '') {
                        alert('Сначала введите причину отклонения.');
                        $comment.trigger('focus');
                    } else {
                        const section = $(this).closest('.actions').data('section');
                        const currentDateTime = getActionTime();
                        getAgreementData(section, 5);
                        $('.actions[data-section=' + section + ']').hide();
                        $('#agResult' + section).html("<span style='color: var(--red)'>Отклонено<br>" + currentDateTime + '</span>');
                    }
                });

                $('[name=comment]').off('blur').on('blur', function () {
                    const comment = $(this).val();
                    const section = $(this).closest('td').prev('td').find('.actions').data('section');
                    getAgreementData(section, 0);
                    if ($.trim(comment) !== '') {
                        $(this).closest('td').prev('td').find('.setReject').removeClass('disabled');
                    } else {
                        $(this).closest('td').prev('td').find('.setReject').addClass('disabled');
                    }
                });

                $('[name=redirect], [name="redirect[]"]').off('change');

                $(document).off('click', '.doRedirect').on('click', '.doRedirect', function (e) {
                    e.preventDefault();
                    const $actions = $(this).closest('.actions');
                    const section = $actions.data('section');
                    const vals = $actions.find('[name="redirect[]"]').val() || [];
                    if (!vals || vals.length === 0) {
                        el_tools.notify('error', 'Ошибка', 'Выберите сотрудника для перенаправления');
                        return;
                    }
                    getAgreementData(section, 4);
                    $('#agResult' + section).html("<span style='color: #086a9b'>Перенаправлено<br>" + getActionTime() + '</span>');
                    inform('Перенаправление', 'Документ перенаправлен. Повторная запись появится после согласования цепочки.');
                });

                $(document).off('click', '.setReturn').on('click', '.setReturn', function (e) {
                    e.preventDefault();
                    const section = $(this).closest('.actions').data('section');
                    getAgreementData(section, 6);
                    $('.actions[data-section=' + section + ']').hide();
                    $('#agResult' + section).html("<span style='color: #e67e22'>Возвращено на доработку<br>" + getActionTime() + '</span>');
                    inform('Возврат', 'Документ возвращён на доработку перенаправившему сотруднику.');
                });

                // Удаление сотрудника инициатором
                $(document).off('click', '.ag-delete-btn').on('click', '.ag-delete-btn', function (e) {
                    e.preventDefault();
                    const section = $(this).data('section');
                    const index   = $(this).data('index');
                    const $ag = $('#ag' + section);
                    let agj = JSON.parse($ag.val());
                    agj.splice(index, 1);
                    $ag.val(JSON.stringify(agj));
                    saveAgreementList();
                });

                // Вспомогательная функция сохранения agreementList на сервер
                // Проверяет порядок в секции подписантов:
                // role=1 (подписывает) должен быть перед role=0 (утверждает)
                function checkSignersOrder(agj) {
                    // Определяем секцию подписантов по stage=""
                    if (!agj[0] || agj[0].stage === undefined) return true;
                    const isSigners = (agj[0].stage === '' || agj[0].stage === null);
                    if (!isSigners) return true;

                    let lastSignerIdx = -1; // последний role=1
                    let firstApproverIdx = -1; // первый role=0

                    for (let i = 1; i < agj.length; i++) {
                        if (!agj[i].id || agj[i]._is_redirector_repeat || agj[i].stage !== undefined) continue;
                        const role = parseInt(agj[i].role ?? 0);
                        if (role === 1 && lastSignerIdx < i) lastSignerIdx = i;
                        if (role === 0 && firstApproverIdx === -1) firstApproverIdx = i;
                    }

                    // Если есть и подписывающий и утверждающий — подписывающий должен быть раньше
                    if (lastSignerIdx > -1 && firstApproverIdx > -1) {
                        if (lastSignerIdx > firstApproverIdx) {
                            return false;
                        }
                    }
                    return true;
                }

                function saveAgreementList(onSuccess) {
                    const agList = [];
                    $('[name=addAgreement]').each(function () {
                        try { agList.push(JSON.parse($(this).val())); }
                        catch (e2) { agList.push($(this).val()); }
                    });
                    $.post('/', {
                        ajax: 1, action: 'updateAgreement', path: 'documents',
                        agreementList: agList, docId: DOC_ID
                    }, function (data) {
                        const answer = JSON.parse(data);
                        if (answer.result) {
                            if (answer.serverTime) _serverTime = answer.serverTime;
                            refreshAgreementTable(answer.resultAgreement);
                            if (onSuccess) onSuccess();
                        } else {
                            el_tools.notify('error', 'Ошибка', answer.resultText);
                        }
                    });
                }

                // Добавление нового сотрудника инициатором (после выбранной строки)
                $(document).off('click', '.ag-add-btn').on('click', '.ag-add-btn', function (e) {
                    e.preventDefault();
                    const $btn      = $(this);
                    const section   = $btn.data('section');
                    const afterIndex = parseInt($btn.data('index')); // -1 = в конец
                    const isSigners  = $btn.data('is-signers') == 1;
                    const $tr = $btn.closest('tr');

                    if ($tr.next('.ag-add-form-row').length) {
                        $tr.next('.ag-add-form-row').remove();
                        return;
                    }
                    $('.ag-add-form-row, .ag-edit-row').remove();

                    const urgentOptions = '<option value="1">Обычный</option>' +
                        '<option value="2">Срочный</option>' +
                        '<option value="3">Незамедлительно</option>';
                    const typeOptions = isSigners
                        ? '<option value="1">Подписание</option>'
                        : '<option value="2" selected>Согласование</option><option value="1">Подписание</option>';

                    const addRow = $('<tr class="ag-add-form-row"><td colspan="5">' +
                        '<div style="padding:10px;background:#f0f8ff;border:1px solid #b3d9ff;border-radius:4px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">' +
                        '<div><label style="display:block;font-size:11px;color:#666;margin-bottom:3px">Сотрудник</label>' +
                        '<select class="ag-add-user chosen-select" style="min-width:200px" data-placeholder="Выберите сотрудника"></select></div>' +
                        '<div><label style="display:block;font-size:11px;color:#666;margin-bottom:3px">Тип</label>' +
                        '<select class="ag-add-type">' + typeOptions + '</select></div>' +
                        '<div><label style="display:block;font-size:11px;color:#666;margin-bottom:3px">Срочность</label>' +
                        '<select class="ag-add-urgent">' + urgentOptions + '</select></div>' +
                        '<div><label style="display:block;font-size:11px;color:#666;margin-bottom:3px">ВРИО</label>' +
                        '<select class="ag-add-vrio chosen-select" style="min-width:150px" data-placeholder="Нет"></select></div>' +
                        '<button class="button icon text green ag-add-save" style="margin-bottom:0"><span class="material-icons">save</span>Добавить</button>' +
                        '<button class="button icon text ag-add-cancel" style="margin-bottom:0"><span class="material-icons">close</span>Отмена</button>' +
                        '</div></td></tr>');

                    $tr.after(addRow);
                    addRow.find('.ag-add-user').html(AG_USERS_OPTIONS);
                    addRow.find('.ag-add-vrio').html('<option value="0">Нет</option>' + AG_USERS_OPTIONS);
                    addRow.find('.ag-add-user, .ag-add-vrio').chosen({width: '100%'});

                    addRow.find('.ag-add-save').on('click', function () {
                        const newItem = {
                            id:     addRow.find('.ag-add-user').val(),
                            type:   addRow.find('.ag-add-type').val(),
                            urgent: addRow.find('.ag-add-urgent').val(),
                            vrio:   addRow.find('.ag-add-vrio').val() || '0',
                            role:   '0',
                            result: null
                        };
                        if (!newItem.id) { el_tools.notify('error', 'Ошибка', 'Выберите сотрудника'); return; }
                        const $ag = $('#ag' + section);
                        let agj = JSON.parse($ag.val());
                        // Вставляем после afterIndex (или в конец если -1)
                        if (afterIndex < 0) {
                            agj.push(newItem);
                        } else {
                            agj.splice(afterIndex + 1, 0, newItem);
                        }
                        $ag.val(JSON.stringify(agj));
                        addRow.remove();
                        saveAgreementList(function() { inform('Готово', 'Сотрудник добавлен.'); });
                    });
                    addRow.find('.ag-add-cancel').on('click', function () { addRow.remove(); });
                });

                // Перемещение строки вверх
                $(document).off('click', '.ag-move-up-btn').on('click', '.ag-move-up-btn', function (e) {
                    e.preventDefault();
                    const section = $(this).data('section');
                    const index   = parseInt($(this).data('index'));
                    const $ag = $('#ag' + section);
                    let agj = JSON.parse($ag.val());
                    // Ищем предыдущий pending-элемент (не _is_redirector_repeat)
                    let prevIdx = -1;
                    for (let k = index - 1; k >= 0; k--) {
                        if (agj[k].id !== undefined && !agj[k]._is_redirector_repeat && !agj[k].stage) {
                            prevIdx = k; break;
                        }
                    }
                    if (prevIdx >= 0) {
                        [agj[prevIdx], agj[index]] = [agj[index], agj[prevIdx]];
                        if (!checkSignersOrder(agj)) {
                            // Откатываем
                            [agj[prevIdx], agj[index]] = [agj[index], agj[prevIdx]];
                            el_tools.notify('error', 'Ошибка', 'Утверждающий (role=0) должен быть после подписывающего (role=1)');
                            return;
                        }
                        $ag.val(JSON.stringify(agj));
                        saveAgreementList();
                    }
                });

                // Перемещение строки вниз
                $(document).off('click', '.ag-move-down-btn').on('click', '.ag-move-down-btn', function (e) {
                    e.preventDefault();
                    const section = $(this).data('section');
                    const index   = parseInt($(this).data('index'));
                    const $ag = $('#ag' + section);
                    let agj = JSON.parse($ag.val());
                    // Ищем следующий pending-элемент (не _is_redirector_repeat)
                    let nextIdx = -1;
                    for (let k = index + 1; k < agj.length; k++) {
                        if (agj[k].id !== undefined && !agj[k]._is_redirector_repeat && !agj[k].stage) {
                            nextIdx = k; break;
                        }
                    }
                    if (nextIdx >= 0) {
                        [agj[nextIdx], agj[index]] = [agj[index], agj[nextIdx]];
                        if (!checkSignersOrder(agj)) {
                            [agj[nextIdx], agj[index]] = [agj[index], agj[nextIdx]];
                            el_tools.notify('error', 'Ошибка', 'Утверждающий (role=0) должен быть после подписывающего (role=1)');
                            return;
                        }
                        $ag.val(JSON.stringify(agj));
                        saveAgreementList();
                    }
                });
                $(document).off('click', '.ag-edit-btn').on('click', '.ag-edit-btn', function (e) {
                    e.preventDefault();
                    const $btn    = $(this);
                    const $tr     = $btn.closest('tr');
                    const section = $btn.data('section');
                    const index   = $btn.data('index');
                    const level   = $btn.data('level') || 0;
                    const item    = $btn.data('item');

                    // Если уже открыта форма — закрываем
                    if ($tr.next('.ag-edit-row').length) {
                        $tr.next('.ag-edit-row').remove();
                        return;
                    }
                    // Закрываем другие открытые формы
                    $('.ag-edit-row').remove();

                    const urgentOptions = [
                        '<option value="1"' + (item.urgent == 1 ? ' selected' : '') + '>Обычный</option>',
                        '<option value="2"' + (item.urgent == 2 ? ' selected' : '') + '>Срочный</option>',
                        '<option value="3"' + (item.urgent == 3 ? ' selected' : '') + '>Незамедлительно</option>'
                    ].join('');

                    const typeOptions = [
                        '<option value="1"' + (item.type == 1 ? ' selected' : '') + '>Подписание</option>',
                        '<option value="2"' + (item.type == 2 ? ' selected' : '') + '>Согласование</option>'
                    ].join('');

                    const colCount = $tr.find('td').length;
                    const editRow = $('<tr class="ag-edit-row"><td colspan="' + colCount + '">' +
                        '<div style="padding:10px;background:#f8f9fa;border:1px solid #dee2e6;border-radius:4px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">' +
                        '<div><label style="display:block;font-size:11px;color:#666;margin-bottom:3px">Сотрудник</label>' +
                        '<select class="ag-edit-user chosen-select" style="min-width:200px" data-placeholder="Выберите сотрудника"></select></div>' +
                        '<div><label style="display:block;font-size:11px;color:#666;margin-bottom:3px">Тип</label>' +
                        '<select class="ag-edit-type">' + typeOptions + '</select></div>' +
                        '<div><label style="display:block;font-size:11px;color:#666;margin-bottom:3px">Срочность</label>' +
                        '<select class="ag-edit-urgent">' + urgentOptions + '</select></div>' +
                        '<div><label style="display:block;font-size:11px;color:#666;margin-bottom:3px">ВРИО</label>' +
                        '<select class="ag-edit-vrio chosen-select" style="min-width:150px" data-placeholder="Нет"></select></div>' +
                        '<button class="button icon text green ag-edit-save" style="margin-bottom:0"><span class="material-icons">save</span>Сохранить</button>' +
                        '<button class="button icon text ag-edit-cancel" style="margin-bottom:0"><span class="material-icons">close</span>Отмена</button>' +
                        '</div></td></tr>');

                    $tr.after(editRow);

                    // Подставляем список пользователей из PHP
                    editRow.find('.ag-edit-user').html(AG_USERS_OPTIONS).val(item.id);
                    editRow.find('.ag-edit-vrio').html('<option value="0">Нет</option>' + AG_USERS_OPTIONS).val(item.vrio || '0');
                    editRow.find('.ag-edit-user, .ag-edit-vrio').chosen({width: '100%'});

                    // Сохранение
                    editRow.find('.ag-edit-save').on('click', function () {
                        const newItem = {
                            id:     editRow.find('.ag-edit-user').val(),
                            type:   editRow.find('.ag-edit-type').val(),
                            urgent: editRow.find('.ag-edit-urgent').val(),
                            vrio:   editRow.find('.ag-edit-vrio').val() || '0',
                            role:   item.role || '0',
                            result: null
                        };
                        if (!newItem.id) {
                            el_tools.notify('error', 'Ошибка', 'Выберите сотрудника');
                            return;
                        }
                        // Применяем изменение к agreementList
                        const $ag = $('#ag' + section);
                        let agj = JSON.parse($ag.val());

                        if (level === 0) {
                            // Верхний уровень — прямая замена
                            agj[index] = newItem;
                        } else {
                            // Вложенный уровень — ищем элемент рекурсивно по id и index
                            function updateNestedItem(arr, targetId, targetIndex, lv, currentLv) {
                                for (let i = 0; i < arr.length; i++) {
                                    if (arr[i].redirect && Array.isArray(arr[i].redirect)) {
                                        if (currentLv + 1 === lv) {
                                            // Следующий уровень — это целевой
                                            if (i === targetIndex || arr[i].redirect.some(
                                                r => parseInt(r.id) === parseInt(targetId)
                                            )) {
                                                for (let j = 0; j < arr[i].redirect.length; j++) {
                                                    if (parseInt(arr[i].redirect[j].id) === parseInt(targetId)
                                                        && !arr[i].redirect[j]._is_redirector_repeat) {
                                                        arr[i].redirect[j] = newItem;
                                                        return true;
                                                    }
                                                }
                                            }
                                        }
                                        if (updateNestedItem(arr[i].redirect, targetId, targetIndex, lv, currentLv + 1)) {
                                            return true;
                                        }
                                    }
                                }
                                return false;
                            }
                            updateNestedItem(agj, item.id, index, level, 0);
                        }
                        $ag.val(JSON.stringify(agj));
                        editRow.remove();
                        saveAgreementList(function() { inform('Готово', 'Согласующий обновлён.'); });
                    });

                    // Отмена
                    editRow.find('.ag-edit-cancel').on('click', function () {
                        editRow.remove();
                    });
                });

                // Раскрытие/скрытие длинных комментариев
                $(document).off('click', '.ag-comment-toggle').on('click', '.ag-comment-toggle', function (e) {
                    e.preventDefault();
                    const uid = $(this).data('target');
                    $('#' + uid + '_short').toggle();
                    $('#' + uid + '_full').toggle();
                });

                bindSign('agreement', DOC_ID, CURRENT_USER_ID);
            }

            function getChosenSortedVal(obj) {
                const lis = $(obj).next().find('.search-choice');
                const out = [];
                for (let i = 0; i < lis.length; i++) {
                    out.push($(obj).find("option:contains('" + $(lis[i]).find('span').text() + "')").val());
                }
                return out;
            }

            $(document).ready(function () {
                // Загружаем таблицу при старте напрямую с сервера по docId
                $('.preloader').fadeIn('fast');
                $.post('/', {
                    ajax: 1,
                    action: 'renderAgreementTable',
                    path: 'documents',
                    agreementList: <?= json_encode(
                        array_map('json_encode', $initialAgreementList),
                        JSON_UNESCAPED_UNICODE
                    ) ?>,
                    docId: DOC_ID
                }, function (data) {
                    let answer;
                    try { answer = JSON.parse(data); } catch (e) { return; }
                    if (answer.result) {
                        $('#agreement_list_container').html(answer.html);
                        $('select[name="redirect[]"]').chosen({
                            search_contains: true,
                            no_results_text: 'Ничего не найдено.',
                            group_search: false,
                            allowInput: true
                        });
                        reinitEvents();
                    }
                    $('.preloader').fadeOut('fast');
                });
                el_app.initTabs();
                // Предпросмотр PDF
                $('#agreement #tab_preview').on('click', function () {
                    $('.preloader').fadeIn('fast');
                    $.post('/', {
                        ajax: 1, mode: 'popup', module: 'documents', url: 'planPdf', outputType: 0,
                        params: {docId: DOC_ID}
                    }, function (data) {
                        if (data.length > 0) {
                            $('#pdf-viewer').attr('src', 'data:application/pdf;base64,' + data);
                            $('.preloader').fadeOut('fast');
                        }
                    });
                });



                // Обработчик события подписи ЭП
                $(document).off('doc_signed').on('doc_signed', function (e, param) {
                    if (param.class.includes('setAgreeSign')) {
                        $('.actions[data-section=' + param.section + ']').hide();
                        getAgreementData(param.section, 2);
                        $('#agResult' + param.section).html("<span style='color: #086a9b'>Согласовано с ЭП<br>" + param.date + '</span>');
                    }
                    if (param.class.includes('setSign')) {
                        $('.actions[data-section=' + param.section + ']').hide();
                        getAgreementData(param.section, 1);
                        $('#agResult' + param.section).html("<span style='color: #086a9b'>Подписано с ЭП<br>" + param.date + '</span>');
                    }
                });
            });

        })(); // IIFE — изолируем все переменные от глобальной области видимости
    </script>
</div>