<?php
/**
 * modules/documents/ajaxHandlers/create_report.php
 *
 * Создаёт запись доклада министру (documentacial=8) в cam_agreement,
 * формирует agreementlist с согласовантами + подписью министра.
 */

use Core\Db;
use Core\Auth;
use Core\Registry;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db   = new Db();
$auth = new Auth();
$reg  = new Registry();

$result     = false;
$resultText = '';
$reportId   = 0;

if (!$auth->checkAjax()) {
    echo json_encode(['result' => false, 'resultText' => 'Ошибка авторизации.']);
    die();
}

$actId          = intval($_POST['params']['act_id']           ?? 0);
$docNumber      = trim($_POST['params']['doc_number']         ?? '');
$docDate        = trim($_POST['params']['doc_date']           ?? date('Y-m-d'));
$actSentDate    = trim($_POST['params']['act_sent_date']      ?? '');
$introText      = trim($_POST['params']['intro_text']         ?? '');

// Получаем предложения как массив и фильтруем пустые
$proposals      = array_values(array_filter(
    array_map('trim', (array)($_POST['params']['proposals'] ?? [])),
    function($val) { return strlen($val) > 0; }
));

// violation_ids — индексы отмеченных чекбоксов, violation_texts — тексты нарушений
$checkedIds      = array_map('intval', (array)($_POST['params']['violation_ids']    ?? []));
$violationTexts  = array_map('trim',   (array)($_POST['params']['violation_texts']  ?? []));
// Оставляем только отмеченные нарушения
$violations      = array_values(array_filter($violationTexts, function($text, $idx) use ($checkedIds) {
    return in_array($idx, $checkedIds) && strlen($text) > 0;
}, ARRAY_FILTER_USE_BOTH));
$inclObj         = intval($_POST['params']['include_objections'] ?? 0);

// agreementlist от компонента agreement_list
$rawAgreementList = (array)($_POST['agreementlist'] ?? []);
$clearAgreement = array_filter($rawAgreementList, function($s) { return strlen(trim($s)) > 0; });
$agreementListParsed = $reg->fixJsonArray(array_values($clearAgreement));

if ($actId === 0) {
    echo json_encode(['result' => false, 'resultText' => 'Не указан акт.']);
    die();
}

// Проверяем акт
$act = $db->selectOne('agreement', ' WHERE id = ? AND documentacial = 2 AND status = 1', [$actId]);
if (!$act) {
    echo json_encode(['result' => false, 'resultText' => 'Подписанный акт не найден.']);
    die();
}

// Проверяем — нет ли уже доклада
$existReport = $db->selectOne('agreement', ' WHERE documentacial = 8 AND source_id = ?', [$actId]);
if ($existReport) {
    echo json_encode(['result' => false, 'resultText' => 'Доклад по этому акту уже создан.', 'report_id' => intval($existReport->id)]);
    die();
}

// ── Формируем agreementlist ───────────────────────────────────
// ВАЖНО: Министр НЕ добавляется при создании!
// Министр будет добавлен АВТОМАТИЧЕСКИ после полного согласования
// (см. updateAgreement.php, documentacial=8, finalStatus=1)
$agreementList = $agreementListParsed;

// ── Формируем name доклада ────────────────────────────────────
$reportName = 'Доклад о результатах проверки «' . ($act->name ?? '') . '»';

// ── Сохраняем доклад в cam_agreement ─────────────────────────
$reportData = [
    'created_at'    => date('Y-m-d H:i:s'),
    'author'        => intval($_SESSION['user_id']),
    'active'        => 1,
    'name'          => $reportName,
    'documentacial' => 8,
    'doc_number'    => $docNumber,
    'status'        => 0,
    'source_id'     => $actId,   // ссылка на акт
    'source_table'  => 'agreement',
    'docdate'       => $docDate,
    'ins_id'        => intval($act->ins_id ?? $act->source_id ?? 0),
    'plan_id'       => intval($act->plan_id ?? 0),
    'agreementlist' => json_encode($agreementList, JSON_UNESCAPED_UNICODE),
    // Дополнительные поля доклада (используются шаблоном PDF)
    'brief'         => $introText,
    'body'          => json_encode([
        'act_sent_date'     => $actSentDate,
        'proposals'         => $proposals,
        'violations'        => $violations,
        'include_objections'=> $inclObj,
    ], JSON_UNESCAPED_UNICODE),
];

$reportId = $db->insert('agreement', $reportData);

if ($reportId > 0) {
    // Помечаем акт как «доклад создан»
    $db->update('agreement', $actId, ['report_id' => $reportId]);

    $result     = true;
    $resultText = 'Доклад успешно создан. Откройте лист согласования.';
} else {
    $resultText = 'Ошибка создания доклада.';
}

echo json_encode([
    'result'     => $result,
    'resultText' => $resultText,
    'report_id'  => $reportId,
]);