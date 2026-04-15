<?php
/**
 * modules/documents/dialogs/report_pdf.php
 *
 * Генерация PDF-доклада министру (documentacial=8).
 * Аналог planPdf.php — рендерит HTML → DomPDF.
 *
 * POST params[docId] — id записи доклада в cam_agreement
 */

use Core\Db;
use Core\Auth;
use Core\Date;
use Core\Registry;
use Core\Templates;
use Dompdf\Dompdf;
use Dompdf\Options;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db   = new Db();
$auth = new Auth();
$date = new Date();
$reg  = new Registry();
$temp = new Templates();

if (!$auth->isLogin()) { die(); }

$docId = intval($_POST['params']['docId'] ?? $_GET['docId'] ?? 0);
if ($docId === 0) {
    echo '<script>alert("Не указан id доклада.");</script>';
    die();
}

// Загружаем доклад
$rep = $db->selectOne('agreement', ' WHERE id = ? AND documentacial = 8', [$docId]);
if (!$rep) {
    echo '<script>alert("Доклад не найден.");</script>';
    die();
}

// Загружаем акт-источник
$actId = intval($rep->source_id ?? 0);
$act   = $db->selectOne('agreement', ' WHERE id = ?', [$actId]);

$insId  = intval($rep->ins_id  ?? 0);
$planId = intval($rep->plan_id ?? 0);

// Учреждение
$ins = $insId > 0 ? $db->selectOne('institutions', ' WHERE id = ?', [$insId]) : null;
$insName      = $ins->name       ?? '';
$insNameShort = $ins->short      ?? ($ins->name_short ?? $insName);

// Шаблон доклада (из cam_documents, привязан к документу через rep->document)
$tmplDoc = intval($rep->document ?? 0) > 0
    ? $db->selectOne('documents', ' WHERE id = ?', [intval($rep->document)])
    : null;

// Министр
$minister = $reg->getMinister();
$ministerInitials = '';
$ministerFio      = '';
if ($minister) {
    $ministerInitials = trim($minister->surname) . ' ' .
        mb_substr(trim($minister->name), 0, 1) . '.' .
        mb_substr(trim($minister->middle_name), 0, 1) . '.';
    $ministerFio = trim($minister->surname) . ' ' .
        trim($minister->name) . ' ' .
        trim($minister->middle_name);
}

// Данные из поля body (сохранены при создании)
$body = json_decode($rep->body ?? '{}', true) ?: [];
$actSentDate = $body['act_sent_date'] ?? ($act->doc_number ?? '');
$inclObj     = intval($body['include_objections'] ?? 0);

// Предложения
$proposals = [];
if (!empty($body['proposals']) && is_array($body['proposals'])) {
    $proposals = array_values(array_filter($body['proposals'], function($p) { return strlen(trim($p)) > 0; }));
}

// Нарушения — из body['violations'] (сохранены при создании доклада)
$violations = $body['violations'] ?? [];

// Возражения ОК
$objections = json_decode($act->objections ?? '{}', true) ?: [];

// Дата доклада
$repDate = strlen($rep->docdate ?? '') > 0
    ? $date->dateToString($rep->docdate)
    : date('d.m.Y');

// Подписант — автор доклада
$repAuthor  = $db->selectOne('users', ' WHERE id = ?', [intval($rep->author ?? 0)]);
$authorFio  = trim(($repAuthor->surname ?? '') . ' ' .
    mb_substr(trim($repAuthor->name ?? ''), 0, 1) . '. ' .
    mb_substr(trim($repAuthor->middle_name ?? ''), 0, 1) . '.');
$authorPos  = $repAuthor->position ?? '';

// Данные приказа
$order = $planId > 0 && $insId > 0
    ? $db->selectOne('agreement', ' WHERE documentacial = 1 AND plan_id = ? AND ins_id = ?', [$planId, $insId])
    : null;
$orderNumber = $order->doc_number ?? '';
$orderDate   = $order->docdate ? $date->dateToString($order->docdate) : '';

// Период проверки из плана
$checkPeriodStart = '';
$checkPeriodEnd   = '';
$plan = $planId > 0 ? $db->selectOne('checksplans', ' WHERE id = ?', [$planId]) : null;
if ($plan) {
    $checks = json_decode($plan->checks ?? '[]', true) ?: [];
    foreach ($checks as $ch) {
        if (intval($ch['institution'] ?? $ch['institutions'] ?? 0) === $insId) {
            $pArr = explode(' - ', $ch['check_periods'] ?? '');
            $checkPeriodStart = $date->correctDateFormatFromMysql(trim($pArr[0] ?? ''));
            $checkPeriodEnd   = $date->correctDateFormatFromMysql(trim($pArr[1] ?? ''));
            break;
        }
    }
}

// ── Переменные для подстановки в шаблон ─────────────────────
$tplVars = [
    'minister_initials'  => $ministerInitials,
    'minister_fio'       => $ministerFio,
    'institution'        => $insName,
    'institution_short'  => $insNameShort,
    'order_number'       => $orderNumber,
    'order_date'         => $orderDate,
    'check_period_start' => $checkPeriodStart,
    'check_period_end'   => $checkPeriodEnd,
    'report_date'        => $repDate,
    'signer'             => $authorFio,
    'signer_position'    => $authorPos,
    'act_sent_date'      => $actSentDate,
];

// Рендерим header из шаблона (адресат)
$headerHtml = $tmplDoc ? $temp->twig_parse($tmplDoc->header ?? '', $tplVars) : '';

// Рендерим вводный абзац: если заполнен вручную — берём его,
// иначе рендерим body шаблона
$introHtml = '';
if (strlen($rep->brief ?? '') > 0) {
    $introHtml = nl2br(htmlspecialchars($rep->brief));
} elseif ($tmplDoc && strlen($tmplDoc->body ?? '') > 0) {
    $introHtml = $temp->twig_parse($tmplDoc->body, $tplVars);
}

// ── HTML для DomPDF ─────────────────────────────────────────
ob_start();
?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="utf-8">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'DejaVu Sans', Arial, sans-serif;
                font-size: 12pt;
                line-height: 1.5;
                color: #000;
                padding: 20mm 20mm 20mm 30mm;
            }
            .header-right {
                float: right;
                width: 55%;
                text-align: left;
                font-size: 11pt;
                margin-bottom: 10mm;
                border-left: 2px solid #000;
                padding-left: 6mm;
            }
            .clearfix::after { content: ''; display: table; clear: both; }
            .title-block {
                text-align: center;
                margin: 15mm 0 8mm;
            }
            .title-block .doc-title {
                font-size: 14pt;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 0.5pt;
            }
            .title-block .doc-subtitle {
                font-size: 12pt;
                font-weight: bold;
                margin-top: 4pt;
            }
            .title-block .ins-name {
                font-size: 12pt;
                font-weight: bold;
                margin-top: 2pt;
            }
            .greeting {
                margin: 8mm 0 6mm;
                font-size: 12pt;
            }
            .intro {
                text-align: justify;
                margin-bottom: 6mm;
                text-indent: 12mm;
            }
            h2.section-title {
                font-size: 12pt;
                font-weight: bold;
                margin: 8mm 0 4mm;
                text-align: center;
            }
            .violation-item {
                text-align: justify;
                text-indent: 12mm;
                margin-bottom: 4mm;
            }
            .objections-block {
                background: #f5f5f5;
                border-left: 3px solid #666;
                padding: 4mm 6mm;
                margin: 6mm 0;
                font-style: italic;
            }
            .proposals-title {
                font-weight: bold;
                margin: 10mm 0 4mm;
                text-align: center;
                font-size: 12pt;
            }
            .proposal-item {
                text-indent: 12mm;
                text-align: justify;
                margin-bottom: 3mm;
            }
            .signature-block {
                margin-top: 20mm;
                display: flex;
                justify-content: space-between;
            }
            .sig-left { width: 60%; font-size: 11pt; }
            .sig-right { width: 35%; text-align: right; font-size: 11pt; }
            table.sig-table { width: 100%; margin-top: 20mm; }
            table.sig-table td { vertical-align: top; font-size: 11pt; }
        </style>
    </head>
    <body>

    <!-- Шапка: адресат справа -->
    <?php if (strlen($headerHtml) > 0): ?>
    <div class="clearfix">
        <div class="header-right">
            <?= $headerHtml ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Заголовок -->
    <div class="title-block">
        <div class="doc-title">Доклад о результатах</div>
        <div class="doc-subtitle">проведения плановой проверки</div>
        <div class="ins-name"><?= htmlspecialchars($insName) ?></div>
    </div>

    <!-- Приветствие -->
    <div class="greeting">Уважаемый министр!</div>

    <!-- Вводный абзац -->
    <?php if (strlen($introHtml) > 0): ?>
    <div class="intro"><?= $introHtml ?></div>
    <?php endif; ?>

    <?php if (strlen($actSentDate) > 0): ?>
        <div class="intro">
            Акт проверки направлен руководителю объекта контроля <?= htmlspecialchars($actSentDate) ?> посредством МСЭД.
        </div>
    <?php endif; ?>

    <!-- Результаты проверки -->
    <?php if (count($violations) > 0): ?>
        <h2 class="section-title">По результатам проверки установлено следующее.</h2>
        <?php foreach ($violations as $i => $v): ?>
            <div class="violation-item">
                <?= ($i + 1) . '. ' . htmlspecialchars($v['text'] ?? '') ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="intro">
            По результатам проверки нарушений не выявлено.
        </div>
    <?php endif; ?>

    <!-- Возражения ОК -->
    <?php if ($inclObj && !empty($objections['text'])): ?>
        <h2 class="section-title">Возражения объекта контроля</h2>
        <div class="objections-block">
            Возражения поступили <?= htmlspecialchars($objections['date'] ?? '') ?>:<br><br>
            <?= nl2br(htmlspecialchars($objections['text'])) ?>
        </div>
    <?php endif; ?>

    <!-- Предложения -->
    <?php if (count($proposals) > 0): ?>
        <div class="proposals-title">Предложения по результатам проверки</div>
        <?php foreach ($proposals as $i => $proposal): ?>
            <div class="proposal-item"><?= ($i + 1) . '. ' . htmlspecialchars($proposal) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Подпись -->
    <table class="sig-table">
        <tr>
            <td style="width:55%">
                <?= htmlspecialchars($authorPos) ?>
            </td>
            <td style="width:20%;text-align:center">
                _______________
            </td>
            <td style="width:25%;text-align:right">
                <?= htmlspecialchars($authorFio) ?>
            </td>
        </tr>
    </table>

    </body>
    </html>
<?php
$html = ob_get_clean();

// ── DomPDF ──────────────────────────────────────────────────
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('chroot', $_SERVER['DOCUMENT_ROOT']);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Если открывается в iframe — inline, иначе attachment
$inline = intval($_POST['params']['inline'] ?? $_GET['inline'] ?? 1);
$dompdf->stream(
    'doklad_' . $insNameShort . '_' . $repDate . '.pdf',
    ['Attachment' => $inline ? 0 : 1]
);