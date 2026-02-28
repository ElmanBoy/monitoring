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

// БАГ #3 ИСПРАВЛЕН: intval гарантирует корректное число для вставки в JS
$currentUserId = intval($_SESSION['user_id']);

$tmpl = $db->selectOne('agreement', ' where id = ?', [$docId]);
$docs = $db->selectOne('agreement', ' WHERE id = ?', [$tmpl->consultation]);

$users = $db->getRegistry('users', '', [],
    ['surname', 'name', 'middle_name', 'institution', 'ministries', 'division', 'position']
);
$initiator_fio = $users['array'][$tmpl->initiator][0] . ' ' . $users['array'][$tmpl->initiator][1] . ' ' .
    $users['array'][$tmpl->initiator][2];
$initiator_position = $users['array'][$tmpl->initiator][6];

$urgent_types = [
    1 => 'Обычный',
    2 => '<span style="color: #d8720b">Срочный</span>',
    3 => '<span style="color: #d8110b">Незамедлительно</span>'
];

// Формируем шапку листа согласования
$headerHtml = '';
if ($tmpl->documentacial == 6) {
    $headerHtml = '<div id="agreement_block">Лист согласования к документу &laquo;' . htmlspecialchars($docs->name) .
        (strlen($docs->doc_number) > 0 ? ' № ' . htmlspecialchars($docs->doc_number) : '') . '&raquo;<br>' .
        'Инициатор согласования: ' . htmlspecialchars($initiator_fio) . ' ' . htmlspecialchars($initiator_position) . '<br>' .
        'Согласование инициировано: ' . htmlspecialchars($tmpl->initiation) . '</div>';
} elseif ($tmpl->documentacial == 3) {
    // Зарезервировано
} else {
    $headerHtml = '<div id="agreement_block">Лист согласования к документу &laquo;' . htmlspecialchars($tmpl->name) . '&raquo;<br>' .
        'Инициатор согласования: ' . htmlspecialchars($initiator_fio) . ' ' . htmlspecialchars($initiator_position) . '<br>' .
        'Согласование инициировано: ' . htmlspecialchars($tmpl->initiation) . '</div>';
}

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

            function dateNow() {
                const now = new Date();
                return `${String(now.getDate()).padStart(2, '0')}.` +
                    `${String(now.getMonth() + 1).padStart(2, '0')}.` +
                    `${now.getFullYear()} ` +
                    `${String(now.getHours()).padStart(2, '0')}:` +
                    `${String(now.getMinutes()).padStart(2, '0')}`;
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
                const currentDateTime = dateNow();

                for (let i = 0; i < agj.length; i++) {
                    if (parseInt(agj[i].id) === CURRENT_USER_ID) {
                        if (parseInt(result_type) === 0) {
                            // Только комментарий — не трогаем result
                            agj[i].comment = (agj[i].comment || '') +
                                '<p class="agreementComment"><small>' + dateNow() + '</small><br>' +
                                $comment.val() + '</p>';

                        } else if (parseInt(result_type) === 4) {
                            // Перенаправление
                            const vals = $redirect.val() || [];
                            if (vals.length > 0) {
                                const originalData = {
                                    id: agj[i].id,
                                    type: agj[i].type,
                                    vrio: agj[i].vrio || '0',
                                    urgent: agj[i].urgent || '0',
                                    role: agj[i].role || '0'
                                };

                                agj[i].result = {id: 4, date: currentDateTime};

                                if (!agj[i].redirect) {
                                    agj[i].redirect = [];
                                }
                                for (let v = 0; v < vals.length; v++) {
                                    const exists = agj[i].redirect.some(
                                        item => parseInt(item.id) === parseInt(vals[v])
                                    );
                                    if (!exists) {
                                        agj[i].redirect.push({id: parseInt(vals[v]), type: agj[i].type});
                                    }
                                }

                                // Запоминаем для вставки повторной записи (только на верхнем уровне)
                                if (level === 0) {
                                    pendingRepeats.push({index: i, data: originalData});
                                }
                            }
                        } else {
                            // Согласование / подписание / отклонение
                            agj[i].result = {id: parseInt(result_type), date: currentDateTime};
                            if (parseInt(result_type) === 5 && agj[i].redirect) {
                                delete agj[i].redirect;
                            }
                        }
                    } else if (agj[i].redirect && Array.isArray(agj[i].redirect)) {
                        applyUserAction(agj[i].redirect, section, result_type, pendingRepeats, level + 1);
                    }
                }

                // Вставляем повторные записи только на верхнем уровне
                if (level === 0 && pendingRepeats.length > 0) {
                    pendingRepeats.sort((a, b) => b.index - a.index);
                    for (let f = 0; f < pendingRepeats.length; f++) {
                        const pr = pendingRepeats[f];
                        // Проверяем, нет ли уже повторной записи (защита от двойного клика)
                        const alreadyExists = agj.some(
                            (item, idx) => idx > pr.index && parseInt(item.id) === parseInt(pr.data.id) && !item.result
                        );
                        if (!alreadyExists) {
                            agj.splice(pr.index + 1 + f, 0, {
                                id: pr.data.id,
                                type: pr.data.type,
                                vrio: pr.data.vrio,
                                urgent: pr.data.urgent,
                                role: pr.data.role,
                                result: null
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

                $.post('/', {
                    ajax: 1,
                    action: 'renderAgreementTable',
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
                $('.setAgree').off('click').on('click', function (e) {
                    e.preventDefault();
                    const section = $(this).closest('.actions').data('section');
                    const currentDateTime = dateNow();
                    getAgreementData(section, 3);
                    $('.actions[data-section=' + section + ']').hide();
                    $('#agResult' + section).html("<span style='color: #086a9b'>Согласовано<br>" + currentDateTime + '</span>');
                });

                $('.setReject').off('mousedown keydown').on('mousedown keydown', function (e) {
                    e.preventDefault();
                    const $comment = $(this).closest('td').next('td').find('[name=comment]');
                    const comment = $comment.val();
                    if ($.trim(comment) === '') {
                        alert('Сначала введите причину отклонения.');
                        $comment.trigger('focus');
                    } else {
                        const section = $(this).closest('.actions').data('section');
                        const currentDateTime = dateNow();
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

                $('[name=redirect], [name="redirect[]"]').off('change').on('change', function () {
                    const section = $(this).closest('.actions').data('section');
                    getAgreementData(section, 4);
                    $('#agResult' + section).html("<span style='color: #086a9b'>Перенаправлено<br>" + dateNow() + '</span>');
                    inform('Перенаправление', 'Документ перенаправлен. Повторная запись появится после согласования цепочки.');
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
                // Загружаем таблицу при старте с актуальными данными из БД
                const initialAgreementList = <?= json_encode($initialAgreementList, JSON_UNESCAPED_UNICODE) ?>;
                refreshAgreementTable(initialAgreementList);

                // Предпросмотр PDF
                $('#tab_preview').on('click', function () {
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

                el_app.initTabs();

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