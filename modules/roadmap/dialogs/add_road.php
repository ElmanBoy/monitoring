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

$docId    = intval($_POST['params']['docId']);
$insId    = intval($_POST['params']['insId']);
$reportId = intval($_POST['params']['reportId'] ?? 0);

// Проверяем что график для этого акта ещё не создан
$existRoad = $db->selectOne('agreement', ' WHERE documentacial = 5 AND source_id = ?', [$docId]);
if ($existRoad) {
    echo '<script>el_app.dialog_open("view_road", {roadId: ' . $existRoad->id . '}, "roadmap");</script>';
    exit;
}

// Получаем предложения из согласованного доклада
$report    = $reportId > 0 ? $db->selectOne('agreement', ' WHERE id = ? AND documentacial = 4 AND status = 1', [$reportId]) : null;
if (!$report) {
    // Доклад не найден или не согласован — создание невозможно
    echo '<div class="pop_up" style="width:400px">
        <div class="title"><div class="name">Ошибка</div><div class="button icon close"><span class="material-icons">close</span></div></div>
        <div class="pop_up_body" style="padding:20px">
            Невозможно создать график: сначала необходимо согласовать доклад о результатах проверки.
        </div>
    </div>';
    exit;
}

$bodyData  = json_decode($report->body ?? '{}', true) ?: [];
$proposals = [];
if (isset($bodyData['proposals']) && is_array($bodyData['proposals'])) {
    // Предложения могут быть массивом объектов с полями text, level, parent
    // или просто массивом строк
    foreach ($bodyData['proposals'] as $p) {
        if (is_array($p) && isset($p['text'])) {
            // Структурированное предложение - извлекаем текст
            $text = trim($p['text']);
            if (strlen($text) > 0) {
                $proposals[] = $text;
            }
        } elseif (is_string($p)) {
            // Простая строка
            $text = trim($p);
            if (strlen($text) > 0) {
                $proposals[] = $text;
            }
        }
    }
} elseif (isset($bodyData['proposals_text']) && strlen($bodyData['proposals_text']) > 0) {
    $proposals = array_values(array_filter(array_map('trim', explode("\n", $bodyData['proposals_text'])), fn($p) => strlen($p) > 0));
}

if (empty($proposals)) {
    echo '<div class="pop_up" style="width:400px">
        <div class="title"><div class="name">Ошибка</div><div class="button icon close"><span class="material-icons">close</span></div></div>
        <div class="pop_up_body" style="padding:20px">
            В докладе не указаны предложения по устранению нарушений. Отредактируйте доклад и добавьте предложения.
        </div>
    </div>';
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

// Предложения берутся из согласованного доклада (уже заполнены выше в $proposals)
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

                <p style="color:var(--color_04);font-size:13px;margin:4px 0 8px">
                    Предложения по устранению взяты из согласованного доклада и не могут быть изменены.
                    Заполните действия, сроки и ответственных.
                </p>
                <table class="schedule_tbl" style="width:100%">
                    <tr>
                        <th style="width:30px">№</th>
                        <th style="width:35%">Предложение по устранению (из доклада)</th>
                        <th>Действия, необходимые для устранения</th>
                        <th style="width:110px">Срок устранения</th>
                        <th style="width:18%">Ответственный</th>
                    </tr>
                    <?php foreach ($proposals as $num => $proposal): ?>
                        <tr class="schedule_row">
                            <td><div class="el_data"><?= $num + 1 ?></div></td>
                            <td>
                                <div class="el_data">
                                    <input type="hidden" name="violation_id[]" value="">
                                    <input type="hidden" name="schedule_offers[]" value="<?= htmlspecialchars($proposal) ?>">
                                    <?= htmlspecialchars($proposal) ?>
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
                        </tr>
                    <?php endforeach; ?>
                </table>
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