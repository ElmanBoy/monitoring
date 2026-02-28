<?php

use Core\Registry;
use Core\Db;
use Core\Notifications;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db        = new Db();
$reg       = new Registry();
$user_signs = [];

$docId = intval($_POST['docId']);

if (!is_array($_POST['agreementList'])) {
    echo json_encode(['result' => false, 'html' => '']);
    exit;
}

// ============ НОРМАЛИЗУЕМ ВХОДЯЩИЙ agreementList ============
// БАГ #9 ИСПРАВЛЕН: убрана хрупкая эвристика convertArrayToObject.
// Структура данных теперь единообразна — всегда ассоциативные массивы.
// Если элемент пришёл как строка JSON — декодируем его.
function normalizeAgreementList(array $raw): array
{
    $result = [];
    foreach ($raw as $item) {
        if (is_string($item)) {
            $decoded = json_decode($item, true);
            $result[] = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $item;
        } else {
            $result[] = $item;
        }
    }
    return $result;
}

$agreementList = normalizeAgreementList($_POST['agreementList']);

// ============ ЗАГРУЖАЕМ СПРАВОЧНИКИ ============
$users = $db->getRegistry('users', '', [],
    ['surname', 'name', 'middle_name', 'institution', 'ministries', 'division', 'position']
);

$urgent_types = [
    1 => 'Обычный',
    2 => '<span style="color: #d8720b">Срочный</span>',
    3 => '<span style="color: #d8110b">Незамедлительно</span>'
];

// ============ ЗАГРУЖАЕМ ПОДПИСИ ЭЦП ============
$signs = $db->select('signs', " where table_name = 'agreement' AND doc_id = ?", [$docId]);
if (count($signs) > 0) {
    foreach ($signs as $s) {
        $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
    }
}

// ============ ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ============

function getApproverStatus(array $approver): array
{
    $result = $approver['result'] ?? null;
    if (!$result || !is_array($result)) {
        return ['status' => 'pending', 'result_id' => 0];
    }
    switch (intval($result['id'] ?? 0)) {
        case 1: case 2: case 3:
        return ['status' => 'approved',   'result_id' => intval($result['id'])];
        case 4:
            return ['status' => 'redirected', 'result_id' => 4];
        case 5:
            return ['status' => 'rejected',   'result_id' => 5];
        default:
            return ['status' => 'pending',    'result_id' => 0];
    }
}

function isRedirectChainComplete(array $redirects): bool
{
    foreach ($redirects as $r) {
        if (!isset($r['id'])) continue;
        $st = getApproverStatus($r);
        if ($st['status'] === 'pending' || $st['status'] === 'rejected') return false;
        if ($st['status'] === 'redirected') {
            if (!isset($r['redirect']) || !isRedirectChainComplete($r['redirect'])) return false;
        }
    }
    return true;
}

/**
 * БАГ #5 ИСПРАВЛЕН: этап завершён, если каждый участник approved
 * либо redirected с полностью закрытой цепочкой.
 */
function checkStageComplete(array $itemArr): bool
{
    $startIndex = isset($itemArr[0]['stage']) ? 1 : 0;
    $approvers  = array_slice($itemArr, $startIndex);

    if (empty($approvers)) return false;

    foreach ($approvers as $approver) {
        if (!isset($approver['id'])) continue;
        $st = getApproverStatus($approver);

        if ($st['status'] === 'pending' || $st['status'] === 'rejected') return false;

        if ($st['status'] === 'redirected') {
            if (!isset($approver['redirect']) || !isRedirectChainComplete($approver['redirect'])) {
                return false;
            }
        }
    }
    return true;
}

// ============ ОПРЕДЕЛЯЕМ ТИП СОГЛАСОВАНИЯ ДЛЯ ШАПКИ ============
$list_types = [];
foreach ($agreementList as $itemArr) {
    if (is_array($itemArr) && isset($itemArr[0]['list_type'])) {
        $lt = (string)$itemArr[0]['list_type'];
        if (!in_array($lt, $list_types)) {
            $list_types[] = $lt;
        }
    }
}

if (count($list_types) === 0) {
    $listTypeText = 'параллельное';
} elseif (count($list_types) > 1) {
    $listTypeText = 'смешанное';
} else {
    $listTypeText = ($list_types[0] === '1') ? 'последовательное' : 'параллельное';
}

// ============ ГЕНЕРИРУЕМ HTML ТАБЛИЦЫ ============
$html = '<div class="agreement_list">' .
    '<h4>ЛИСТ СОГЛАСОВАНИЯ</h4>' .
    '<div class="list_type">Тип согласования: <strong>' . $listTypeText . '</strong></div>' .
    '<table class="agreement-table">' .
    '<thead><tr>' .
    '<th>№</th>' .
    '<th style="width: 30%;">ФИО</th>' .
    '<th>Срок согласования</th>' .
    '<th style="width:30%">Результат согласования</th>' .
    '<th>Комментарии</th>' .
    '</tr></thead>';

$jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

for ($i = 0; $i < count($agreementList); $i++) {
    $itemArr = $agreementList[$i];

    if (!is_array($itemArr) || count($itemArr) === 0) continue;

    $stageComplete = checkStageComplete($itemArr);
    $listType      = isset($itemArr[0]['list_type']) ? (string)$itemArr[0]['list_type'] : '2';
    $stageNum      = isset($itemArr[0]['stage']) ? intval($itemArr[0]['stage']) : 0;

    $html .= '<tbody' . ($stageComplete ? '' : ' class="notComplete"') . '>';

    // Заголовок секции
    // БАГ #4 ИСПРАВЛЕН: htmlspecialchars + ENT_QUOTES для value атрибута
    $jsonString = htmlspecialchars(json_encode($itemArr, $jsonOptions), ENT_QUOTES, 'UTF-8');

    $html .= '<tr><td class="divider" colspan="5">';
    if ($stageNum > 0) {
        $html .= '<strong>Этап ' . $stageNum . '</strong><br>';
    } else {
        $html .= '<strong>Подписанты</strong><br>';
    }
    $html .= 'Тип согласования: <strong>' .
        ($listType === '1' ? 'последовательное' : 'параллельное') .
        '</strong>';
    $html .= '<input type="hidden" name="addAgreement" id="ag' . $i . '" value="' . $jsonString . '">';
    $html .= '</td></tr>';

    // Строки участников
    $html .= $reg->buildAgreementList($itemArr, $i, $users, $urgent_types, $user_signs, $reg, 0, $agreementList);

    $html .= '</tbody>';
}

$html .= '</table></div>';

echo json_encode(['result' => true, 'html' => $html]);
exit;