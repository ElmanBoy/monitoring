<?php

use Core\Registry;
use Core\Db;
use Core\Notifications;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db    = new Db();
$reg   = new Registry();
$alert = new Notifications();
$user_signs = [];

$docId    = intval($_POST['docId']);
$currentUserId = intval($_SESSION['user_id'] ?? 0); // ID текущего пользователя для проверки уведомлений
$message  = '';
$updateData = [];
$options  = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

$agr = $db->selectOne('agreement', ' id = ?', [$docId]);

// ============================================================
// Проверка IDOR: документ должен существовать и пользователь
// должен быть его автором или участником списка согласования.
// Без этой проверки любой авторизованный пользователь мог бы
// изменить agreementlist чужого документа, передав чужой docId.
// ============================================================
if (!$agr) {
    echo json_encode(['result' => false, 'resultText' => 'Документ не найден.', 'errorFields' => []]);
    exit;
}
$_currentUserId = intval($_SESSION['user_id'] ?? 0);
$_agreementListRaw = json_decode($agr->agreementlist ?? '[]', true) ?: [];
$_userInAgreement = false;
foreach ($_agreementListRaw as $_section) {
    if (!is_array($_section)) continue;
    foreach ($_section as $_item) {
        if (isset($_item['id']) && intval($_item['id']) === $_currentUserId) {
            $_userInAgreement = true;
            break 2;
        }
    }
}
if (intval($agr->author) !== $_currentUserId && !$_userInAgreement) {
    echo json_encode(['result' => false, 'resultText' => 'Нет доступа к документу.', 'errorFields' => []]);
    exit;
}
unset($_agreementListRaw, $_userInAgreement, $_section, $_item);

// ============================================================
// Исправляем возможную двойную JSON-сериализацию элементов
// ============================================================
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

// Рекурсивно нормализует секцию agreementList:
// - "true"/"false" → булевы
// - result="" → null
// - result.id как int
// - У записей с _is_redirector_repeat: true принудительно сбрасываем result в null,
//   чтобы сервер не доверял клиенту и не принимал уже заполненный result для повторных записей
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
    // Сервер не доверяет клиенту: если запись является повторной записью перенаправившего,
    // сбрасываем result в null независимо от того, что прислал клиент.
    // Подпись для повторной записи должна проставляться только через applySignsToAgreementList.
    if (!empty($item['_is_redirector_repeat']) && isset($item['result']) && $item['result'] !== null) {
        $item['result'] = null;
    }
    return $item;
}

$_POST['agreementList'] = fixAgreementList($_POST['agreementList']);

// Получаем тип документа для валидации
$documentType = intval($agr->documentacial ?? 0);

// Инициализируем предупреждение о количестве участников (используется в конце файла в json_encode)
$quantityWarning = '';

// Валидация секции подписантов
foreach ($_POST['agreementList'] as $section) {
    if (!is_array($section) || !isset($section[0])) continue;
    // Секция подписантов — stage=""
    if (!array_key_exists('stage', $section[0]) || $section[0]['stage'] !== '') continue;

    // Считаем участников первого уровня
    $signerCount = 0;
    $approverCount = 0;
    $firstLevelCount = 0;
    $lastSignerIdx = -1;
    $firstApproverIdx = -1;

    for ($k = 1; $k < count($section); $k++) {
        if (!isset($section[$k]['id']) || !empty($section[$k]['_is_redirector_repeat'])) continue;

        $firstLevelCount++;
        $role = intval($section[$k]['role'] ?? 0);

        if ($role === 1) {
            $signerCount++;
            $lastSignerIdx = $k;
        }
        if ($role === 0) {
            $approverCount++;
            if ($firstApproverIdx === -1) $firstApproverIdx = $k;
        }
    }

    // Проверка порядка: role=1 должен быть перед role=0
    if ($lastSignerIdx > -1 && $firstApproverIdx > -1 && $lastSignerIdx > $firstApproverIdx) {
        echo json_encode([
            'result'      => false,
            'resultText'  => 'Ошибка: подписывающий сотрудник должен быть в списке раньше утверждающего.',
            'errorFields' => []
        ]);
        exit;
    }

    // Проверка количества участников в зависимости от типа документа
    // Показываем предупреждение, но не блокируем сохранение (пользователь может быть в процессе формирования списка)
    $quantityWarning = '';
    if ($documentType == 1) {
        // Приказ: только 1 подписант, без утверждающих
        if ($firstLevelCount != 1 || $signerCount != 1 || $approverCount > 0) {
            $quantityWarning = '⚠️ Внимание: Приказ должен иметь ровно 1 подписанта без утверждающих. ';
        }
    } else {
        // Остальные документы: 1 подписант + 1 утверждающий
        if ($firstLevelCount != 2 || $signerCount != 1 || $approverCount != 1) {
            $quantityWarning = '⚠️ Внимание: Документ должен иметь ровно 2 участника первого уровня - один подписывает, второй утверждает. ';
        }
    }
}

// Подписи ЭЦП из таблицы signs
$signs = $db->select('signs', " where table_name = 'agreement' AND doc_id = ?", [$docId]);
foreach ($signs as $s) {
    $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
}

if (!is_array($_POST['agreementList'])) {
    throw new Exception('Данные должны быть массивом');
}

// Сохраняем список перед обработкой
$updateArr = [
    'created_at'    => date('Y-m-d H:i:s'),
    'author'        => $_SESSION['user_id'],
    'agreementlist' => json_encode($_POST['agreementList'], $options)
];
$result = $db->update('agreement', $docId, $updateArr);
if ($result['result']) {
    $message = 'Изменения в документе сохранены.';
} else {
    $message = '<strong>Ошибка:</strong>&nbsp; ' . $result['resultText'];
}

$check         = $db->selectOne('agreement', ' WHERE id = ?', [$docId]);
$planId        = intval($check->source_id);
$docType       = intval($check->documentacial);
$agreementList = json_decode($check->agreementlist, true);
$newDocNumber  = $reg->getNewDocNumber($check->documentacial);

// ============================================================
// Определение статуса согласующего
// ============================================================
function getApproverStatus(array $approver): array
{
    $result = $approver['result'] ?? null;
    if (!$result || !is_array($result)) {
        return ['status' => 'pending', 'result_id' => 0];
    }
    $resultId = intval($result['id'] ?? 0);
    switch ($resultId) {
        case 1: case 2: case 3:
        return ['status' => 'approved',   'result_id' => $resultId];
        case 4:
            return ['status' => 'redirected', 'result_id' => 4];
        case 5:
            return ['status' => 'rejected',   'result_id' => 5];
        case 6:
            return ['status' => 'returned',   'result_id' => 6];
        default:
            return ['status' => 'pending',    'result_id' => 0];
    }
}

// ============================================================
// Завершена ли redirect-цепочка (рекурсивно)
// ============================================================
function isRedirectChainCompleted(array $redirectArr, int $depth = 0): bool
{
    // Защита от зацикливания при испорченных данных (циклические ссылки или слишком длинные цепочки)
    if ($depth > 20) return false;

    foreach ($redirectArr as $approver) {
        if (!isset($approver['id'])) continue;
        $status = getApproverStatus($approver);

        if ($status['status'] === 'pending') return false;
        if ($status['status'] === 'returned') return false; // вернули — цепочка не завершена

        if ($status['status'] === 'redirected') {
            // Проверяем, завершена ли под-цепочка
            $subDone = isset($approver['redirect']) && is_array($approver['redirect'])
                && isRedirectChainCompleted($approver['redirect'], $depth + 1);
            if (!$subDone) return false;

            // Под-цепочка завершена, но сам redirector ещё должен принять финальное решение.
            // Финальное решение фиксируется в _is_redirector_repeat-записи с непустым result.
            // Пока такой записи нет — считаем цепочку незавершённой.
            $redirectorId  = $approver['id'];
            $hasFinalized  = false;
            foreach ($redirectArr as $entry) {
                if (isset($entry['id']) && $entry['id'] == $redirectorId
                    && !empty($entry['_is_redirector_repeat'])
                    && !empty($entry['result'])) {
                    $hasFinalized = true;
                    break;
                }
            }
            if (!$hasFinalized) return false;
        }
        // approved / rejected — считаются завершёнными, ничего не делаем
    }
    return true;
}

// ============================================================
// ПРАВИЛО 4: Добавить повторную запись перенаправившего
// сразу после перенаправленного (если ещё не добавлена).
// Функция рекурсивна — обрабатывает вложенные redirect[] любой глубины.
// ============================================================

/**
 * Рекурсивно обходит $level и вставляет _is_redirector_repeat-записи
 * для каждого участника со статусом redirected на любой глубине вложенности.
 * Обработка идёт снизу вверх: сначала вложенные уровни, потом текущий —
 * это гарантирует, что isRedirectChainCompleted увидит уже корректную структуру.
 */
function _insertRepeatInLevel(array &$level, int $startIndex = 0, int $depth = 0): void
{
    // Защита от зацикливания при испорченных данных
    if ($depth > 20) return;

    for ($j = $startIndex; $j < count($level); $j++) {
        if (!isset($level[$j]['id'])) continue;

        // Сначала рекурсивно обрабатываем вложенный redirect[]
        if (!empty($level[$j]['redirect']) && is_array($level[$j]['redirect'])) {
            _insertRepeatInLevel($level[$j]['redirect'], 0, $depth + 1);
        }

        $status = getApproverStatus($level[$j]);
        if ($status['status'] !== 'redirected') continue;

        $userId = $level[$j]['id'];

        // Проверяем, нет ли уже повторной записи после текущей позиции
        $hasRepeat = false;
        for ($k = $j + 1; $k < count($level); $k++) {
            if (isset($level[$k]['id']) && $level[$k]['id'] == $userId
                && !empty($level[$k]['_is_redirector_repeat'])) {
                $hasRepeat = true;
                break;
            }
        }

        if (!$hasRepeat) {
            $approver    = $level[$j];
            $repeatEntry = [
                'id'                    => $userId,
                'type'                  => $approver['type']   ?? 1,
                'vrio'                  => $approver['vrio']   ?? '0',
                'urgent'                => $approver['urgent'] ?? '0',
                'role'                  => $approver['role']   ?? '0',
                'result'                => null,
                '_is_redirector_repeat' => true
            ];
            array_splice($level, $j + 1, 0, [$repeatEntry]);
            $j++; // пропускаем только что вставленную запись
        }
    }
}

function insertRedirectorRepeatEntry(array &$agreementList): void
{
    for ($i = 0; $i < count($agreementList); $i++) {
        $section    = &$agreementList[$i];
        $startIndex = isset($section[0]['stage']) ? 1 : 0;
        // Рекурсивно обходим секцию, начиная с первого участника
        _insertRepeatInLevel($section, $startIndex);
    }
}

// ============================================================
// Сбор глобальной статистики
// ============================================================

/**
 * Рекурсивно обходит redirect[] любой глубины и добавляет статусы в $stats.
 */
function _countRedirectStats(array $redirectArr, array &$stats, int $depth = 0): void
{
    // Защита от зацикливания при испорченных данных
    if ($depth > 20) return;

    foreach ($redirectArr as $rd) {
        if (!isset($rd['id'])) continue;
        $rst = getApproverStatus($rd);
        $stats['total']++;
        $stats[$rst['status']]++;
        // Рекурсия вглубь вложенных перенаправлений
        if (isset($rd['redirect']) && is_array($rd['redirect'])) {
            _countRedirectStats($rd['redirect'], $stats, $depth + 1);
        }
    }
}

function collectGlobalStats(array $agreementList): array
{
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'redirected' => 0, 'rejected' => 0, 'returned' => 0];

    foreach ($agreementList as $section) {
        $startIndex = isset($section[0]['stage']) ? 1 : 0;
        for ($i = $startIndex; $i < count($section); $i++) {
            if (!isset($section[$i]['id'])) continue;
            $st = getApproverStatus($section[$i]);
            $stats['total']++;
            $stats[$st['status']]++;
            // Рекурсивно считаем статусы всех вложенных redirect[]
            if (isset($section[$i]['redirect']) && is_array($section[$i]['redirect'])) {
                _countRedirectStats($section[$i]['redirect'], $stats);
            }
        }
    }
    return $stats;
}

// ============================================================
// ПРАВИЛА 1, 2, 3, 4: Отправка уведомлений следующим участникам
// ============================================================
function sendNotificationsToNextActors(
    Db            $db,
    Notifications $alert,
    array         $agreementList,
    int           $docId,
    string        $docName,
    int           $currentUserId,
    bool          $isNewDocument = false
): array {
    global $docType;
    $notified = [];

    // Есть ли хотя бы один pending
    $hasAnyPending = false;
    foreach ($agreementList as $section) {
        $si = isset($section[0]['stage']) ? 1 : 0;
        for ($i = $si; $i < count($section); $i++) {
            if (!isset($section[$i]['id'])) continue;
            if (getApproverStatus($section[$i])['status'] === 'pending') {
                $hasAnyPending = true;
                break 2;
            }
        }
    }
    if (!$hasAnyPending) return [];

    foreach ($agreementList as $sectionIndex => $section) {
        if (!is_array($section)) continue;

        $startIndex = isset($section[0]['stage']) ? 1 : 0;
        $listType   = intval($section[0]['list_type'] ?? 2); // 1=последовательный, 2=параллельный
        $stage      = $section[0]['stage'] ?? '';

        // ПРАВИЛО 3: Если в секции есть отклонение — пропускаем
        $hasRejection = false;
        for ($i = $startIndex; $i < count($section); $i++) {
            if (!isset($section[$i]['id'])) continue;
            if (getApproverStatus($section[$i])['status'] === 'rejected') {
                $hasRejection = true;
                break;
            }
        }
        if ($hasRejection) continue;

        // Предыдущие секции должны быть завершены
        $prevDone = true;
        for ($s = 0; $s < $sectionIndex; $s++) {
            if (!isset($agreementList[$s]) || !is_array($agreementList[$s])) continue;
            $pSect  = $agreementList[$s];
            $pStart = isset($pSect[0]['stage']) ? 1 : 0;
            for ($p = $pStart; $p < count($pSect); $p++) {
                if (!isset($pSect[$p]['id'])) continue;
                if (getApproverStatus($pSect[$p])['status'] === 'pending') {
                    $prevDone = false;
                    break 2;
                }
            }
        }
        if (!$prevDone) continue;

        // ПРАВИЛО 4: Уведомление перенаправленным и перенаправившим
        for ($i = $startIndex; $i < count($section); $i++) {
            if (!isset($section[$i]['id'])) continue;
            $st = getApproverStatus($section[$i]);

            if ($st['status'] === 'redirected' && isset($section[$i]['redirect'])) {
                // Рекурсивная функция для обработки вложенных redirect
                $processRedirects = function($redirectArr, $depth = 0) use (&$processRedirects, &$notified, $alert, $docId, $docName, $currentUserId, $sectionIndex, $i) {
                    foreach ($redirectArr as $rd) {
                        if (!isset($rd['id'])) continue;
                        $rdStatus = getApproverStatus($rd);

                        // Если pending - отправляем уведомление
                        if ($rdStatus['status'] === 'pending') {
                            $targetId = $rd['id'];
                            $key = $targetId . '_redirect_' . $sectionIndex . '_' . $i . '_d' . $depth;
                            if ($targetId != $currentUserId && !isset($notified[$key])) {
                                try {
                                    // ВАЖНО: Определяем тип уведомления по role перенаправленного
                                    // Если role=1 - это подписание, иначе - согласование
                                    $notifType = (isset($rd['role']) && intval($rd['role']) === 1) ? 1 : 4;
                                    $alert->notificationSigner($targetId, $notifType, $docId, $docName);
                                    $notified[$key] = true;
                                    $logMsg = "[" . date('Y-m-d H:i:s') . "] ПРАВИЛО 4 (redirect chain): Отправлено уведомление user=$targetId, doc=$docId, type=$notifType, depth=$depth, currentUser=$currentUserId\n";
                                    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/PHP_errors.log', $logMsg, FILE_APPEND);
                                } catch (\Exception $e) {
                                    $logMsg = "[" . date('Y-m-d H:i:s') . "] Уведомление (перенаправление): " . $e->getMessage() . "\n";
                                    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/PHP_errors.log', $logMsg, FILE_APPEND);
                                }
                            } else {
                                $logMsg = "[" . date('Y-m-d H:i:s') . "] ПРАВИЛО 4 (redirect chain): Уведомление НЕ отправлено user=$targetId (current=$currentUserId, alreadyNotified=" . (isset($notified[$key]) ? 'YES' : 'NO') . ")\n";
                                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/PHP_errors.log', $logMsg, FILE_APPEND);
                            }
                        }

                        // Если redirected - рекурсивно обрабатываем вложенный redirect
                        if ($rdStatus['status'] === 'redirected' && isset($rd['redirect']) && is_array($rd['redirect'])) {
                            $processRedirects($rd['redirect'], $depth + 1);
                        }
                    }
                };

                $processRedirects($section[$i]['redirect']);
            }

            // Повторная запись перенаправившего — уведомляем, если redirect завершён
            if (isset($section[$i]['_is_redirector_repeat']) && $section[$i]['_is_redirector_repeat']) {
                $redirectorId = $section[$i]['id'];
                for ($prev = $startIndex; $prev < $i; $prev++) {
                    if (isset($section[$prev]['id']) && $section[$prev]['id'] == $redirectorId
                        && isset($section[$prev]['redirect'])) {
                        if (isRedirectChainCompleted($section[$prev]['redirect'])) {
                            $key = $redirectorId . '_redirector_back_' . $sectionIndex . '_' . $i;
                            if ($redirectorId != $currentUserId && !isset($notified[$key])) {
                                try {
                                    $alert->notificationSigner($redirectorId, 4, $docId, $docName);
                                    $notified[$key] = true;
                                    $logMsg = "[" . date('Y-m-d H:i:s') . "] ПРАВИЛО 4 (возврат перенаправившему): Отправлено уведомление user=$redirectorId, doc=$docId, currentUser=$currentUserId\n";
                                    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/PHP_errors.log', $logMsg, FILE_APPEND);
                                } catch (\Exception $e) {
                                    $logMsg = "[" . date('Y-m-d H:i:s') . "] Уведомление (возврат перенаправившему): " . $e->getMessage() . "\n";
                                    file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/PHP_errors.log', $logMsg, FILE_APPEND);
                                }
                            } else {
                                $logMsg = "[" . date('Y-m-d H:i:s') . "] ПРАВИЛО 4 (возврат перенаправившему): Уведомление НЕ отправлено user=$redirectorId (current=$currentUserId, alreadyNotified=" . (isset($notified[$key]) ? 'YES' : 'NO') . ")\n";
                                file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/PHP_errors.log', $logMsg, FILE_APPEND);
                            }
                        }
                        break;
                    }
                }
                continue;
            }
        }

        $isSigners = ($stage === '');
        $notifType = $isSigners ? 1 : 4; // 1=подписание, 4=согласование

        if ($listType == 2) {
            // ПРАВИЛО 1: Параллельное — уведомляем ВСЕХ pending
            for ($i = $startIndex; $i < count($section); $i++) {
                if (!isset($section[$i]['id'])) continue;
                if (getApproverStatus($section[$i])['status'] !== 'pending') continue;
                if (isset($section[$i]['_is_redirector_repeat'])) continue;
                $userId = $section[$i]['id'];
                $key    = $userId . '_' . $sectionIndex;
                if ($userId != $currentUserId && !isset($notified[$key])) {
                    try {
                        $alert->notificationSigner($userId, $notifType, $docId, $docName);
                        $notified[$key] = true;
                    } catch (\Exception $e) {
                        $logMsg = "[" . date('Y-m-d H:i:s') . "] Уведомление (параллельное): " . $e->getMessage() . "\n";
                        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/PHP_errors.log', $logMsg, FILE_APPEND);
                    }
                }
            }
        } else {
            // ПРАВИЛО 2: Последовательное — уведомляем только ПЕРВОГО pending
            for ($i = $startIndex; $i < count($section); $i++) {
                if (!isset($section[$i]['id'])) continue;
                if (isset($section[$i]['_is_redirector_repeat'])) continue;
                if (getApproverStatus($section[$i])['status'] !== 'pending') continue;
                $userId = $section[$i]['id'];
                $key    = $userId . '_' . $sectionIndex;
                if ($userId != $currentUserId && !isset($notified[$key])) {
                    if ($isNewDocument && $sectionIndex === 0 && $i === $startIndex) {
                        // первый участник при создании документа — не уведомляем
                    } else {
                        try {
                            $alert->notificationSigner($userId, $notifType, $docId, $docName);
                            $notified[$key] = true;
                        } catch (\Exception $e) {
                            $logMsg = "[" . date('Y-m-d H:i:s') . "] Уведомление (последовательное): " . $e->getMessage() . "\n";
                            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/logs/PHP_errors.log', $logMsg, FILE_APPEND);
                        }
                    }
                }
                break;
            }
        }
    }

    return array_keys($notified);
}

// ============================================================
// Подтягиваем ЭЦП из таблицы signs в agreementList
// Только для строк у которых result ещё не установлен (null/пустой)
// и которые НЕ являются _is_redirector_repeat
// ============================================================

/**
 * Рекурсивно применяет подписи из $user_signs к записям redirect[] любой глубины.
 * $sectionIndex используется как ключ в $user_signs (индекс секции).
 */
function _applySignsToRedirectLevel(array &$redirectArr, array $user_signs, int $sectionIndex, int $depth = 0): void
{
    // Защита от зацикливания при испорченных данных
    if ($depth > 20) return;

    for ($r = 0; $r < count($redirectArr); $r++) {
        $rd = &$redirectArr[$r];
        if (!isset($rd['id'])) continue;
        // Не перезаписываем уже имеющийся result
        if (!empty($rd['result'])) {
            // Но рекурсия вглубь всё равно нужна
            if (!empty($rd['redirect']) && is_array($rd['redirect'])) {
                _applySignsToRedirectLevel($rd['redirect'], $user_signs, $sectionIndex, $depth + 1);
            }
            continue;
        }
        $rdUserId = intval($rd['id']);
        if (isset($user_signs[$rdUserId][$sectionIndex])) {
            $signType = intval($user_signs[$rdUserId][$sectionIndex]['type']);
            if (in_array($signType, [1, 2])) {
                $rd['result'] = [
                    'id'   => $signType,
                    'date' => $user_signs[$rdUserId][$sectionIndex]['date']
                ];
            }
        }
        // Рекурсия вглубь вложенных redirect[]
        if (!empty($rd['redirect']) && is_array($rd['redirect'])) {
            _applySignsToRedirectLevel($rd['redirect'], $user_signs, $sectionIndex, $depth + 1);
        }
    }
}

/**
 * Рекурсивно собирает userId тех участников, кто уже подписал через redirect[].
 * Нужно для определения, что основная запись участника тоже должна получить result.
 */
function _collectSignedViaRedirect(array $redirectArr, array &$signedViaRedirect, int $depth = 0): void
{
    // Защита от зацикливания при испорченных данных
    if ($depth > 20) return;

    foreach ($redirectArr as $rd) {
        if (!isset($rd['id'])) continue;
        $rdStatus = intval($rd['result']['id'] ?? 0);
        if (in_array($rdStatus, [1, 2, 3])) {
            $signedViaRedirect[intval($rd['id'])] = true;
        }
        // Рекурсия вглубь вложенных redirect[]
        if (!empty($rd['redirect']) && is_array($rd['redirect'])) {
            _collectSignedViaRedirect($rd['redirect'], $signedViaRedirect, $depth + 1);
        }
    }
}

function applySignsToAgreementList(array &$agreementList, array $user_signs): void
{
    for ($i = 0; $i < count($agreementList); $i++) {
        $startIndex = isset($agreementList[$i][0]['stage']) ? 1 : 0;

        // Собираем userId которые уже подписали через redirect[] в этой секции (любая глубина)
        $signedViaRedirect = [];
        for ($j = $startIndex; $j < count($agreementList[$i]); $j++) {
            if (!isset($agreementList[$i][$j]['redirect'])) continue;
            _collectSignedViaRedirect($agreementList[$i][$j]['redirect'], $signedViaRedirect);
        }

        // Словарь: userId => true, если подпись для этого участника уже применена в секции.
        // Предотвращает копирование одной и той же подписи на второе вхождение участника
        // (например, если участник перенаправил и получил повторную запись без _is_redirector_repeat).
        $signsApplied = [];

        for ($j = $startIndex; $j < count($agreementList[$i]); $j++) {
            if (!isset($agreementList[$i][$j]['id'])) continue;
            // Не трогаем повторные записи перенаправившего
            if (!empty($agreementList[$i][$j]['_is_redirector_repeat'])) continue;
            $userId = intval($agreementList[$i][$j]['id']);
            // Если у этой записи уже есть result — помечаем userId как обработанный и пропускаем.
            // Это гарантирует, что следующее вхождение того же участника (без _is_redirector_repeat)
            // не получит подпись повторно.
            if (!empty($agreementList[$i][$j]['result'])) {
                $signsApplied[$userId] = true;
                continue;
            }
            // Если подпись для этого userId в секции уже была применена — пропускаем.
            // Защищает от дублирования при наличии нескольких вхождений одного участника.
            if (isset($signsApplied[$userId])) continue;
            // Применяем подпись из cam_signs если она есть
            if (isset($user_signs[$userId][$i])) {
                $signType = intval($user_signs[$userId][$i]['type']);
                if (in_array($signType, [1, 2])) {
                    $agreementList[$i][$j]['result'] = [
                        'id'   => $signType,
                        'date' => $user_signs[$userId][$i]['date']
                    ];
                    $signsApplied[$userId] = true;
                }
            } elseif (isset($signedViaRedirect[$userId])) {
                // Пользователь подписал через redirect — копируем результат из redirect
                foreach ($agreementList[$i] as $item) {
                    if (!isset($item['redirect'])) continue;
                    foreach ($item['redirect'] as $rd) {
                        if (intval($rd['id']) === $userId && !empty($rd['result'])) {
                            $rdStatus = intval($rd['result']['id'] ?? 0);
                            if (in_array($rdStatus, [1, 2, 3])) {
                                $agreementList[$i][$j]['result'] = $rd['result'];
                                $signsApplied[$userId] = true;
                            }
                            break 2;
                        }
                    }
                }
            }
            // Рекурсивно применяем подписи ко всем уровням redirect[]
            if (!empty($agreementList[$i][$j]['redirect']) && is_array($agreementList[$i][$j]['redirect'])) {
                _applySignsToRedirectLevel($agreementList[$i][$j]['redirect'], $user_signs, $i);
            }
        }
    }
}

applySignsToAgreementList($agreementList, $user_signs);

// Вставляем повторные записи перенаправившего для всех уровней вложенности.
// Функция идемпотентна (проверяет $hasRepeat перед вставкой), поэтому
// дублирования с клиентским кодом не возникает.
insertRedirectorRepeatEntry($agreementList);

$globalStats = collectGlobalStats($agreementList);

// ============================================================
// Итоговый статус документа + отправка уведомлений
// ============================================================
$finalStatus  = 0;
$finalMessage = $message;

if ($globalStats['rejected'] > 0) {
    // Есть отклонение — документ отклонён
    $finalStatus   = 0;
    $finalMessage .= '<br>Документ отклонён.';

} elseif ($globalStats['pending'] > 0) {
    $finalStatus   = 0;
    $finalMessage .= '<br>Документ на согласовании.';

    try {
        $notifiedUsers = sendNotificationsToNextActors(
            $db, $alert, $agreementList, $docId, $agr->name, $_SESSION['user_id']
        );
        if (!empty($notifiedUsers)) {
            $gui = new \Core\Gui();
            $finalMessage .= '<br>Уведомления отправлены: ' . count($notifiedUsers) . ' сотрудник' .
                $gui->postfix($notifiedUsers, 'у', 'ам', 'ам') . '.';
        }
    } catch (\Exception $e) {
        error_log('Ошибка отправки уведомлений: ' . $e->getMessage());
    }

} elseif ($globalStats['approved'] > 0 && $globalStats['pending'] == 0 && $globalStats['rejected'] == 0) {
    // ──────────────────────────────────────────────────────────
    // СПЕЦИАЛЬНАЯ ЛОГИКА для ДОКЛАДА (documentacial=8):
    // Если все согласовали, но министра ещё нет — добавляем министра
    // ──────────────────────────────────────────────────────────
    if ($docType == 8) {
        // Проверяем, есть ли уже министр в agreementList
        $hasMinister = false;
        foreach ($agreementList as $section) {
            $startIdx = isset($section[0]['stage']) ? 1 : 0;
            for ($i = $startIdx; $i < count($section); $i++) {
                if (!isset($section[$i]['id'])) continue;
                $userId = intval($section[$i]['id']);
                // Проверяем, является ли этот пользователь министром
                $userRec = $db->selectOne('users', ' WHERE id = ?', [$userId]);
                if ($userRec && strpos($userRec->roles, '2') !== false &&
                    strpos($userRec->position, 'Министр социального развития') !== false) {
                    $hasMinister = true;
                    break 2;
                }
            }
        }

        // Если министра нет — добавляем и НЕ переводим в finalStatus=1
        if (!$hasMinister) {
            // Находим министра
            $minister = $db->selectOne('users',
                " WHERE active = 1 AND roles LIKE '%2%' AND position LIKE '%Министр социального развития Московской области%' LIMIT 1");

            if ($minister) {
                $ministerId = intval($minister->id);
                // Добавляем секцию с министром (подписант, последовательное согласование)
                $agreementList[] = [
                    ['stage' => '', 'list_type' => 1], // stage='' означает подписанты
                    [
                        'id'     => $ministerId,
                        'role'   => 1,  // роль подписанта
                        'type'   => 1,  // тип: подпись
                        'result' => null,
                    ]
                ];

                // Статус остаётся 0 (на подписи у министра)
                $finalStatus = 0;
                $finalMessage = $message . '<br>Доклад согласован. Направлен министру на подпись.';

                // Уведомляем министра
                try {
                    $emailSent = $alert->notificationSigner($ministerId, 1, $docId, $agr->name);
                    if (!$emailSent) {
                        error_log('[ДОКЛАД] Email министру не отправлен (возможно не указан email). ID министра: ' . $ministerId);
                    } else {
                        error_log('[ДОКЛАД] Уведомление министру отправлено успешно. ID: ' . $ministerId . ', Доклад: ' . $docId);
                    }
                } catch (\Exception $e) {
                    error_log('[ДОКЛАД] Ошибка отправки уведомления министру: ' . $e->getMessage());
                }
            } else {
                // Министр не найден — всё равно завершаем
                $finalStatus   = 1;
                $finalMessage .= '<br>Доклад согласован (министр не найден в системе).';
                $updateData['doc_number'] = $newDocNumber;
                $updateData['docdate']    = date('Y-m-d');
            }
        } else {
            // Министр уже есть и подписал — документ полностью согласован
            $finalStatus   = 1;
            $finalMessage .= '<br>Доклад подписан министром.';
            $updateData['doc_number'] = $newDocNumber;
            $updateData['docdate']    = date('Y-m-d');
        }
    } else {
        // Для всех остальных типов документов — обычная логика
        $finalStatus   = 1;
        $finalMessage .= '<br>Документ согласован.';
        $updateData['doc_number'] = $newDocNumber;
        $updateData['docdate']    = date('Y-m-d');

        // Уведомление руководителю при подписании приказа (documentacial=1)
        if ($docType == 1) {
            try {
                $alert->notificationOrder($agr->executors_head, $docId, $agr->name);
            } catch (\RedBeanPHP\RedException $e) {
                $finalMessage .= $e->getMessage();
            }
        }
    }

    // ──────────────────────────────────────────────────────────
    // ТРИГГЕР: Доклад министру ПОДПИСАН (documentacial=8)
    // → создаём график устранения нарушений + уведомляем ОК
    // ──────────────────────────────────────────────────────────
    if ($docType == 8 && $finalStatus == 1) {
        $report = $db->selectOne('agreement', ' WHERE id = ?', [$docId]);
        $actId  = intval($report->source_id ?? 0);
        $act    = $actId > 0 ? $db->selectOne('agreement', ' WHERE id = ?', [$actId]) : null;
        $insId  = intval($report->ins_id  ?? 0);
        $planId = intval($report->plan_id ?? 0);

        if ($act && $insId > 0) {

            // 1. Предложения из доклада (не нарушения!)
            $bodyData  = json_decode($report->body ?? '{}', true) ?: [];
            $proposals = [];

            if (isset($bodyData['proposals']) && is_array($bodyData['proposals'])) {
                // Новый формат - массив
                $proposals = array_filter($bodyData['proposals'], function($p) {
                    return strlen(trim($p)) > 0;
                });
            } elseif (isset($bodyData['proposals_text']) && strlen($bodyData['proposals_text']) > 0) {
                // Старый формат - текст (обратная совместимость)
                $proposals = array_filter(
                    array_map('trim', explode("\n", $bodyData['proposals_text'])),
                    function($p) { return strlen($p) > 0; }
                );
            }

            // 2. Строки графика (одна строка = одно предложение)
            $scheduleItems = [];
            foreach ($proposals as $idx => $proposalText) {
                $scheduleItems[] = [
                    'proposal_index'       => $idx,
                    'schedule_offers'      => $proposalText,
                    'schedule_actions'     => '',
                    'schedule_deadlines'   => '',
                    'schedule_responsible' => '',
                    'fix_status'           => 0,
                    'fix_comment'          => '',
                    'fix_files'            => [],
                    'check_comment'        => '',
                    'deadline_extended'    => null,
                    'extended_reason'      => '',
                ];
            }

            // 3. Создаём запись графика (documentacial=5)
            $ins     = $db->selectOne('institutions', ' WHERE id = ?', [$insId]);
            $insName = $ins->name ?? '';

            $roadmapId = $db->insert('agreement', [
                'created_at'    => date('Y-m-d H:i:s'),
                'author'        => intval($_SESSION['user_id']),
                'active'        => 1,
                'name'          => 'График выполнения предложений по результатам проверки — ' . $insName,
                'documentacial' => 5,
                'status'        => 0,
                'source_id'     => $docId,  // ссылка на доклад, а не на акт
                'source_table'  => 'agreement',
                'ins_id'        => $insId,
                'plan_id'       => $planId,
                'agreementlist' => json_encode($scheduleItems, JSON_UNESCAPED_UNICODE),
            ]);

            if ($roadmapId > 0) {
                $finalMessage .= '<br>График выполнения предложений сформирован (' . count($proposals) . ' ' .
                    (count($proposals) == 1 ? 'предложение' : (count($proposals) < 5 ? 'предложения' : 'предложений')) . ').';
            }

            // 4. Уведомляем пользователей ОК учреждения (роль 5)
            $okUsers = $db->select('users',
                " WHERE active = 1 AND institution = ? AND roles LIKE '%5%'", [$insId]);

            foreach ($okUsers as $okUser) {
                $okUserId = intval($okUser->id ?? 0);
                if ($okUserId === 0) continue;

                // Внутреннее уведомление (колокольчик + email)
                try {
                    $alert->notificationSigner(
                        $okUserId,
                        4,
                        $roadmapId > 0 ? $roadmapId : $docId,
                        'График выполнения предложений — ' . $insName
                    );
                } catch (\Exception $e) {
                    error_log('Уведомление ОК (доклад): ' . $e->getMessage());
                }

                // Дополнительный email через notificationObject
                try {
                    $alert->notificationObject(
                        $okUserId,
                        5,
                        $roadmapId > 0 ? $roadmapId : $docId,
                        'График выполнения предложений — ' . $insName
                    );
                } catch (\Exception $e) {
                    error_log('Email ОК (доклад): ' . $e->getMessage());
                }
            }

            if (count($okUsers) > 0) {
                $finalMessage .= '<br>Уведомление направлено в объект контроля.';
            }
        }
    }
    // ── Конец триггера documentacial=8 ──────────────────────

} else {
    // Только перенаправления, всё ещё в процессе
    $finalStatus   = 0;
    $finalMessage .= '<br>Документ в процессе перенаправлений.';
}

// ============================================================
// Сохраняем итог
// ============================================================
$updateData['status']        = $finalStatus;
$updateData['agreementlist'] = json_encode($agreementList, $options);

// Заполняем initiator и initiation если ещё не заполнены
if (empty($agr->initiator)) {
    $updateData['initiator'] = $_SESSION['user_id'];
}
if (empty($agr->initiation)) {
    $updateData['initiation'] = date('Y-m-d H:i:s');
}

if ($finalStatus == 1) {
    $updateData['docdate']    = date('Y-m-d');
    $updateData['doc_number'] = $newDocNumber;
    $result = $db->update('agreement', $docId, $updateData);
    if ($docType == 3) {
        $db->update('checksplans', $planId, ['active' => 1, 'doc_number' => $newDocNumber]);
    }
} else {
    $result = $db->update('agreement', $docId, $updateData);
}

echo json_encode([
    'result'          => $result['result'],
    'resultText'      => $quantityWarning . $finalMessage . '<script>el_app.reloadMainContent();</script>',
    'resultAgreement' => $agreementList,
    'resultStats'     => $globalStats,
    'serverTime'      => date('d.m.Y H:i'),
    'errorFields'     => []
]);