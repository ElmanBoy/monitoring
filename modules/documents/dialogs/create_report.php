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
use Core\Gui;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db();
$auth = new Auth();
$date = new Date();
$gui = new Gui();

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
$existReport = $db->selectOne('agreement', ' WHERE documentacial = 8 AND source_id = ?', [$actId]);

// Нарушения по акту: ищем через checkstaff по ins_id и plan_id акта
$insId = intval($act->ins_id ?? $act->source_id ?? 0);
$planId = intval($act->plan_id ?? 0);

$violations = [];
if ($insId > 0) {
    $staffRows = [];
    if ($planId > 0) {
        $plan = $db->selectOne('checksplans', ' WHERE id = ?', [$planId]);
        if ($plan && strlen($plan->uid ?? '') > 0) {
            $staffRows = $db->select('checkstaff', ' WHERE check_uid = ? AND institution = ?',
                [$plan->uid, $insId]
            );
        }
    }
    if (count($staffRows) > 0) {
        $taskIds = array_map(function ($r) {
            return intval($r->id);
        }, $staffRows
        );
        $violations = $db->db::getAll(
            'SELECT * FROM ' . TBL_PREFIX . 'checksviolations WHERE tasks IN (' .
            implode(',', $taskIds) . ') ORDER BY id'
        );
    }
}

// Возражения ОК
$objections = json_decode($act->objections ?? '{}', true);
$hasObjections = !empty($objections['text']) || !empty($objections['files']);

// Пользователи для выбора листа согласования
$users = $db->getRegistry('users', " WHERE active = 1 AND roles NOT LIKE '%2%'",
    [], ['surname', 'name', 'middle_name']
);
?>
<div class='pop_up drag' style='width:65vw;min-height:70vh'>
    <div class='title handle'>
        <div class='name'>Доклад министру — «<?= htmlspecialchars($act->name) ?>»</div>
        <div class='button icon close'><span class='material-icons'>close</span></div>
    </div>
    <div class='pop_up_body'>

        <?php if ($existReport): ?>
            <!-- Доклад уже создан — показываем статус и кнопку согласования -->
            <div class='group'>
                <div class='item'>
                    <div class='inform_block <?= intval($existReport->status) === 1 ? 'inform_success' : '' ?>'>
                        <span class='material-icons'><?= intval($existReport->status) === 1 ? 'task_alt' : 'pending' ?></span>
                        Доклад <?= intval($existReport->status) === 1 ? 'утверждён министром' : 'на согласовании' ?>.
                        <?php if (strlen($existReport->doc_number ?? '') > 0): ?>
                            Номер: <strong><?= htmlspecialchars($existReport->doc_number) ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class='group'>
                <div class='item'>
                    <button class='button icon text' onclick="
                            el_app.dialog_open('documents', 'agreement',
                            {docId: <?= intval($existReport->id) ?>}, true)">
                        <span class='material-icons'>how_to_vote</span>Открыть лист согласования
                    </button>
                    <button class='button icon text' style='margin-left:8px' onclick="
                            window.open('/?ajax=1&mode=popup&module=documents&url=report_pdf&params[docId]=<?= intval($existReport->id) ?>','_blank')">
                        <span class='material-icons'>picture_as_pdf</span>Предпросмотр PDF
                    </button>
                </div>
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
                                <label>Исходящий номер доклада</label>
                                <input class='el_input' type='text' name='params[doc_number]'
                                       placeholder='20Исх-XXXX'>
                            </div>
                        </div>
                        <div class='item w_50'>
                            <div class='el_data'>
                                <label>Дата доклада</label>
                                <input class='el_input single_date' type='date'
                                       name='params[doc_date]' value='<?= date('Y-m-d') ?>'>
                            </div>
                        </div>
                    </div>
                    <div class='group'>
                        <div class='item'>
                            <div class='el_data'>
                                <label>Акт направлен ОК</label>
                                <input class='el_input' type='text' name='params[act_sent_date]'
                                       placeholder='дата и номер направления, например: 14.06.2024 №20Исх-8986'
                                       value='<?= htmlspecialchars($act->doc_number ?? '') ?>'>
                            </div>
                        </div>
                    </div>
                    <div class='group'>
                        <div class='item'>
                            <div class='el_data'>
                                <label>Вводный текст доклада <span style='color:var(--color_04);font-size:11px'>(можно оставить по умолчанию)</span></label>
                                <textarea class='el_input' name='params[intro_text]' rows='5'
                                          style='width:100%;resize:vertical'><?= htmlspecialchars($act->brief ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class='group'>
                        <div class='item'>
                            <div class='el_data'>
                                <label>Предложения по результатам проверки</label>
                                <textarea class='el_input' name='params[proposals_text]' rows='6'
                                          style='width:100%;resize:vertical'
                                          placeholder='Предложения и рекомендации для ОК...'></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Вкладка: Нарушения -->
                <div class='tab-panel' id='tab_report_violations-panel' style='display:none'>
                    <div class='group'>
                        <div class='item'>
                            <p style='color:var(--color_04);font-size:12px'>
                                Нарушения подтягиваются из акта автоматически. Снимите галочку, чтобы исключить
                                нарушение из доклада.
                            </p>
                        </div>
                    </div>
                    <?php if (count($violations) === 0): ?>
                        <div class='group'>
                            <div class='item'>
                                <div class='inform_block'>
                                    <span class='material-icons'>info</span>
                                    По данному акту нарушений не зафиксировано.
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($violations as $i => $v): ?>
                            <div class='group' style='border-bottom:1px solid var(--border_color);padding-bottom:8px'>
                                <div class='item' style='display:flex;gap:12px;align-items:flex-start'>
                                    <input type='checkbox'
                                           name='params[violation_ids][]'
                                           value='<?= intval($v['id']) ?>'
                                           checked
                                           style='margin-top:4px;flex-shrink:0'>
                                    <div>
                                        <div style='font-weight:500'><?= ($i + 1) . '. ' . htmlspecialchars($v['name'] ?? '') ?></div>
                                        <?php if (!empty($v['violations'])): ?>
                                            <div style='color:var(--color_04);font-size:12px;margin-top:4px'>
                                                Тип: <?= htmlspecialchars($v['violations']) ?>
                                            </div>
                                        <?php endif; ?>
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
                            <div class='item'>
                                <label>Дата возражений</label>
                                <div class='el_value'><?= htmlspecialchars($objections['date'] ?? '') ?></div>
                            </div>
                        </div>
                        <div class='group'>
                            <div class='item'>
                                <label>Текст возражений ОК</label>
                                <div style='background:var(--bg_02);padding:12px;border-radius:6px;white-space:pre-wrap'>
                                    <?= htmlspecialchars($objections['text'] ?? 'Нет текста') ?>
                                </div>
                            </div>
                        </div>
                        <div class='group'>
                            <div class='item'>
                                <label>Включить возражения в текст доклада</label>
                                <label class='container'>
                                    <input type='checkbox' name='params[include_objections]' value='1' checked>
                                    <span class='checkmark'></span>
                                    Да, включить раздел «Возражения ОК» в доклад
                                </label>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Вкладка: Лист согласования -->
                <div class='tab-panel' id='tab_report_agreement-panel' style='display:none'>
                    <div class='group'>
                        <div class='item'>
                            <p>Укажите согласовантов. Министр будет добавлен автоматически последним подписантом.</p>
                        </div>
                    </div>
                    <div class='group'>
                        <div class='item'>
                            <select data-label='Согласующие (можно выбрать несколько)'
                                    name='params[signers][]' multiple style='height:150px'>
                                <?= $gui->buildSelectFromRegistry($users['result'], [], true,
                                    ['surname', 'name', 'middle_name']
                                ) ?>
                            </select>
                        </div>
                    </div>
                    <div class='group'>
                        <div class='item w_50'>
                            <label>Тип согласования</label>
                            <select name='params[list_type]' class='el_input'>
                                <option value='2'>Параллельное</option>
                                <option value='1'>Последовательное</option>
                            </select>
                        </div>
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

<script>
    (function () {
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