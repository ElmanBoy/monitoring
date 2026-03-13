<?php

use Dompdf\Dompdf;
use Dompdf\Options;
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
$docId      = intval($_POST['params']['docId']);
$is_inst    = boolval($_POST['params']['is_inst'] ?? false);
$user_signs = [];

$tmpl  = $db->selectOne('agreement', ' WHERE id = ?', [$docId]);
$signs = $db->select('signs', " WHERE table_name = 'agreement' AND doc_id = ?", [$docId]);

if (count($signs) > 0) {
    foreach ($signs as $s) {
        $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
    }
}

$users        = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name', 'position']);
$urgent_types = $db->getRegistry('urgent_types');

$initiator_fio      = ($users['array'][$tmpl->initiator][0] ?? '') . ' ' .
    ($users['array'][$tmpl->initiator][1] ?? '') . ' ' .
    ($users['array'][$tmpl->initiator][2] ?? '');
$initiator_position = $users['array'][$tmpl->initiator][3] ?? '';

function buildArgeementList($itemArr, $section, $users, $urgent_types, $user_signs, $level = 0): string
{
    $html      = '';
    $rowNumber = 1;
    $itemArr   = is_string($itemArr) ? json_decode($itemArr, true) : $itemArr;
    $start     = ($level == 0) ? 1 : 0;

    for ($l = $start; $l < count($itemArr); $l++) {
        $user_fio = ($users['array'][$itemArr[$l]['id']][0] ?? '') . ' ' .
            mb_substr($users['array'][$itemArr[$l]['id']][1] ?? '', 0, 1) . '. ' .
            mb_substr($users['array'][$itemArr[$l]['id']][2] ?? '', 0, 1) . '.';

        if ($level == 0) {
            if (is_array($itemArr[$l - 1]['redirect'] ?? null)) {
                $rowNumber = ($rowNumber - 1);
            }
        }

        $result_id   = intval($itemArr[$l]['result']['id'] ?? 0);
        $result_date = $itemArr[$l]['result']['date'] ?? '';
        $comment     = htmlspecialchars($itemArr[$l]['comment'] ?? '');

        switch ($result_id) {
            case 1:  $result_text = '<span style="color:#086a9b">Подписано с ЭП<br>' . $result_date . '</span>'; break;
            case 2:  $result_text = '<span style="color:#086a9b">Согласовано с ЭП<br>' . $result_date . '</span>'; break;
            case 3:  $result_text = '<span style="color:#2e7d32">Согласовано<br>' . $result_date . '</span>'; break;
            case 4:  $result_text = '<span style="color:#e65100">Перенаправлено<br>' . $result_date . '</span>'; break;
            case 5:  $result_text = '<span style="color:#c62828">Отклонено<br>' . $result_date . '</span>'; break;
            default: $result_text = '—'; break;
        }

        $html .= '<tr'
            . ($level > 0 ? ' class="redirected"' : '') . '>'
            . '<td>' . ($level == 0 ? $rowNumber . (is_array($itemArr[$l - 1]['redirect'] ?? null) ? '.' . ($l - 1) : '') : '') . '</td>'
            . '<td' . ($level > 0 ? ' style="padding-left:' . (15 * $level) . 'px"' : '') . '>' . $user_fio . '</td>'
            . '<td class="center">' . ($urgent_types[$itemArr[0]['urgent'] ?? ''] ?? '') . '</td>'
            . '<td class="center">' . $result_text . '</td>'
            . '<td>' . $comment . '</td>'
            . '</tr>';

        // Рекурсия для перенаправлений
        if (is_array($itemArr[$l]['redirect'] ?? null)) {
            $html .= buildArgeementList($itemArr[$l]['redirect'], $section, $users, $urgent_types, $user_signs, $level + 1);
        }

        if ($level == 0) $rowNumber++;
    }
    return $html;
}

$orientation     = 'portrait';
$footer_position = 820;

$html .= '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { font-family: "DejaVu Sans", sans-serif; font-size: 11pt; }
    body { margin: 0; padding: 0 20px; }
    footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8pt; color: #666; border-top: 1px solid #ccc; padding: 3px 10px; display: flex; justify-content: space-between; align-items: center; }
    main { padding-bottom: 40px; }
    .agreement_list { margin: 10px 0; font-size: 95%; }
    .agreement_list h4 { font-weight: 600; font-size: 14px; margin: 0; }
    .agreement_list table { width: 100%; margin: 0; }
    .agreement_list table, .agreement_list table td, .agreement_list table th {
        background-color: #fff; border-collapse: collapse; border: 1px solid #c5d4fc; font-size: 95%; vertical-align: middle;
    }
    .agreement_list table td { padding: 5px; line-height: 10px; }
    .agreement_list table th { padding: 5px; background-color: #e5e5e5; color: #4f6396; text-align: center; }
    .agreement_list table td.divider { font-size: 80%; background-color: #dde8f7; padding: 0 2px; line-height: 10px; }
    .agreement_list table td.center { text-align: center; }
    .agreement_list .list_type { text-align: right; margin-top: -25px; }
    table.schedule_pdf { width: 100%; border-collapse: collapse; margin-top: 10px; }
    table.schedule_pdf th, table.schedule_pdf td { border: 1px solid #999; padding: 5px; font-size: 10pt; vertical-align: top; }
    table.schedule_pdf th { background: #e5e5e5; text-align: center; font-weight: 600; }
    .fix-done { color: #2e7d32; }
    .fix-pending { color: #1565c0; }
    .fix-overdue { color: #c62828; }
</style>
<title>Документ</title>
</head>
<body>
<footer>Документ создан в электронной форме. №&nbsp;' . ($tmpl->doc_number ?? '') . ' от ' .
    $date->correctDateFormatFromMysql($tmpl->created_at) . '. Исполнитель: ' . $initiator_fio .
    '<img src="data:image/png;base64,' . $temp->bottom_logo . '"></footer>
<main>';

$documentacial = intval($tmpl->documentacial);

if ($documentacial == 3 || $documentacial == 0) {
    $orientation     = 'landscape';
    $footer_position = 576;
}

if ($documentacial == 6) {
    // Лист согласования
    $agreementlist = json_decode($tmpl->agreementlist ?? '[]', true) ?: [];
    $list_types    = [];
    foreach ($agreementlist as $section) {
        $lt = $section[0]['list_type'] ?? null;
        if ($lt && !in_array($lt, $list_types)) $list_types[] = $lt;
    }

    $html .= 'Лист согласования к документу ' . htmlspecialchars($tmpl->consultation ?? '') . '<br>' .
        'Инициатор согласования: ' . $initiator_fio . ' ' . $initiator_position . '<br>' .
        'Согласование инициировано: ' . ($tmpl->initiation ?? '');

    $html .= '<div class="agreement_list"><h4>ЛИСТ СОГЛАСОВАНИЯ</h4>'
        . '<div class="list_type">Тип согласования: <strong>'
        . (count($list_types) > 1 ? 'смешанное' : ($list_types[0] == '1' ? 'последовательное' : 'параллельное'))
        . '</strong></div>'
        . '<table><tr><th>№</th><th style="width:30%">ФИО</th>'
        . '<th style="width:20%">Срок согласования</th>'
        . '<th style="width:25%">Результат согласования</th>'
        . '<th>Комментарии</th></tr>';

    for ($i = 0; $i < count($agreementlist); $i++) {
        $itemArr = $agreementlist[$i];
        if (is_string($itemArr)) $itemArr = json_decode($itemArr, true);
        $html .= '<tr><td class="divider" colspan="5">'
            . (isset($itemArr[0]['stage']) ? '<strong>Этап ' . $itemArr[0]['stage'] . '</strong><br>' : '')
            . 'Тип согласования: <strong>'
            . (($itemArr[0]['list_type'] ?? '') == '1' ? 'последовательное' : 'параллельное')
            . '</strong></td>';
        $html .= buildArgeementList($itemArr, $i, $users, $urgent_types, $user_signs);
    }
    $html .= '</table></div>';

} elseif ($documentacial == 5) {
    // График устранения нарушений — печатная форма
    $schedule = json_decode($tmpl->agreementlist ?? '[]', true) ?: [];

    if (strlen($tmpl->header ?? '') > 0) {
        $html .= $temp->twig_parse($tmpl->header, [], []);
    }

    $html .= '<table class="schedule_pdf">
        <thead><tr>
            <th style="width:30px">№</th>
            <th style="width:28%">Нарушение / Предложения по устранению</th>
            <th>Действия для устранения</th>
            <th style="width:90px">Срок</th>
            <th style="width:15%">Ответственный</th>
            <th style="width:90px">Статус</th>
        </tr></thead><tbody>';

    $fixLabels = [0 => 'Не устранено', 1 => 'На проверке', 2 => 'Снято', 3 => 'Возврат', 4 => 'Просрочено'];
    $num = 1;
    foreach ($schedule as $row) {
        $fixSt = intval($row['fix_status'] ?? 0);
        $dl    = $row['deadline_extended'] ?? $row['schedule_deadlines'] ?? null;
        $isOv  = $dl && strtotime($dl) < time() && $fixSt < 2;
        $cls   = $fixSt === 2 ? 'fix-done' : ($fixSt === 1 ? 'fix-pending' : ($isOv ? 'fix-overdue' : ''));

        $html .= '<tr>
            <td style="text-align:center">' . $num . '</td>
            <td>' . htmlspecialchars($row['schedule_offers'] ?? '') . '</td>
            <td>' . htmlspecialchars($row['schedule_actions'] ?? '') . '</td>
            <td style="text-align:center">' . ($dl ? date('d.m.Y', strtotime($dl)) : '—') . '</td>
            <td>' . htmlspecialchars($row['schedule_responsible'] ?? '') . '</td>
            <td class="' . $cls . '" style="text-align:center">' . ($fixLabels[$fixSt] ?? '—') . '</td>
        </tr>';
        $num++;
    }
    $html .= '</tbody></table>';

} else {
    // Обычный документ — акт, приказ и т.д.
    if (strlen($tmpl->header ?? '') > 0) {
        $html .= $temp->twig_parse($tmpl->header, [], []);
    }
    if (strlen($tmpl->body ?? '') > 0) {
        $html .= $temp->twig_parse($tmpl->body, [], []);
    }
    if (strlen($tmpl->bottom ?? '') > 0) {
        $html .= $temp->twig_parse($tmpl->bottom, [], []);
    }
}

$html .= '</main></body></html>';

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Jost');
$options->set('defaultEncoding', 'UTF-8');
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', $orientation);
$dompdf->render();
$canvas     = $dompdf->getCanvas();
$footerText = 'Страница {PAGE_NUM} из {PAGE_COUNT}. Страница создана: ' . $date->dateToString($tmpl->created_at);
$canvas->page_text(8, $footer_position, $footerText, 'DejaVu Sans', 8, [0, 0, 0]);

if (isset($_POST['params']['outputType']) && intval($_POST['params']['outputType']) == 0) {
    echo base64_encode($dompdf->output());
} else {
    ?>
    <div class="pop_up drag" style="width: 60vw; min-height: 70vh;">
        <div class="title handle">
            <div class="name">Просмотр документа</div>
            <div class="button icon close"><span class="material-icons">close</span></div>
        </div>
        <div class="pop_up_body">
            <iframe id="pdf-viewer" width="100%" height="600px" style="min-height:80vh"></iframe>
            <div class="confirm">
                <?php if ($is_inst): ?>
                    <button class="button icon text" id="act_agree">
                        <span class="material-icons">check</span>С актом ознакомлены
                    </button>
                <?php endif; ?>
                <button class="button icon text close">
                    <span class="material-icons">close</span>Закрыть
                </button>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('pdf-viewer').src = `data:application/pdf;base64,<?= base64_encode($dompdf->output()) ?>`;

        <?php if ($is_inst): ?>
        $("#act_agree").off("click").on("click", function () {
            $('.preloader').fadeIn('fast');
            $.post("/", {
                ajax: 1, action: 'act_agree', path: 'roadmap',
                user_id: <?= intval($_SESSION['user_id']) ?>,
                act_id: <?= $docId ?>
            }, function (data) {
                let answer = JSON.parse(data);
                $('.preloader').fadeOut('fast');
                if (answer.result) {
                    inform('Отлично!', answer.resultText);
                } else {
                    el_tools.notify('error', 'Ошибка', answer.resultText);
                }
            });
        });
        <?php endif; ?>
    </script>
    <?php
}