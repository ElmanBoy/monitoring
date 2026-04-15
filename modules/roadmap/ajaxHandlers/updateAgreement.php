<?php

use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db     = new Db();
$docId  = intval($_POST['docId']);

// ============================================================
// Проверка IDOR: документ должен существовать и пользователь
// должен быть его автором или участником списка согласования.
// ============================================================
$_agrDoc = $db->selectOne('agreement', ' WHERE id = ?', [$docId]);
if (!$_agrDoc) {
    echo json_encode(['result' => false, 'resultText' => 'Документ не найден.', 'errorFields' => []]);
    exit;
}
$_currentUserId    = intval($_SESSION['user_id'] ?? 0);
$_agrListRaw       = json_decode($_agrDoc->agreementlist ?? '[]', true) ?: [];
$_userInAgreement  = false;
foreach ($_agrListRaw as $_section) {
    if (!is_array($_section)) continue;
    foreach ($_section as $_item) {
        if (isset($_item['id']) && intval($_item['id']) === $_currentUserId) {
            $_userInAgreement = true;
            break 2;
        }
    }
}
if (intval($_agrDoc->author) !== $_currentUserId && !$_userInAgreement) {
    echo json_encode(['result' => false, 'resultText' => 'Нет доступа к документу.', 'errorFields' => []]);
    exit;
}
unset($_agrDoc, $_agrListRaw, $_userInAgreement, $_section, $_item);

// ============================================================
// Проверяем наличие и корректность переданного agreementList.
// Пустой или отсутствующий массив затрёт существующие данные согласования.
// ============================================================
if (empty($_POST['agreementList']) || !is_array($_POST['agreementList'])) {
    echo json_encode([
        'result'      => false,
        'resultText'  => 'Ошибка: данные согласования не переданы.',
        'errorFields' => [],
    ]);
    exit;
}

// ============================================================
// Нормализация agreementList — аналог функций из documents/updateAgreement.php.
// Без нормализации клиент мог бы прислать _is_redirector_repeat-запись
// с ненулевым result и тем самым подменить статус согласования без подписи.
// ============================================================

/**
 * Рекурсивно нормализует одну запись согласующего:
 * - _is_redirector_repeat приводится к bool
 * - result="" / "null" / null → null
 * - result.id приводится к int
 * - Для _is_redirector_repeat-записей result принудительно сбрасывается в null,
 *   чтобы сервер не доверял клиентскому значению
 */
function normalizeSection($item): array
{
    if (!is_array($item)) return $item;
    foreach ($item as $k => &$v) {
        if ($k === '_is_redirector_repeat') {
            $v = ($v === true || $v === 'true' || $v === 1 || $v === '1');
            continue;
        }
        if ($k === 'result') {
            if ($v === '' || $v === 'null' || $v === null) {
                $v = null;
            } elseif (is_array($v)) {
                $v['id'] = intval($v['id'] ?? 0);
            }
            continue;
        }
        if ($k === 'redirect' && is_array($v)) {
            $v = array_map('normalizeSection', $v);
            continue;
        }
        if (is_array($v)) {
            $v = normalizeSection($v);
        }
    }
    unset($v); // Разрываем ссылку на последний элемент после foreach по ссылке
    // Сервер не доверяет клиенту: сбрасываем result у повторных записей перенаправившего
    if (!empty($item['_is_redirector_repeat']) && isset($item['result']) && $item['result'] !== null) {
        $item['result'] = null;
    }
    return $item;
}

/**
 * Исправляет возможную двойную JSON-сериализацию элементов и нормализует каждую секцию.
 */
function fixAgreementList($agreementlist): array
{
    $result = [];
    foreach ($agreementlist as $item) {
        if (is_string($item)) {
            $decoded = json_decode($item, true);
            $result[] = (json_last_error() === JSON_ERROR_NONE) ? normalizeSection($decoded) : $item;
        } else {
            $result[] = normalizeSection($item);
        }
    }
    return $result;
}

$_POST['agreementList'] = fixAgreementList($_POST['agreementList']);

$signs      = $db->select('signs', " WHERE table_name = 'agreement' AND doc_id = ?", [$docId]);
$user_signs = [];
if (count($signs) > 0) {
    foreach ($signs as $s) {
        $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
    }
}

$updateArr = [
    'created_at'    => date('Y-m-d H:i:s'),
    'author'        => $_SESSION['user_id'],
    'agreementlist' => json_encode($_POST['agreementList'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
];
$result  = $db->update('agreement', $docId, $updateArr);
$message = $result['result'] ? 'Изменения в документе сохранены.' : '<strong>Ошибка:</strong>&nbsp;' . $result['resultText'];

$check         = $db->selectOne('agreement', ' WHERE id = ?', [$docId]);
$agreementList = json_decode($check->agreementlist, true) ?: [];
$results       = 0;
$signers       = 0;

// Примечание по безопасности (Bug A):
// В модуле documents/ajaxHandlers/updateAgreement.php есть функция applySignsToAgreementList,
// которая копирует result из cam_signs в записи agreementList. Там существует уязвимость:
// при наличии нескольких вхождений одного сотрудника подпись может скопироваться в неправильную запись.
// В roadmap этой функции НЕТ — agreementList сохраняется напрямую из $_POST без модификации,
// поэтому данная уязвимость здесь отсутствует.
//
// Однако в roadmap присутствует другой баг: в подсчёте $signers/$results не учитываются
// _is_redirector_repeat-записи и записи с redirect[]. Исправлено ниже.

for ($i = 0; $i < count($agreementList); $i++) {
    $itemArr = $agreementList[$i];
    if (is_string($itemArr)) $itemArr = json_decode($itemArr, true);

    // Считаем только «реальных» участников:
    // - пропускаем записи без поля id (метаданные секции — индекс 0)
    // - пропускаем _is_redirector_repeat-записи (они ждут возврата из redirect-цепочки,
    //   но в roadmap нет механизма их заполнения — не учитываем в знаменателе)
    // - пропускаем записи с redirect[] — они перенаправили ответственность дальше и
    //   в $results также не учитываются (условие ниже аналогично)
    for ($l = 1; $l < count($itemArr); $l++) {
        if (!isset($itemArr[$l]['id'])) continue;
        if (!empty($itemArr[$l]['_is_redirector_repeat'])) continue;
        if (isset($itemArr[$l]['redirect']) && is_array($itemArr[$l]['redirect'])) continue;
        $signers++;
    }

    for ($l = 1; $l < count($itemArr); $l++) {
        // Записи с redirect[] — участник перенаправил, сам не голосует
        if (isset($itemArr[$l]['redirect']) && is_array($itemArr[$l]['redirect'])) continue;
        // _is_redirector_repeat — повторная запись перенаправившего: в roadmap не заполняется
        if (!empty($itemArr[$l]['_is_redirector_repeat'])) continue;

        $rid = intval($itemArr[$l]['result']['id'] ?? 0);
        if (in_array($rid, [1, 2, 3])) {
            if (in_array($rid, [1, 2])) {
                if (isset($user_signs[$itemArr[$l]['id']][$i])
                    && in_array($user_signs[$itemArr[$l]['id']][$i]['type'], [1, 2])) {
                    $results++;
                }
            } else {
                $results++;
            }
        }
    }
}

if ($results == $signers && $signers > 0) {
    $message .= '<br>Документ согласован.';
    $db->update('agreement', $docId, ['status' => 1]);
} else {
    $db->update('agreement', $docId, ['status' => 0]);
}

echo json_encode([
    'result'      => $result['result'],
    'resultText'  => $message . '<script>el_app.reloadMainContent();</script>',
    'errorFields' => [],
]);