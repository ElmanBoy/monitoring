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

$html        = '';
$html_header = '';
$user_signs  = [];

$docId = intval($_POST['params']['docId']);
$insId = intval($_POST['params']['insId']);

// Проверяем что график для этого акта ещё не создан
$existRoad = $db->selectOne('agreement', ' WHERE documentacial = 5 AND source_id = ?', [$docId]);
if ($existRoad) {
    // Уже создан — перенаправляем на просмотр
    echo '<script>el_app.dialog_open("view_road", {roadId: ' . $existRoad->id . '}, "roadmap");</script>';
    exit;
}

$tmpl = $db->selectOne('documents', ' where id = ?', [18]);
$docs = $db->selectOne('agreement', ' WHERE id = ?', [$tmpl->consultation]);
$signs = $db->select('signs', " where table_name = 'agreement' AND doc_id = ?", [$docId]);
$ins  = $db->selectOne('institutions', ' WHERE id = ?', [$insId]);

// Получение периода проверки по учреждению
$checkArr   = $db->select('checkstaff', ' WHERE institution = ?', [$insId]);
$dateResults = [];
foreach ($checkArr as $chr) {
    $dateResults[] = $date->getMinMaxDates($chr->dates);
}
$allMinDates = array_column($dateResults, 'min');
$allMaxDates = array_column($dateResults, 'max');
$globalMin   = count($allMinDates) ? $date->correctDateFormatFromMysql(min($allMinDates)) : '';
$globalMax   = count($allMaxDates) ? $date->correctDateFormatFromMysql(max($allMaxDates)) : '';

$header_vars = [
    'institution'        => $ins->name,
    'institution_short'  => $ins->short,
    'institution_legal'  => $ins->legal,
    'institution_phones' => $ins->phones,
    'institution_head'   => $ins->leader,
    'shedule_date'       => date('Y-m-d'),
    'check_period_start' => $globalMin,
    'check_period_end'   => $globalMax,
    'shedule_number'     => '____________',
];

if (count($signs) > 0) {
    foreach ($signs as $s) {
        $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
    }
}

$users          = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name', 'position']);
$initiator_fio  = ($users['array'][$tmpl->initiator][0] ?? '') . ' ' .
    ($users['array'][$tmpl->initiator][1] ?? '') . ' ' .
    ($users['array'][$tmpl->initiator][2] ?? '');

if (strlen($tmpl->header ?? '') > 0) {
    $html_header = $temp->twig_parse($tmpl->header, $header_vars);
}
if (strlen($tmpl->body ?? '') > 0) {
    $html .= $temp->twig_parse($tmpl->body, $header_vars);
}
if (strlen($tmpl->bottom ?? '') > 0) {
    $html .= $temp->twig_parse($tmpl->bottom, $header_vars);
}

// Нарушения — берём по учреждению через checkstaff (исправлен hardcode [5])
$tids = [];
$taskRows = $db->select('checkstaff', ' WHERE institution = ?', [$insId]);
if ($taskRows) {
    foreach ($taskRows as $task) {
        $tids[] = $task->id;
    }
}
$violations = [];
if (count($tids) > 0) {
    $violations = $db->db::getAll(
        'SELECT * FROM ' . TBL_PREFIX . 'checksviolations WHERE tasks IN (' . implode(',', $tids) . ') ORDER BY id'
    );
}
?>
<style>
    .schedule_tbl, .schedule_tbl tr td, .schedule_tbl tr th {
        border: 1px solid grey;
        border-collapse: collapse;
    }
    .schedule_tbl tr td, .schedule_tbl tr th { padding: 3px; }
    .schedule_tbl tr td input, .schedule_tbl tr td textarea,
    .schedule_tbl tr td .el_input, .schedule_tbl tr td .el_data { display: block; }
    textarea.el_input { min-height: 100px; width: 100%; }
</style>

<div class="pop_up drag" style="width: 70vw; min-height: 70vh;">
    <div class="title handle">
        <div class="name">Создание графика устранения нарушений</div>
        <div class="button icon close"><span class="material-icons">close</span></div>
    </div>
    <div class="pop_up_body">
        <form class="ajaxFrm noreset" id="add_road" onsubmit="return false">
            <input type="hidden" name="doc_id" value="<?= $docId ?>">
            <input type="hidden" name="ins_id" value="<?= $insId ?>">

            <div class="group">
                <div class="item w_100">
                    <div class="el_data">
                        <label>Верх документа</label>
                        <textarea name="header"><?= $html_header ?></textarea>
                    </div>
                </div>

                <table class="schedule_tbl" style="width:100%">
                    <tr>
                        <th style="width:30px">№</th>
                        <th style="width:28%">Нарушение / Предложения по устранению</th>
                        <th>Действия, необходимые для устранения</th>
                        <th style="width:110px">Срок устранения</th>
                        <th style="width:18%">Ответственный</th>
                        <th style="width:24px"></th>
                    </tr>
                    <?php if (!empty($violations)):
                        $num = 1;
                        foreach ($violations as $v): ?>
                            <tr class="schedule_row">
                                <td><div class="el_data"><?= $num ?></div></td>
                                <td>
                                    <div class="el_data">
                                        <input type="hidden" name="violation_id[]" value="<?= intval($v['id']) ?>">
                                        <input type="hidden" name="schedule_offers[]" value="<?= htmlspecialchars($v['name']) ?>">
                                        <?= htmlspecialchars($v['name']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="el_data">
                                        <textarea class="el_input" name="schedule_actions[]"></textarea>
                                    </div>
                                </td>
                                <td>
                                    <div class="el_data">
                                        <input type="date" class="el_input" name="schedule_deadlines[]">
                                    </div>
                                </td>
                                <td>
                                    <div class="el_data">
                                        <input type="text" class="el_input" name="schedule_responsible[]" placeholder="ФИО">
                                    </div>
                                </td>
                                <td>
                                    <div class="button icon clear" style="display:none">
                                        <span class="material-icons">close</span>
                                    </div>
                                </td>
                            </tr>
                            <?php $num++; endforeach;
                    else: ?>
                        <tr class="schedule_row">
                            <td><div class="el_data">1</div></td>
                            <td>
                                <div class="el_data">
                                    <input type="hidden" name="violation_id[]" value="">
                                    <textarea class="el_input" name="schedule_offers[]" placeholder="Нарушение"></textarea>
                                </div>
                            </td>
                            <td>
                                <div class="el_data">
                                    <textarea class="el_input" name="schedule_actions[]"></textarea>
                                </div>
                            </td>
                            <td>
                                <div class="el_data">
                                    <input type="date" class="el_input" name="schedule_deadlines[]">
                                </div>
                            </td>
                            <td>
                                <div class="el_data">
                                    <input type="text" class="el_input" name="schedule_responsible[]" placeholder="ФИО">
                                </div>
                            </td>
                            <td>
                                <div class="button icon clear" style="display:none">
                                    <span class="material-icons">close</span>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>

                <?php if (empty($violations)): ?>
                    <button class="button icon text new_schedule_row" style="margin-top:6px">
                        <span class="material-icons">add</span>Ещё строка
                    </button>
                <?php endif; ?>
            </div>

            <div class="confirm">
                <button class="button icon text">
                    <span class="material-icons">save</span>Сохранить
                </button>
            </div>
        </form>
    </div>
</div>

<script src="/js/assets/cades_sign.js"></script>
<script>
    el_app.mainInit();

    tinymce.init({
        target: document.querySelector("[name='header']"),
        language: 'ru',
        plugins: 'code link table autoresize lists',
        width: '100%',
        license_key: 'gpl',
        branding: false,
        statusbar: false,
        menubar: false,
        extended_valid_elements: 'code[*]',
        protect: [/\{\{.*?\}\}/g, /\{%.*?%\}/g],
        toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link table | code',
    });

    // Удаление добавленных вручную строк
    $(document).on('click', '.schedule_row .button.clear', function () {
        $(this).closest('tr.schedule_row').remove();
    });
</script>