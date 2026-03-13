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
use Dompdf\Dompdf;
use Dompdf\Options;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db   = new Db();
$auth = new Auth();
$date = new Date();

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
$insName = $ins->name ?? '';
$insNameShort = $ins->name_short ?? $insName;

// Данные из поля body (сохранены при создании)
$body = json_decode($rep->body ?? '{}', true) ?: [];
$actSentDate    = $body['act_sent_date']     ?? ($act->doc_number ?? '');
$proposalsText  = $body['proposals_text']    ?? '';
$violationIds   = $body['violation_ids']     ?? [];
$inclObj        = intval($body['include_objections'] ?? 0);

// Нарушения
$violations = [];
if ($insId > 0 && $planId > 0) {
    $plan = $db->selectOne('checksplans', ' WHERE id = ?', [$planId]);
    if ($plan && strlen($plan->uid ?? '') > 0) {
        $staffRows = $db->select('checkstaff', ' WHERE check_uid = ? AND institution = ?',
            [$plan->uid, $insId]);
        if (count($staffRows) > 0) {
            $taskIds = array_map(function($r){ return intval($r->id); }, $staffRows);
            $allViolations = $db->db::getAll(
                'SELECT * FROM ' . TBL_PREFIX . 'checksviolations WHERE tasks IN (' .
                implode(',', $taskIds) . ') ORDER BY id'
            );
            foreach ($allViolations as $v) {
                // Если выбраны конкретные нарушения — фильтруем
                if (count($violationIds) === 0 || in_array(intval($v['id']), $violationIds)) {
                    $violations[] = $v;
                }
            }
        }
    }
}

// Возражения ОК
$objections = json_decode($act->objections ?? '{}', true) ?: [];

// Дата доклада
$repDate = strlen($rep->docdate ?? '') > 0
    ? $date->dateToString($rep->docdate)
    : date('d.m.Y');

// Подписант (начальник управления) — автор доклада
$repAuthor = $db->selectOne('users', ' WHERE id = ?', [intval($rep->author ?? 0)]);
$authorFio  = trim(($repAuthor->surname ?? '') . ' ' . mb_substr(trim($repAuthor->name ?? ''), 0, 1) . '. ' .
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
if ($planId > 0) {
    $plan = $plan ?? $db->selectOne('checksplans', ' WHERE id = ?', [$planId]);
    if ($plan) {
        $addIns = json_decode($plan->addinstitution ?? '[]', true);
        foreach ((array)$addIns as $ads) {
            if (intval($ads['institutions'] ?? 0) === $insId && isset($ads['check_periods'])) {
                $pArr = explode(' - ', $ads['check_periods']);
                $checkPeriodStart = $date->correctDateFormatFromMysql($pArr[0] ?? '');
                $checkPeriodEnd   = $date->correctDateFormatFromMysql($pArr[1] ?? '');
                break;
            }
        }
    }
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
    <div class="clearfix">
        <div class="header-right">
            Министру социального развития<br>
            Московской области<br>
            <br>
            <strong>_______________</strong>
        </div>
    </div>

    <!-- Заголовок -->
    <div class="title-block">
        <div class="doc-title">Доклад о результатах</div>
        <div class="doc-subtitle">проведения плановой проверки</div>
        <div class="ins-name"><?= htmlspecialchars($insName) ?></div>
    </div>

    <!-- Приветствие -->
    <div class="greeting">Уважаемый министр!</div>

    <!-- Вводный абзац -->
    <div class="intro">
        <?php
        $introDefault = 'Управлением финансового контроля и аудита' .
            (strlen($orderDate) > 0 ? ' на основании приказа от ' . $orderDate .
                (strlen($orderNumber) > 0 ? ' № ' . $orderNumber : '') : '') .
            ', в период с ' . $checkPeriodStart . ' по ' . $checkPeriodEnd .
            ' года, проведена плановая проверка' .
            (strlen($insName) > 0 ? ' ' . $insName : '') . '.';

        echo htmlspecialchars(strlen($rep->brief ?? '') > 0 ? $rep->brief : $introDefault);
        ?>
    </div>

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
                <?= ($i + 1) . '. ' . htmlspecialchars($v['name'] ?? '') ?>
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
    <?php if (strlen($proposalsText) > 0): ?>
        <div class="proposals-title">Предложения по результатам проверки</div>
        <?php
        $lines = array_filter(array_map('trim', explode("\n", $proposalsText)));
        foreach ($lines as $i => $line):
            ?>
            <div class="proposal-item"><?= ($i + 1) . '. ' . htmlspecialchars($line) ?></div>
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