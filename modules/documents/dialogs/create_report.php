<?php
/**
 * modules/documents/dialogs/create_report.php
 *
 * Диалог создания доклада министру на основе подписанного акта.
 *
 * Открывается из карточки акта (список documents/roadmap).
 * POST params[docId] — id акта в cam_agreement (documentacial=2)
 */

use Core\Db;
use Core\Auth;
use Core\Date;
use Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db   = new Db();
$auth = new Auth();
$date = new Date();
$reg  = new Registry();

if (!$auth->isLogin()) {
    die();
}

$actId = intval($_POST['params']['docId'] ?? 0);

// Загружаем акт
$act = $db->selectOne('agreement', ' WHERE id = ? AND documentacial = 2', [$actId]);
if (!$act) {
    echo '<script>alert("Акт не найден.");</script>';
    die();
}
if (intval($act->status) !== 1) {
    echo '<script>alert("Доклад можно сформировать только после подписания акта.");</script>';
    die();
}

// Проверяем — не создан ли доклад уже
$existReport = $db->selectOne('agreement', ' WHERE documentacial = 4 AND source_id = ?', [$actId]);

// Генерация следующего номера доклада (формат: ДКЛ{номер}-{год})
$currentYear = date('Y');
$reportsThisYear = $db->select('agreement',
    " WHERE documentacial = 4 AND doc_number LIKE 'ДКЛ%-" . $currentYear . "'"
);

$maxNumber = 999; // Начинаем с 999, чтобы первый номер был 1000
foreach ($reportsThisYear as $rep) {
    if (preg_match('/ДКЛ(\d+)-' . $currentYear . '/', $rep->doc_number, $matches)) {
        $num = intval($matches[1]);
        if ($num > $maxNumber) {
            $maxNumber = $num;
        }
    }
}

$nextNumber = $maxNumber + 1;
$generatedDocNumber = 'ДКЛ' . $nextNumber . '-' . $currentYear;

// Нарушения по акту: ищем через checkstaff по ins_id и plan_id акта
$insId = intval($act->ins_id ?? $act->source_id ?? 0);
$planId = intval($act->plan_id ?? 0);

// Нарушения из чек-листов: поля radio с ответом "Нет" (0) или "Частично" (2)
$violations = [];
if ($insId > 0 && $planId > 0) {
    $plan = $db->selectOne('checksplans', ' WHERE id = ?', [$planId]);
    if ($plan && strlen($plan->uid ?? '') > 0) {
        $staffRows = $db->select('checkstaff',
            ' WHERE check_uid = ? AND institution = ?', [$plan->uid, $insId]
        );
        foreach ($staffRows as $sr) {
            // task_id — jsonb массив ID задач
            $taskIds = json_decode($sr->task_id ?? '[]', true) ?: [];
            foreach ($taskIds as $taskId) {
                $task = $db->selectOne('tasks', ' WHERE id = ?', [intval($taskId)]);
                if (!$task) continue;
                $checklistIds = json_decode($task->sheet ?? '[]', true) ?: [];
                foreach ($checklistIds as $clId) {
                    $checklist = $db->selectOne('checklists', ' WHERE id = ?', [intval($clId)]);
                    if (!$checklist || empty($checklist->table_name)) continue;
                    $clData = $db->selectOne($checklist->table_name, ' WHERE id = ?', [intval($sr->record_id)]);
                    if (!$clData) continue;
                    $clViolations = $reg->getChecklistViolations(intval($clId), (array)$clData);
                    foreach ($clViolations as $v) {
                        $violations[] = $v;
                    }
                }
            }
        }
        // Убираем дубли по тексту
        $seen = [];
        $violations = array_filter($violations, function($v) use (&$seen) {
            if (isset($seen[$v['text']])) return false;
            $seen[$v['text']] = true;
            return true;
        });
        $violations = array_values($violations);
    }
}

// Возражения ОК
$objections = json_decode($act->objections ?? '{}', true);
$hasObjections = !empty($objections['text']) || !empty($objections['files']);

?>
<style>
    #proposals_container .proposal_item .el_data {
        flex: 1 !important;
        width: 100% !important;
    }
    #proposals_container .proposal_item .el_textarea {
        width: 100% !important;
    }
</style>
<div class='pop_up drag' style='width:65vw'>
    <div class='title handle'>
        <div class='name'>Доклад министру — «<?= htmlspecialchars($act->name) ?>»</div>
        <div class='button icon close'><span class='material-icons'>close</span></div>
    </div>
    <div class='pop_up_body'>

        <?php if ($existReport): ?>
            <!-- Доклад уже создан — показываем статус и кнопку согласования -->
            <div class='group'>
                <div class='item w_100'>
                    <div class='inform_block <?= intval($existReport->status) === 1 ? 'inform_success' : '' ?>'>
                        <span class='material-icons'><?= intval($existReport->status) === 1 ? 'task_alt' : 'pending' ?></span>
                        Доклад <?= intval($existReport->status) === 1 ? 'утверждён министром' : 'на согласовании' ?>.
                        <?php if (strlen($existReport->doc_number ?? '') > 0): ?>
                            Номер: <strong><?= htmlspecialchars($existReport->doc_number) ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class='confirm'>
                <button class='button icon text' onclick="el_app.dialog_open('documents', 'agreement', {docId: <?= intval($existReport->id) ?>}, true)">
                    <span class='material-icons'>how_to_vote</span>Открыть лист согласования
                </button>
                <button class='button icon text' onclick="window.open('/?ajax=1&mode=popup&module=documents&url=report_pdf&params[docId]=<?= intval($existReport->id) ?>','_blank')">
                    <span class='material-icons'>picture_as_pdf</span>Предпросмотр PDF
                </button>
            </div>
        <?php else: ?>
            <!-- Форма создания доклада -->
            <form class='ajaxFrm' id='create_report_form' onsubmit='return false'>
                <input type='hidden' name='ajax' value='1'>
                <input type='hidden' name='path' value='documents'>
                <input type='hidden' name='action' value='create_report'>
                <input type='hidden' name='params[act_id]' value='<?= $actId ?>'>

                <ul class='tab-pane'>
                    <li id='tab_report_main' class='active'>Основные данные</li>
                    <li id='tab_report_violations'>Нарушения (<?= count($violations) ?>)</li>
                    <?php if ($hasObjections): ?>
                        <li id='tab_report_objections'>Возражения ОК</li>
                    <?php endif; ?>
                    <li id='tab_report_agreement'>Лист согласования</li>
                </ul>

                <!-- Вкладка: Основные данные -->
                <div class='tab-panel' id='tab_report_main-panel'>
                    <div class='group'>
                        <div class='item w_50'>
                            <div class='el_data'>
                                <label>Исходящий номер доклада <span style='color:var(--color_04);font-size:11px;'>(автоматически сгенерирован, можно изменить)</span></label>
                                <input class='el_input' type='text' name='params[doc_number]' value='<?= htmlspecialchars($generatedDocNumber) ?>' placeholder='ДКЛ1000-<?= date('Y') ?>'>
                            </div>
                        </div>
                        <div class='item w_50'>
                            <div class='el_data'>
                                <label>Дата доклада</label>
                                <input class='el_input single_date' type='date' name='params[doc_date]' value='<?= date('Y-m-d') ?>'>
                            </div>
                        </div>
                    </div>
                    <div class='group'>
                        <div class='item w_100'>
                            <div class='el_data'>
                                <label>Акт направлен ОК</label>
                                <input class='el_input' type='text' name='params[act_sent_date]'
                                       placeholder='дата и номер направления, например: 14.06.2024 №20Исх-8986'
                                       value='<?= htmlspecialchars($act->doc_number ?? '') ?>'>
                            </div>
                        </div>
                    </div>
                    <div class='group'>
                        <div class='item w_100'>
                            <div class='el_data vertical'>
                                <label>Вводный текст доклада <span style='color:var(--color_04);font-size:11px'>(если оставить пустым — сформируется автоматически из данных плана и приказа)</span></label>
                                <textarea class='el_textarea' name='params[intro_text]' rows='3' placeholder='Управлением финансового контроля и аудита на основании приказа от ... №..., в период с ... по ..., проведена плановая проверка ...'></textarea>
                            </div>
                        </div>
                    </div>
                    <div class='group'>
                        <div class='item w_100 block'>
                            <div class='el_data vertical'>
                                <label>Предложения по результатам проверки</label>
                            </div>
                            <div id='proposals_container'>
                                <div class='proposal_item' data-level='1' data-parent='0'>
                                    <div style='display:flex;align-items:flex-start;gap:10px;width:100%;'>
                                        <span class='proposal_number' style='padding-top:8px;white-space:nowrap;'>1.</span>
                                        <div class='el_data' style='flex:1;width:100%;'>
                                            <textarea class='el_textarea' name='params[proposals][0][text]' rows='3' placeholder='Введите предложение...' style='width:100%;'></textarea>
                                            <input type='hidden' name='params[proposals][0][level]' value='1'>
                                            <input type='hidden' name='params[proposals][0][parent]' value='0'>
                                        </div>
                                        <div style='display:flex;gap:4px;padding-top:4px;flex-shrink:0;'>
                                            <button type='button' class='button icon add_subproposal' title='Добавить подпункт'>
                                                <span class='material-icons'>subdirectory_arrow_right</span>
                                            </button>
                                            <button type='button' class='button icon remove_proposal invisible'>
                                                <span class='material-icons'>close</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type='button' class='button icon text' id='add_proposal'>
                                <span class='material-icons'>add_circle_outline</span>Добавить предложение
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Вкладка: Нарушения -->
                <div class='tab-panel' id='tab_report_violations-panel' style='display:none'>
                    <div class='group'>
                        <div class='item w_100'>
                            <p class='hint_text'>Нарушения подтягиваются из акта автоматически. Снимите галочку, чтобы исключить нарушение из доклада.</p>
                        </div>
                    </div>
                    <?php if (count($violations) === 0): ?>
                        <div class='group'>
                            <div class='item w_100'>
                                <div class='inform_block'>
                                    <span class='material-icons'>info</span>
                                    По данному акту нарушений не зафиксировано.
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($violations as $i => $v): ?>
                            <div class='group'>
                                <div class='item w_100'>
                                    <div class='custom_checkbox'>
                                        <label class='container'>
                                            <input type='checkbox' name='params[violation_ids][]' value='<?= $i ?>' checked>
                                            <span class='checkmark'></span>
                                        </label>
                                    </div>
                                    <input type='hidden' name='params[violation_texts][]' value='<?= htmlspecialchars($v['text']) ?>'>
                                    <div class='violation_text'>
                                        <strong><?= ($i + 1) . '. ' . htmlspecialchars($v['text']) ?></strong>
                                        <div class='hint_text'>Ответ: <?= htmlspecialchars($v['answer']) ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Вкладка: Возражения ОК (если есть) -->
                <?php if ($hasObjections): ?>
                    <div class='tab-panel' id='tab_report_objections-panel' style='display:none'>
                        <div class='group'>
                            <div class='item w_50'>
                                <div class='el_data'>
                                    <label>Дата возражений</label>
                                    <input class='el_input' type='text' readonly value='<?= htmlspecialchars($objections['date'] ?? '') ?>'>
                                </div>
                            </div>
                        </div>
                        <div class='group'>
                            <div class='item w_100'>
                                <div class='el_data vertical'>
                                    <label>Текст возражений ОК</label>
                                    <textarea class='el_textarea' rows='5' readonly><?= htmlspecialchars($objections['text'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class='group'>
                            <div class='item w_100'>
                                <div class='custom_checkbox'>
                                    <label class='container'>
                                        <input type='checkbox' name='params[include_objections]' value='1' checked>
                                        <span class='checkmark'></span>
                                        Включить раздел «Возражения ОК» в доклад
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Вкладка: Лист согласования -->
                <div class='tab-panel' id='tab_report_agreement-panel' style='display:none'>
                    <div class='group'>
                        <div class='item w_100'>
                            <p class='hint_text'>Укажите согласовантов. Министр будет добавлен автоматически последним подписантом.</p>
                        </div>
                    </div>
                    <div class='group agreement_list_group'>
                        <?= $reg->renderAddAgreement(
                            ['field_name' => 'agreementlist'],
                            ['agreementlist' => 'null'],
                            ''
                        ) ?>
                    </div>
                </div>

                <div class='confirm'>
                    <button type='button' class='button icon close'>
                        <span class='material-icons'>close</span>Закрыть
                    </button>
                    <button type='button' class='button icon text' id='btn_create_report'>
                        <span class='material-icons'>description</span>Создать доклад
                    </button>
                </div>
            </form>
        <?php endif; ?>

    </div>
</div>

<script src='/js/assets/agreement_list.js'></script>
<script>
    (function () {
        agreement_list.agreement_list_init();
        el_app.mainInit();

        var proposalIndex = 0;

        // Функция обновления нумерации предложений с учётом иерархии
        function updateProposalNumbers() {
            var mainCounter = 0;
            var subCounters = {};

            $('#proposals_container .proposal_item').each(function(idx) {
                var $item = $(this);
                var level = parseInt($item.attr('data-level'));
                var parent = parseInt($item.attr('data-parent'));
                var number = '';

                if (level === 1) {
                    mainCounter++;
                    subCounters[mainCounter] = 0;
                    number = mainCounter + '.';
                    $item.attr('data-main-number', mainCounter);
                } else if (level === 2) {
                    var parentMain = $item.prevAll('.proposal_item[data-level="1"]').first().attr('data-main-number');
                    if (!parentMain) parentMain = mainCounter;
                    if (!subCounters[parentMain]) subCounters[parentMain] = 0;
                    subCounters[parentMain]++;
                    number = parentMain + '.' + subCounters[parentMain] + '.';
                }

                $item.find('.proposal_number').text(number);

                // Обновляем индексы в именах полей
                var $textarea = $item.find('textarea');
                var $levelInput = $item.find('input[name*="[level]"]');
                var $parentInput = $item.find('input[name*="[parent]"]');

                $textarea.attr('name', 'params[proposals][' + idx + '][text]');
                $levelInput.attr('name', 'params[proposals][' + idx + '][level]').val(level);
                $parentInput.attr('name', 'params[proposals][' + idx + '][parent]').val(parent);
            });

            // Показываем/скрываем кнопки удаления
            var totalItems = $('#proposals_container .proposal_item').length;
            if (totalItems > 1) {
                $('.remove_proposal').removeClass('invisible');
            } else {
                $('.remove_proposal').addClass('invisible');
            }
        }

        // Добавление нового основного предложения
        $('#add_proposal').on('click', function(e) {
            e.preventDefault();
            proposalIndex++;

            var $newProposal = $('<div>', {
                'class': 'proposal_item',
                'data-level': '1',
                'data-parent': '0'
            });

            var $wrapper = $('<div>', {'style': 'display:flex;align-items:flex-start;gap:10px;width:100%;'});
            $wrapper.append($('<span>', {'class': 'proposal_number', 'style': 'padding-top:8px;white-space:nowrap;', 'text': '1.'}));

            var $dataWrap = $('<div>', {'class': 'el_data', 'style': 'flex:1;width:100%;'});
            $dataWrap.append($('<textarea>', {
                'class': 'el_textarea',
                'name': 'params[proposals][' + proposalIndex + '][text]',
                'rows': 3,
                'placeholder': 'Введите предложение...',
                'style': 'width:100%;'
            }));
            $dataWrap.append($('<input>', {
                'type': 'hidden',
                'name': 'params[proposals][' + proposalIndex + '][level]',
                'value': '1'
            }));
            $dataWrap.append($('<input>', {
                'type': 'hidden',
                'name': 'params[proposals][' + proposalIndex + '][parent]',
                'value': '0'
            }));
            $wrapper.append($dataWrap);

            var $buttons = $('<div>', {'style': 'display:flex;gap:4px;padding-top:4px;flex-shrink:0;'});
            $buttons.append($('<button>', {
                'type': 'button',
                'class': 'button icon add_subproposal',
                'title': 'Добавить подпункт',
                'html': '<span class="material-icons">subdirectory_arrow_right</span>'
            }));
            $buttons.append($('<button>', {
                'type': 'button',
                'class': 'button icon remove_proposal',
                'html': '<span class="material-icons">close</span>'
            }));
            $wrapper.append($buttons);

            $newProposal.append($wrapper);
            $('#proposals_container').append($newProposal);

            updateProposalNumbers();
        });

        // Добавление подпункта
        $(document).on('click', '.add_subproposal', function(e) {
            e.preventDefault();
            e.stopPropagation();

            proposalIndex++;
            var $parentItem = $(this).closest('.proposal_item');
            var parentLevel = parseInt($parentItem.attr('data-level'));

            // Подпункт можно добавить только к пункту уровня 1
            if (parentLevel !== 1) {
                alert('Подпункты можно добавлять только к основным пунктам');
                return;
            }

            var $newSubproposal = $('<div>', {
                'class': 'proposal_item',
                'data-level': '2',
                'data-parent': '1'
            });

            var $wrapper = $('<div>', {'style': 'display:flex;align-items:flex-start;gap:10px;width:100%;'});
            $wrapper.append($('<span>', {'class': 'proposal_number', 'style': 'padding-top:8px;white-space:nowrap;padding-left:30px;', 'text': '1.1.'}));

            var $dataWrap = $('<div>', {'class': 'el_data', 'style': 'flex:1;width:100%;'});
            $dataWrap.append($('<textarea>', {
                'class': 'el_textarea',
                'name': 'params[proposals][' + proposalIndex + '][text]',
                'rows': 3,
                'placeholder': 'Введите подпункт...',
                'style': 'width:100%;'
            }));
            $dataWrap.append($('<input>', {
                'type': 'hidden',
                'name': 'params[proposals][' + proposalIndex + '][level]',
                'value': '2'
            }));
            $dataWrap.append($('<input>', {
                'type': 'hidden',
                'name': 'params[proposals][' + proposalIndex + '][parent]',
                'value': '1'
            }));
            $wrapper.append($dataWrap);

            var $buttons = $('<div>', {'style': 'display:flex;gap:4px;padding-top:4px;flex-shrink:0;'});
            $buttons.append($('<button>', {
                'type': 'button',
                'class': 'button icon remove_proposal',
                'html': '<span class="material-icons">close</span>'
            }));
            $wrapper.append($buttons);

            $newSubproposal.append($wrapper);

            // Вставляем подпункт после последнего подпункта родителя или после самого родителя
            var $nextMain = $parentItem.nextAll('.proposal_item[data-level="1"]').first();
            if ($nextMain.length > 0) {
                $nextMain.before($newSubproposal);
            } else {
                $('#proposals_container').append($newSubproposal);
            }

            updateProposalNumbers();
        });

        // Удаление предложения
        $(document).on('click', '.remove_proposal', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $item = $(this).closest('.proposal_item');
            var level = parseInt($item.attr('data-level'));

            // Если удаляем основной пункт, удаляем и все его подпункты
            if (level === 1) {
                var $subsToRemove = $item.nextUntil('.proposal_item[data-level="1"]');
                $subsToRemove.remove();
            }

            $item.remove();
            updateProposalNumbers();
        });

        // Создание доклада
        $('#btn_create_report').on('click', function () {
            var $btn = $(this).prop('disabled', true).find('.material-icons').text('hourglass_empty');

            $.post('/', $('#create_report_form').serialize(), function (data) {
                try {
                    var r = JSON.parse(data);
                    inform(r.resultText, r.result);
                    if (r.result) {
                        setTimeout(function () {
                            el_app.dialog_close();
                            // Открываем лист согласования созданного доклада
                            if (r.report_id) {
                                el_app.dialog_open('documents', 'agreement',
                                    {docId: r.report_id}, true);
                            }
                        }, 1000);
                    } else {
                        $('#btn_create_report').prop('disabled', false)
                            .find('.material-icons').text('description');
                    }
                } catch (e) {
                    inform(data, false);
                }
            });
        });
    })();
</script>