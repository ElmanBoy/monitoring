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
$message  = '';
$updateData = [];
$options  = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

$agr = $db->selectOne('agreement', ' id = ?', [$docId]);

// ============================================================
// Исправляем возможную двойную JSON-сериализацию элементов
// ============================================================
function fixAgreementList($agreementlist): array
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

$_POST['agreementList'] = fixAgreementList($_POST['agreementList']);

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
        default:
            return ['status' => 'pending',    'result_id' => 0];
    }
}

// ============================================================
// Завершена ли redirect-цепочка (рекурсивно)
// ============================================================
function isRedirectChainCompleted(array $redirectArr): bool
{
    foreach ($redirectArr as $approver) {
        if (!isset($approver['id'])) continue;
        $status = getApproverStatus($approver);
        if ($status['status'] === 'pending') return false;
        if ($status['status'] === 'redirected') {
            if (isset($approver['redirect']) && is_array($approver['redirect'])) {
                if (!isRedirectChainCompleted($approver['redirect'])) return false;
            } else {
                return false;
            }
        }
    }
    return true;
}

// ============================================================
// ПРАВИЛО 4: Добавить повторную запись перенаправившего
// сразу после перенаправленного (если ещё не добавлена).
// ============================================================
function insertRedirectorRepeatEntry(array &$agreementList): void
{
    for ($i = 0; $i < count($agreementList); $i++) {
        $section    = &$agreementList[$i];
        $startIndex = isset($section[0]['stage']) ? 1 : 0;

        for ($j = $startIndex; $j < count($section); $j++) {
            $approver = $section[$j];
            if (!isset($approver['id'])) continue;

            $status = getApproverStatus($approver);
            if ($status['status'] !== 'redirected') continue;

            $userId = $approver['id'];

            // Проверяем, нет ли уже повторной записи после текущей позиции
            $hasRepeat = false;
            for ($k = $j + 1; $k < count($section); $k++) {
                if (isset($section[$k]['id']) && $section[$k]['id'] == $userId
                    && !isset($section[$k]['result'])) {
                    $hasRepeat = true;
                    break;
                }
            }

            if (!$hasRepeat) {
                $repeatEntry = [
                    'id'                    => $userId,
                    'type'                  => $approver['type']   ?? 1,
                    'vrio'                  => $approver['vrio']   ?? '0',
                    'urgent'                => $approver['urgent'] ?? '0',
                    'role'                  => $approver['role']   ?? '0',
                    'result'                => null,
                    '_is_redirector_repeat' => true
                ];
                array_splice($section, $j + 1, 0, [$repeatEntry]);
                $j++; // пропускаем только что вставленную запись
            }
        }
    }
}

// ============================================================
// Сбор глобальной статистики
// ============================================================
function collectGlobalStats(array $agreementList): array
{
    $stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'redirected' => 0, 'rejected' => 0];

    foreach ($agreementList as $section) {
        $startIndex = isset($section[0]['stage']) ? 1 : 0;
        for ($i = $startIndex; $i < count($section); $i++) {
            if (!isset($section[$i]['id'])) continue;
            $st = getApproverStatus($section[$i]);
            $stats['total']++;
            $stats[$st['status']]++;
            if (isset($section[$i]['redirect']) && is_array($section[$i]['redirect'])) {
                foreach ($section[$i]['redirect'] as $rd) {
                    if (!isset($rd['id'])) continue;
                    $rst = getApproverStatus($rd);
                    $stats['total']++;
                    $stats[$rst['status']]++;
                }
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
                foreach ($section[$i]['redirect'] as $rd) {
                    if (!isset($rd['id'])) continue;
                    if (getApproverStatus($rd)['status'] !== 'pending') continue;
                    $targetId = $rd['id'];
                    $key = $targetId . '_redirect_' . $sectionIndex . '_' . $i;
                    if ($targetId != $currentUserId && !isset($notified[$key])) {
                        try {
                            $alert->notificationSigner($targetId, 4, $docId, $docName);
                            $notified[$key] = true;
                        } catch (\Exception $e) {
                            error_log('Уведомление (перенаправление): ' . $e->getMessage());
                        }
                    }
                }
            }

            // Повторная запись перенаправившего — уведомляем, если redirect завершён
            if (isset($section[$i]['_is_redirector_repeat']) && $section[$i]['_is_redirector_repeat']) {
                $redirectorId = $section[$i]['id'];
                for ($prev = $startIndex; $prev < $i; $prev++) {
                    if (isset($section[$prev]['id']) && $section[$prev]['id'] == $redirectorId
                        && isset($section[$prev]['redirect'])) {
                        if (isRedirectChainCompleted($section[$prev]['redirect'])) {
                            $key = $redirectorId . '_redirector_back_' . $sectionIndex . '_' . $i;
                            if (!isset($notified[$key])) {
                                try {
                                    $alert->notificationSigner($redirectorId, 4, $docId, $docName);
                                    $notified[$key] = true;
                                } catch (\Exception $e) {
                                    error_log('Уведомление (возврат перенаправившему): ' . $e->getMessage());
                                }
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
                        error_log('Уведомление (параллельное): ' . $e->getMessage());
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
                            error_log('Уведомление (последовательное): ' . $e->getMessage());
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
// ============================================================
for ($i = 0; $i < count($agreementList); $i++) {
    $startIndex = isset($agreementList[$i][0]['stage']) ? 1 : 0;
    for ($j = $startIndex; $j < count($agreementList[$i]); $j++) {
        if (!isset($agreementList[$i][$j]['id'])) continue;
        $userId = $agreementList[$i][$j]['id'];
        if (isset($user_signs[$userId][$i])) {
            $signType = intval($user_signs[$userId][$i]['type']);
            if (in_array($signType, [1, 2])) {
                $agreementList[$i][$j]['result'] = [
                    'id'   => $signType,
                    'date' => $user_signs[$userId][$i]['date']
                ];
            }
        }
    }
}

insertRedirectorRepeatEntry($agreementList);

$globalStats = collectGlobalStats($agreementList);

// ============================================================
// Итоговый статус документа + отправка уведомлений
// ============================================================
$finalStatus  = 0;
$finalMessage = $message;

if ($globalStats['rejected'] > 0) {
    // Есть отклонение — документ отклонён
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

    // ──────────────────────────────────────────────────────────
    // ТРИГГЕР: Доклад министру подписан (documentacial=8)
    // → создаём график устранения нарушений + уведомляем ОК
    // ──────────────────────────────────────────────────────────
    if ($docType == 8) {
        $report = $db->selectOne('agreement', ' WHERE id = ?', [$docId]);
        $actId  = intval($report->source_id ?? 0);
        $act    = $actId > 0 ? $db->selectOne('agreement', ' WHERE id = ?', [$actId]) : null;
        $insId  = intval($report->ins_id  ?? 0);
        $planId = intval($report->plan_id ?? 0);

        if ($act && $insId > 0) {

            // 1. Нарушения, выбранные при формировании доклада
            $bodyData     = json_decode($report->body ?? '{}', true) ?: [];
            $violationIds = $bodyData['violation_ids'] ?? [];

            $allViolations = [];
            if ($planId > 0) {
                $plan = $db->selectOne('checksplans', ' WHERE id = ?', [$planId]);
                if ($plan && strlen($plan->uid ?? '') > 0) {
                    $staffRows = $db->select('checkstaff',
                        ' WHERE check_uid = ? AND institution = ?', [$plan->uid, $insId]);
                    if (count($staffRows) > 0) {
                        $taskIds = array_map(function($r){ return intval($r->id); }, $staffRows);
                        $rawVio  = $db->db::getAll(
                            'SELECT * FROM ' . TBL_PREFIX . 'checksviolations WHERE tasks IN (' .
                            implode(',', $taskIds) . ') ORDER BY id'
                        );
                        foreach ((array)$rawVio as $v) {
                            if (count($violationIds) === 0 || in_array(intval($v['id']), $violationIds)) {
                                $allViolations[] = $v;
                            }
                        }
                    }
                }
            }

            // 2. Строки графика (одна строка = одно нарушение)
            $scheduleItems = [];
            foreach ($allViolations as $v) {
                $scheduleItems[] = [
                    'violation_id'         => intval($v['id']),
                    'schedule_offers'      => $v['name'] ?? '',
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
                'name'          => 'График устранения нарушений — ' . $insName,
                'documentacial' => 5,
                'status'        => 0,
                'source_id'     => $actId,
                'source_table'  => 'agreement',
                'ins_id'        => $insId,
                'plan_id'       => $planId,
                'agreementlist' => json_encode($scheduleItems, JSON_UNESCAPED_UNICODE),
            ]);

            if ($roadmapId > 0) {
                $finalMessage .= '<br>График устранения нарушений сформирован.';
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
                        'График устранения нарушений — ' . $insName
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
                        'График устранения нарушений — ' . $insName
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
    'resultText'      => $finalMessage . '<script>el_app.reloadMainContent();</script>',
    'resultAgreement' => $agreementList,
    'resultStats'     => $globalStats,
    'errorFields'     => []
]);