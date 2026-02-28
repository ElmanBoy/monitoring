<?php

use Core\Registry;
use Core\Db;
use Core\Notifications;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db();
$reg = new Registry();
$alert = new Notifications();
$user_signs = [];

$docId = intval($_POST['docId']);
$message = '';
$updateData = [];

$jsonOptions = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

// ============ ПОЛУЧАЕМ АКТУАЛЬНЫЙ ДОКУМЕНТ ============
$agr = $db->selectOne('agreement', ' WHERE id = ?', [$docId]);
if (!$agr || !$agr->id) {
    echo json_encode(['result' => false, 'resultText' => 'Документ не найден.', 'errorFields' => []]);
    exit;
}

// ============ НОРМАЛИЗУЕМ ВХОДЯЩИЙ agreementList ============
// Клиент может присылать массив строк JSON или массив массивов.
// fixAgreementList приводит всё к массиву массивов.
function fixAgreementList(array $agreementlist): array
{
    $result = [];
    foreach ($agreementlist as $item) {
        if (is_string($item)) {
            $decoded = json_decode($item, true);
            $result[] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $item;
        } else {
            $result[] = $item;
        }
    }
    return $result;
}

if (!is_array($_POST['agreementList'])) {
    echo json_encode(['result' => false, 'resultText' => 'Некорректные данные.', 'errorFields' => []]);
    exit;
}

$agreementList = fixAgreementList($_POST['agreementList']);

// ============ ЗАГРУЖАЕМ ПОДПИСИ ЭЦП ============
$signs = $db->select('signs', " where table_name = 'agreement' AND doc_id = ?", [$docId]);
if (count($signs) > 0) {
    foreach ($signs as $s) {
        $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
    }
}

// ============ МЕРЖИМ ПОДПИСИ ЭЦП В agreementList ============
// Подписи из таблицы signs всегда приоритетнее данных из клиента
for ($i = 0; $i < count($agreementList); $i++) {
    $section = $agreementList[$i];
    $startIndex = isset($section[0]['stage']) ? 1 : 0;

    for ($j = $startIndex; $j < count($section); $j++) {
        if (!isset($section[$j]['id'])) continue;
        $userId = $section[$j]['id'];

        if (isset($user_signs[$userId][$i])) {
            $signType = intval($user_signs[$userId][$i]['type']);
            if ($signType === 1 || $signType === 2) {
                $agreementList[$i][$j]['result'] = [
                    'id'   => $signType,
                    'date' => $user_signs[$userId][$i]['date']
                ];
            }
        }
    }
}

// ============ ДОБАВЛЯЕМ ПОВТОРНЫЕ ЗАПИСИ ПОСЛЕ ПЕРЕНАПРАВЛЕНИЙ ============
// БАГ #2 ИСПРАВЛЕН: сервер является единственным местом, где добавляются повторные записи.
// На клиенте (agreement.php) убрано дублирование этой логики — клиент только помечает result.id=4.
for ($i = 0; $i < count($agreementList); $i++) {
    $section = $agreementList[$i];
    $startIndex = isset($section[0]['stage']) ? 1 : 0;

    for ($j = $startIndex; $j < count($section); $j++) {
        if (!isset($section[$j]['id'])) continue;

        $approver = $section[$j];
        $resultId = intval($approver['result']['id'] ?? 0);

        if ($resultId !== 4) continue;

        $userId = $approver['id'];

        // Ищем незавершённую повторную запись этого сотрудника после текущей позиции
        $hasRepeat = false;
        for ($k = $j + 1; $k < count($agreementList[$i]); $k++) {
            if (isset($agreementList[$i][$k]['id']) &&
                intval($agreementList[$i][$k]['id']) === intval($userId) &&
                empty($agreementList[$i][$k]['result'])
            ) {
                $hasRepeat = true;
                break;
            }
        }

        if (!$hasRepeat) {
            $repeatEntry = [
                'id'     => $userId,
                'type'   => $approver['type'] ?? 1,
                'vrio'   => $approver['vrio'] ?? '0',
                'urgent' => $approver['urgent'] ?? '0',
                'role'   => $approver['role'] ?? '0',
                'result' => null
            ];
            array_splice($agreementList[$i], $j + 1, 0, [$repeatEntry]);
            $j++; // пропускаем только что добавленную запись
        }
    }
}

// ============ АНАЛИЗ СТАТУСОВ ============

/**
 * Возвращает статус согласующего: pending | approved | redirected | rejected
 */
function getApproverStatus(array $approver): array
{
    $result = $approver['result'] ?? null;
    if (!$result || !is_array($result)) {
        return ['status' => 'pending', 'result_id' => 0];
    }
    switch (intval($result['id'] ?? 0)) {
        case 1:
        case 2:
        case 3:
            return ['status' => 'approved',    'result_id' => intval($result['id'])];
        case 4:
            return ['status' => 'redirected',  'result_id' => 4];
        case 5:
            return ['status' => 'rejected',    'result_id' => 5];
        default:
            return ['status' => 'pending',     'result_id' => 0];
    }
}

/**
 * БАГ #5 ИСПРАВЛЕН: этап считается завершённым, если у каждого участника есть
 * финальный результат (approved) ИЛИ его перенаправление полностью закрыто.
 * Статус redirected считается «завершённым» только если вся его цепочка согласована.
 */
function isRedirectChainComplete(array $redirects): bool
{
    foreach ($redirects as $r) {
        if (!isset($r['id'])) continue;
        $st = getApproverStatus($r);
        if ($st['status'] === 'pending') return false;
        if ($st['status'] === 'redirected') {
            if (!isset($r['redirect']) || !isRedirectChainComplete($r['redirect'])) {
                return false;
            }
        }
        // rejected — прерывает цепочку, считаем незавершённым
        if ($st['status'] === 'rejected') return false;
    }
    return true;
}

function isApproverEffectivelyDone(array $approver): bool
{
    $st = getApproverStatus($approver);
    if ($st['status'] === 'approved') return true;
    if ($st['status'] === 'redirected') {
        return isset($approver['redirect']) && isRedirectChainComplete($approver['redirect']);
    }
    return false;
}

/**
 * Рекурсивно суммирует статистику по секции.
 */
function checkSectionStatus(array $section, array &$globalStats): void
{
    $startIndex = isset($section[0]['stage']) ? 1 : 0;
    $approvers = array_slice($section, $startIndex);

    foreach ($approvers as $approver) {
        if (!isset($approver['id'])) continue;

        $st = getApproverStatus($approver);
        $globalStats['total']++;
        $globalStats[$st['status']]++;

        if (isset($approver['redirect']) && is_array($approver['redirect'])) {
            checkSectionStatus($approver['redirect'], $globalStats);
        }
    }
}

// ============ ОТПРАВКА УВЕДОМЛЕНИЙ ============

function sendNotificationsToNextActors(
    Db $db,
    Notifications $alert,
    array $agreementList,
    int $docId,
    string $docName,
    int $currentUserId,
    bool $isNewDocument = false
): array {
    global $docType, $planId;
    $notifiedUsers = [];

    // Если нет ни одного pending — никому не пишем
    $hasAnyPending = false;
    foreach ($agreementList as $section) {
        $startIdx = isset($section[0]['stage']) ? 1 : 0;
        for ($i = $startIdx; $i < count($section); $i++) {
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
        $listType   = intval($section[0]['list_type'] ?? 2);
        $stage      = $section[0]['stage'] ?? '';
        $isSignersSection = ($stage === '');

        // Все предыдущие секции должны быть завершены
        $allPreviousDone = true;
        for ($s = 0; $s < $sectionIndex; $s++) {
            if (!isset($agreementList[$s])) continue;
            $prev = $agreementList[$s];
            $prevStart = isset($prev[0]['stage']) ? 1 : 0;
            for ($p = $prevStart; $p < count($prev); $p++) {
                if (!isset($prev[$p]['id'])) continue;
                if (!isApproverEffectivelyDone($prev[$p])) {
                    $allPreviousDone = false;
                    break 3;
                }
            }
        }
        if (!$allPreviousDone) continue;

        // Проверяем наличие отклонений в секции
        $hasRejection = false;
        for ($i = $startIndex; $i < count($section); $i++) {
            if (!isset($section[$i]['id'])) continue;
            if (getApproverStatus($section[$i])['status'] === 'rejected') {
                $hasRejection = true;
                break;
            }
        }

        if ($hasRejection) {
            // При отклонении деактивируем план проверок
            if (!empty($docType) && $docType == 3 && !empty($planId)) {
                $db->update('checksplans', $planId, ['active' => 2]);
            }
            continue;
        }

        // Уведомляем тех, на кого было перенаправление
        for ($i = $startIndex; $i < count($section); $i++) {
            if (!isset($section[$i]['id'])) continue;
            if (getApproverStatus($section[$i])['status'] !== 'redirected') continue;
            if (!isset($section[$i]['redirect'])) continue;

            foreach ($section[$i]['redirect'] as $redirect) {
                if (!isset($redirect['id'])) continue;
                $targetId = intval($redirect['id']);
                $key = $targetId . '_' . $sectionIndex . '_redir_' . $i;
                if ($targetId !== $currentUserId && !isset($notifiedUsers[$key])) {
                    try {
                        $alert->notificationSigner($targetId, 4, $docId, $docName);
                        $notifiedUsers[$key] = true;
                    } catch (\Exception $e) {
                        error_log('Ошибка уведомления (перенаправление): ' . $e->getMessage());
                    }
                }
            }
        }

        // Определяем тип уведомления: 1=параллельное, 2=следующий в очереди
        // БАГ #6 ИСПРАВЛЕН: подписанты и согласующие используют корректный notifyType
        $notifyTypeParallel    = 1;
        $notifyTypeSequential  = 2;

        if ($listType == 1) {
            // Последовательно — уведомляем первого pending
            for ($i = $startIndex; $i < count($section); $i++) {
                if (!isset($section[$i]['id'])) continue;
                $userId = intval($section[$i]['id']);
                $st = getApproverStatus($section[$i]);

                if (isApproverEffectivelyDone($section[$i])) continue;
                if ($st['status'] === 'rejected') break;

                // Это первый pending
                if ($st['status'] === 'pending') {
                    $key = $userId . '_' . $sectionIndex;
                    if ($userId !== $currentUserId && !isset($notifiedUsers[$key])) {
                        if (!($isNewDocument && $sectionIndex == 0 && $i == $startIndex)) {
                            try {
                                $alert->notificationSigner($userId, $notifyTypeSequential, $docId, $docName);
                                $notifiedUsers[$key] = true;
                            } catch (\Exception $e) {
                                error_log('Ошибка уведомления: ' . $e->getMessage());
                            }
                        }
                    }
                    break;
                }
            }
        } else {
            // Параллельно — уведомляем всех pending
            for ($i = $startIndex; $i < count($section); $i++) {
                if (!isset($section[$i]['id'])) continue;
                $userId = intval($section[$i]['id']);
                $st = getApproverStatus($section[$i]);

                if ($st['status'] !== 'pending') continue;
                $key = $userId . '_' . $sectionIndex;
                if ($userId !== $currentUserId && !isset($notifiedUsers[$key])) {
                    if (!($isNewDocument && $sectionIndex == 0 && $i == $startIndex)) {
                        try {
                            $alert->notificationSigner($userId, $notifyTypeParallel, $docId, $docName);
                            $notifiedUsers[$key] = true;
                        } catch (\Exception $e) {
                            error_log('Ошибка уведомления: ' . $e->getMessage());
                        }
                    }
                }
            }
        }
    }

    return array_keys($notifiedUsers);
}

// ============ ГЛОБАЛЬНАЯ СТАТИСТИКА ============
$docType  = intval($agr->documentacial);
$planId   = intval($agr->source_id);

$globalStats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'redirected' => 0, 'rejected' => 0];
foreach ($agreementList as $section) {
    checkSectionStatus($section, $globalStats);
}

// ============ ОПРЕДЕЛЯЕМ ФИНАЛЬНЫЙ СТАТУС ============
$newDocNumber = $reg->getNewDocNumber($agr->documentacial);
$finalStatus  = 0;
$finalMessage = 'Изменения в документе сохранены.';

if ($globalStats['rejected'] > 0) {
    // БАГ #8 ЧАСТИЧНО ИСПРАВЛЕН: используем статус 2 для отклонения
    $finalStatus   = 2;
    $finalMessage .= '<br>Документ отклонён.';

} elseif ($globalStats['pending'] > 0) {
    $finalStatus   = 0;
    $finalMessage .= '<br>Документ на согласовании.';

    try {
        $notifiedUsers = sendNotificationsToNextActors(
            $db, $alert, $agreementList, $docId, $agr->name, intval($_SESSION['user_id']), false
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
    $finalStatus   = 1;
    $finalMessage .= '<br>Документ согласован.';
    $updateData['doc_number'] = $newDocNumber;
    $updateData['docdate']    = date('Y-m-d');

    // Уведомление руководителю при подписании приказа
    if ($docType == 1) {
        try {
            $alert->notificationOrder($agr->executors_head, $docId, $agr->name);
        } catch (\RedBeanPHP\RedException $e) {
            $finalMessage .= ' ' . $e->getMessage();
        }
    }

} elseif ($globalStats['redirected'] > 0 && $globalStats['pending'] == 0 && $globalStats['rejected'] == 0) {
    $finalStatus   = 0;
    $finalMessage .= '<br>Документ в процессе перенаправлений.';
}

// ============ БАГ #1 ИСПРАВЛЕН: единственный UPDATE ============
// Убран первый промежуточный update — данные сохраняются один раз после полной обработки.
$updateData['status']        = $finalStatus;
$updateData['agreementlist'] = json_encode($agreementList, $jsonOptions);
$updateData['created_at']    = date('Y-m-d H:i:s');
$updateData['author']        = intval($_SESSION['user_id']);

$result = $db->update('agreement', $docId, $updateData);

if ($result) {
    // Обновляем план проверок согласно финальному статусу
    if ($docType == 3 && $planId > 0) {
        if ($finalStatus == 1) {
            $db->update('checksplans', $planId, ['active' => 1, 'doc_number' => $newDocNumber]);
        } elseif ($finalStatus == 2) {
            $db->update('checksplans', $planId, ['active' => 2]);
        }
    }
}

echo json_encode([
    'result'          => (bool)$result,
    'resultText'      => $finalMessage . '<script>el_app.reloadMainContent();</script>',
    'resultAgreement' => $agreementList,
    'resultStats'     => $globalStats,
    'errorFields'     => []
]);