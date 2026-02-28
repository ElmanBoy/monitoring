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
$options = JSON_UNESCAPED_UNICODE |
    JSON_HEX_TAG |      // Преобразует < и > в \u003C и \u003E
    JSON_HEX_AMP |      // Преобразует & в \u0026
    JSON_HEX_APOS |     // Преобразует ' в \u0027
    JSON_HEX_QUOT;      // Преобразует " в \u0022

$agr = $db->selectOne('agreement', ' id = ?', [$docId]);

// Функция для исправления
function fixAgreementList($agreementlist): array
{
    $result = [];

    foreach ($agreementlist as $item) {
        if (is_string($item)) {
            $decoded = json_decode($item, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result[] = $decoded;
            } else {
                $result[] = $item;
            }
        } else {
            $result[] = $item;
        }
    }

    return $result;
}

$_POST['agreementList'] = fixAgreementList($_POST['agreementList']);

$signs = $db->select('signs', " where table_name = 'agreement' AND  doc_id = ?", [$docId]);
if (count($signs) > 0) {
    foreach ($signs as $s) {
        $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
    }
}

if (!is_array($_POST['agreementList'])) {
    throw new Exception('Данные должны быть массивом');
}

$updateArr = [
    'created_at' => date('Y-m-d H:i:s'),
    'author' => $_SESSION['user_id'],
    'agreementlist' => json_encode($_POST['agreementList'], $options)
];
$result = $db->update('agreement', $docId, $updateArr);
if ($result) {
    $message = 'Изменения в документе сохранены.';
}

/*
 * 6 Лист согласования
    5 Устранение нарушений
    4 Доклад
    3 План проверок
    2 Акт
    1 Приказ
 * */

$check = $db->selectOne('agreement', ' WHERE id = ?', [$docId]);
$planId = intval($check->source_id);
$docType = intval($check->documentacial);
$agreementList = json_decode($check->agreementlist, true);
$newDocNumber = $reg->getNewDocNumber($check->documentacial);

// Функция для рекурсивной проверки статуса согласующего
function getApproverStatus($approver): array
{
    $result = $approver['result'] ?? null;

    if (!$result || !is_array($result)) {
        return ['status' => 'pending', 'result_id' => 0];
    }

    $resultId = intval($result['id'] ?? 0);

    switch ($resultId) {
        case 1: // Подписание
        case 2: // Согласование с ЭП
        case 3: // Согласование
            return ['status' => 'approved', 'result_id' => $resultId];
        case 4: // Перенаправление
            return ['status' => 'redirected', 'result_id' => 4];
        case 5: // Отклонение
            return ['status' => 'rejected', 'result_id' => 5];
        default:
            return ['status' => 'pending', 'result_id' => 0];
    }
}

// Функция для рекурсивной проверки всех участников в секции
function checkSectionStatus($section, &$globalStats)
{
    if (!is_array($section)) return;

    // Пропускаем заголовок секции (если есть stage)
    $startIndex = isset($section[0]['stage']) ? 1 : 0;
    $approvers = array_slice($section, $startIndex);

    foreach ($approvers as $approver) {
        if (!isset($approver['id'])) continue;

        $status = getApproverStatus($approver);
        $globalStats['total']++;
        $globalStats[$status['status']]++;

        // Проверяем перенаправления рекурсивно
        if (isset($approver['redirect']) && is_array($approver['redirect'])) {
            checkSectionStatus($approver['redirect'], $globalStats);
        }
    }
}

// Функция для проверки, может ли сотрудник действовать сейчас
function canUserAct($section, $itemArr, $userId, $i, $startIndex, $listType, $getApproverStatus)
{
    // Если уже есть результат - не может действовать
    $status = getApproverStatus($itemArr[$i]);
    if ($status['status'] !== 'pending') {
        return false;
    }

    // Для последовательного согласования проверяем предыдущих
    if ($listType == 1) {
        for ($j = $startIndex; $j < $i; $j++) {
            if (isset($itemArr[$j]['id'])) {
                $prevStatus = getApproverStatus($itemArr[$j]);
                if ($prevStatus['status'] !== 'approved' && $prevStatus['status'] !== 'redirected') {
                    return false;
                }
            }
        }
    }

    return true;
}

// Функция для отправки уведомлений сотрудникам, у которых появилась возможность действия
function sendNotificationsToNextActors($db, $alert, $agreementList, $docId, $docName, $currentUserId, $isNewDocument = false): array
{
    global $docType;
    global $planId;
    $notifiedUsers = []; // Теперь храним массив с ключами "user_id_section"

    // Сначала проверяем, есть ли вообще ожидающие
    $hasAnyPending = false;
    foreach ($agreementList as $section) {
        $startIdx = isset($section[0]['stage']) ? 1 : 0;
        for ($i = $startIdx; $i < count($section); $i++) {
            if (!isset($section[$i]['id'])) continue;
            $status = getApproverStatus($section[$i]);
            if ($status['status'] === 'pending') {
                $hasAnyPending = true;
                break 2;
            }
        }
    }

    if (!$hasAnyPending) {
        return []; // Нет ожидающих - никому не отправляем
    }

    // Проходим по всем секциям по порядку
    foreach ($agreementList as $sectionIndex => $section) {
        if (!is_array($section)) continue;

        $startIndex = isset($section[0]['stage']) ? 1 : 0;
        $listType = isset($section[0]['list_type']) ? intval($section[0]['list_type']) : 1;
        $stage = $section[0]['stage'] ?? '';
        $isSignersSection = ($stage === '');

        // ============ ПРОВЕРЯЕМ ЗАВЕРШЕНИЕ ПРЕДЫДУЩИХ ЭТАПОВ ============
        $allPreviousStagesCompleted = true;

        // Проверяем ВСЕ предыдущие секции
        for ($s = 0; $s < $sectionIndex; $s++) {
            if (!isset($agreementList[$s]) || !is_array($agreementList[$s])) {
                continue;
            }

            $prevSection = $agreementList[$s];
            $prevStartIndex = isset($prevSection[0]['stage']) ? 1 : 0;

            for ($p = $prevStartIndex; $p < count($prevSection); $p++) {
                if (!isset($prevSection[$p]['id'])) continue;

                $prevStatus = getApproverStatus($prevSection[$p]);

                // Если предыдущий этап не завершён - дальше не идём
                if ($prevStatus['status'] !== 'approved' && $prevStatus['status'] !== 'redirected') {
                    $allPreviousStagesCompleted = false;
                    break 3; // Выходим из всех циклов - предыдущие этапы не завершены
                }
            }
        }

        // Если предыдущие этапы не завершены - пропускаем эту секцию
        if (!$allPreviousStagesCompleted) {
            continue;
        }

        // ============ ПРОВЕРЯЕМ НАЛИЧИЕ ОТКЛОНЕНИЙ ============
        $hasRejection = false;
        $rejectorId = null;
        $rejectComment = '';

        for ($i = $startIndex; $i < count($section); $i++) {
            if (!isset($section[$i]['id'])) continue;
            $status = getApproverStatus($section[$i]);
            if ($status['status'] === 'rejected') {
                $hasRejection = true;
                $rejectorId = $section[$i]['id'];
                $rejectComment = $section[$i]['comment'] ?? '';
                break;
            }
        }

        // ============ ЕСЛИ ЕСТЬ ОТКЛОНЕНИЕ ============
        if ($hasRejection) {
            // TODO: ЗАГЛУШКА ДЛЯ БУДУЩЕГО - ОТПРАВКА УВЕДОМЛЕНИЯ РУКОВОДИТЕЛЮ ДЕПАРТАМЕНТА

            // Дективируем план проверок
            if ($docType == 3) {
                $plan = $db->update('checksplans', $planId, ['active' => 2]);
            }

            // Здесь будет код для определения руководителя департамента и отправки уведомления
            /*
            // Пример будущей реализации:
            $departmentHeadId = getDepartmentHeadId($rejectorId); // Функция для определения руководителя
            if ($departmentHeadId && $departmentHeadId != $currentUserId) {
                $notificationKey = $departmentHeadId . '_' . $sectionIndex;
                if (!isset($notifiedUsers[$notificationKey])) {
                    try {
                        $alert->notificationAgreement(
                            $departmentHeadId,
                            $docId,
                            $docName,
                            'отклонения документа. Сотрудник ' . getEmployeeName($rejectorId) . ' отклонил документ. Комментарий: ' . $rejectComment
                        );
                        $notifiedUsers[$notificationKey] = true;
                    } catch (\Exception $e) {
                        error_log("Ошибка отправки уведомления руководителю: " . $e->getMessage());
                    }
                }
            }
            */

            // При отклонении не отправляем уведомления другим согласующим в этой секции
            continue;
        }

        // ============ ПРОВЕРЯЕМ НАЛИЧИЕ ПЕРЕНАПРАВЛЕНИЙ ============
        for ($i = $startIndex; $i < count($section); $i++) {
            if (!isset($section[$i]['id'])) continue;

            $status = getApproverStatus($section[$i]);

            // Если есть перенаправление - отправляем уведомление тому, на кого перенаправили
            if ($status['status'] === 'redirected' && isset($section[$i]['redirect'])) {
                $redirects = $section[$i]['redirect'];
                foreach ($redirects as $redirect) {
                    if (isset($redirect['id'])) {
                        $targetUserId = $redirect['id'];
                        $notificationKey = $targetUserId . '_' . $sectionIndex . '_redirect_' . $i;

                        if ($targetUserId != $currentUserId && !isset($notifiedUsers[$notificationKey])) {
                            try {
                                $stageText = !empty($stage) ? " (этап $stage)" : '';
                                /*$alert->notificationAgreement(
                                    $targetUserId,
                                    $docId,
                                    $docName,
                                    'согласования (перенаправление)' . $stageText
                                );*/
                                $alert->notificationSigner(
                                    $targetUserId,
                                    4,
                                    $docId,
                                    $docName);
                                $notifiedUsers[$notificationKey] = true;
                                error_log("Уведомление сотруднику $targetUserId (перенаправление) для документа $docId в секции $sectionIndex");
                            } catch (\Exception $e) {
                                error_log('Ошибка отправки уведомления: ' . $e->getMessage());
                            }
                        }
                    }
                }
                // Продолжаем проверку, могут быть ещё перенаправления
            }
        }

        // ============ ДЛЯ ПОДПИСАНТОВ ============
        if ($isSignersSection) {
            // Ищем первого НЕподписанного в последовательности
            if ($listType == 1) {
                // Последовательное подписание
                for ($i = $startIndex; $i < count($section); $i++) {
                    if (!isset($section[$i]['id'])) continue;

                    $userId = $section[$i]['id'];
                    $status = getApproverStatus($section[$i]);

                    // Если уже подписал - пропускаем
                    if ($status['status'] === 'approved') {
                        continue;
                    }

                    // Если это первый НЕподписанный - уведомляем его
                    if ($status['status'] === 'pending') {
                        $notificationKey = $userId . '_' . $sectionIndex;

                        // Проверяем, что это не текущий пользователь
                        if ($userId != $currentUserId && !isset($notifiedUsers[$notificationKey])) {
                            // ПРОПУСКАЕМ ТОЛЬКО ПЕРВОГО СОТРУДНИКА В ПЕРВОЙ СЕКЦИИ ПРИ СОЗДАНИИ ДОКУМЕНТА
                            if ($isNewDocument && $sectionIndex == 0 && $i == $startIndex) {
                                // Это первый сотрудник в первой секции при создании документа - не уведомляем
                                error_log("Пропуск уведомления первому сотруднику $userId при создании документа");
                            } else {
                                try {
                                    $stageText = !empty($stage) ? " (этап $stage)" : '';
                                    /*$alert->notificationAgreement(
                                        $userId,
                                        $docId,
                                        $docName,
                                        'подписания' . $stageText
                                    );*/
                                    $alert->notificationSigner(
                                        $userId,
                                        2,
                                        $docId,
                                        $docName);
                                    $notifiedUsers[$notificationKey] = true;
                                    error_log("Уведомление подписанту $userId для документа $docId в секции $sectionIndex");
                                } catch (\Exception $e) {
                                    error_log('Ошибка отправки уведомления: ' . $e->getMessage());
                                }
                            }
                        }
                        break; // Нашли первого - дальше не идём
                    }

                    // Если отклонение или перенаправление - прерываем цепочку
                    if ($status['status'] === 'rejected' || $status['status'] === 'redirected') {
                        break;
                    }
                }
            } else {
                // Параллельное подписание - уведомляем ВСЕХ неподписанных
                for ($i = $startIndex; $i < count($section); $i++) {
                    if (!isset($section[$i]['id'])) continue;

                    $userId = $section[$i]['id'];
                    $status = getApproverStatus($section[$i]);

                    if ($status['status'] === 'pending' && $userId != $currentUserId) {
                        $notificationKey = $userId . '_' . $sectionIndex;

                        if (!isset($notifiedUsers[$notificationKey])) {
                            // ПРОПУСКАЕМ ТОЛЬКО ПЕРВОГО СОТРУДНИКА В ПЕРВОЙ СЕКЦИИ ПРИ СОЗДАНИИ ДОКУМЕНТА
                            if ($isNewDocument && $sectionIndex == 0 && $i == $startIndex) {
                                // Это первый сотрудник в первой секции при создании документа - не уведомляем
                                error_log("Пропуск уведомления первому сотруднику $userId при создании документа");
                            } else {
                                try {
                                    $stageText = !empty($stage) ? " (этап $stage)" : '';
                                    /*$alert->notificationAgreement(
                                        $userId,
                                        $docId,
                                        $docName,
                                        'подписания' . $stageText
                                    );*/
                                    $alert->notificationSigner(
                                        $userId,
                                        1,
                                        $docId,
                                        $docName);
                                    $notifiedUsers[$notificationKey] = true;
                                    error_log("Уведомление подписанту $userId для документа $docId в секции $sectionIndex");
                                } catch (\Exception $e) {
                                    error_log('Ошибка отправки уведомления: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // ============ ДЛЯ СЕКЦИЙ СОГЛАСОВАНИЯ ============

            // Сначала проверяем, есть ли в этой секции вообще ожидающие
            $hasPendingInSection = false;
            for ($i = $startIndex; $i < count($section); $i++) {
                if (!isset($section[$i]['id'])) continue;
                $status = getApproverStatus($section[$i]);
                if ($status['status'] === 'pending') {
                    $hasPendingInSection = true;
                    break;
                }
            }

            if (!$hasPendingInSection) {
                continue; // В этой секции все уже согласовали
            }

            if ($listType == 1) {
                // Последовательное согласование
                for ($i = $startIndex; $i < count($section); $i++) {
                    if (!isset($section[$i]['id'])) continue;

                    $userId = $section[$i]['id'];
                    $status = getApproverStatus($section[$i]);

                    // Если уже согласовал или перенаправил - проверяем следующего
                    if ($status['status'] === 'approved' || $status['status'] === 'redirected') {
                        continue;
                    }

                    // Если это первый НЕсогласовавший - уведомляем его
                    if ($status['status'] === 'pending') {
                        // Проверяем, что все предыдущие согласованы (хотя мы уже дошли до этого)
                        $allPrevApproved = true;
                        for ($j = $startIndex; $j < $i; $j++) {
                            if (!isset($section[$j]['id'])) continue;
                            $prevStatus = getApproverStatus($section[$j]);
                            if ($prevStatus['status'] !== 'approved' && $prevStatus['status'] !== 'redirected') {
                                $allPrevApproved = false;
                                break;
                            }
                        }

                        if ($allPrevApproved && $userId != $currentUserId) {
                            $notificationKey = $userId . '_' . $sectionIndex;

                            if (!isset($notifiedUsers[$notificationKey])) {
                                // ПРОПУСКАЕМ ТОЛЬКО ПЕРВОГО СОТРУДНИКА В ПЕРВОЙ СЕКЦИИ ПРИ СОЗДАНИИ ДОКУМЕНТА
                                if ($isNewDocument && $sectionIndex == 0 && $i == $startIndex) {
                                    // Это первый сотрудник в первой секции при создании документа - не уведомляем
                                    error_log("Пропуск уведомления первому сотруднику $userId при создании документа");
                                } else {
                                    try {
                                        $stageText = !empty($stage) ? " (этап $stage)" : '';
                                        /*$alert->notificationAgreement(
                                            $userId,
                                            $docId,
                                            $docName,
                                            'согласования' . $stageText
                                        );*/
                                        $alert->notificationSigner(
                                            $userId,
                                            4,
                                            $docId,
                                            $docName);
                                        $notifiedUsers[$notificationKey] = true;
                                        error_log("Уведомление согласующему $userId для документа $docId в секции $sectionIndex");
                                    } catch (\Exception $e) {
                                        error_log('Ошибка отправки уведомления: ' . $e->getMessage());
                                    }
                                }
                            }
                        }
                        break; // Нашли первого - дальше не идём
                    }

                    // Если отклонение - прерываем цепочку
                    if ($status['status'] === 'rejected') {
                        break;
                    }
                }
            } else {
                // Параллельное согласование - уведомляем ВСЕХ ожидающих
                for ($i = $startIndex; $i < count($section); $i++) {
                    if (!isset($section[$i]['id'])) continue;

                    $userId = $section[$i]['id'];
                    $status = getApproverStatus($section[$i]);

                    if ($status['status'] === 'pending' && $userId != $currentUserId) {
                        $notificationKey = $userId . '_' . $sectionIndex;

                        if (!isset($notifiedUsers[$notificationKey])) {
                            // ПРОПУСКАЕМ ТОЛЬКО ПЕРВОГО СОТРУДНИКА В ПЕРВОЙ СЕКЦИИ ПРИ СОЗДАНИИ ДОКУМЕНТА
                            if ($isNewDocument && $sectionIndex == 0 && $i == $startIndex) {
                                // Это первый сотрудник в первой секции при создании документа - не уведомляем
                                error_log("Пропуск уведомления первому сотруднику $userId при создании документа");
                            } else {
                                try {
                                    $stageText = !empty($stage) ? " (этап $stage)" : '';
                                    /*$alert->notificationAgreement(
                                        $userId,
                                        $docId,
                                        $docName,
                                        'согласования' . $stageText
                                    );*/
                                    $alert->notificationSigner(
                                        $userId,
                                        2,
                                        $docId,
                                        $docName);
                                    $notifiedUsers[$notificationKey] = true;
                                    error_log("Уведомление согласующему $userId для документа $docId в секции $sectionIndex");
                                } catch (\Exception $e) {
                                    error_log('Ошибка отправки уведомления: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    return array_keys($notifiedUsers); // Возвращаем только ID для совместимости
}

// Обновляем agreementList с данными из user_signs
for ($i = 0; $i < count($agreementList); $i++) {
    $section = $agreementList[$i];

    // Определяем начало списка сотрудников в секции
    $startIndex = isset($section[0]['stage']) ? 1 : 0;

    for ($j = $startIndex; $j < count($section); $j++) {
        $approver = $section[$j];

        if (!isset($approver['id'])) continue;

        $userId = $approver['id'];

        // Если есть подпись ЭЦП в таблице signs
        if (isset($user_signs[$userId][$i])) {
            $signType = intval($user_signs[$userId][$i]['type']);
            $signDate = $user_signs[$userId][$i]['date'];

            // Обновляем результат в agreementList
            if ($signType == 1 || $signType == 2) {
                $agreementList[$i][$j]['result'] = [
                    'id' => $signType,
                    'date' => $signDate
                ];
            }
        }
    }
}

// ============ ДОБАВЛЯЕМ ПОВТОРНУЮ ЗАПИСЬ ПОСЛЕ ПЕРЕНАПРАВЛЕНИЯ ============
$redirectedUsers = [];
for ($i = 0; $i < count($agreementList); $i++) {
    $section = $agreementList[$i];
    $startIndex = isset($section[0]['stage']) ? 1 : 0;

    for ($j = $startIndex; $j < count($section); $j++) {
        $approver = $section[$j];

        if (!isset($approver['id'])) continue;

        // Проверяем, было ли только что совершено перенаправление
        if (isset($approver['result']) && is_array($approver['result']) && intval($approver['result']['id'] ?? 0) == 4) {
            $userId = $approver['id'];
            $redirectedUsers[] = $userId;

            // Проверяем, есть ли уже повторная запись этого сотрудника
            $hasRepeat = false;
            for ($k = $j + 1; $k < count($section); $k++) {
                if (isset($section[$k]['id']) && $section[$k]['id'] == $userId) {
                    $hasRepeat = true;
                    break;
                }
            }

            // Если повторной записи нет - добавляем
            if (!$hasRepeat) {
                // КОПИРУЕМ ВСЕ ПОЛЯ ИЗ ИСХОДНОЙ ЗАПИСИ!
                $repeatEntry = [
                    'id' => $userId,
                    'type' => $approver['type'] ?? 1,
                    'vrio' => $approver['vrio'] ?? '0',
                    'urgent' => $approver['urgent'] ?? '0',
                    'role' => $approver['role'] ?? '0',
                    'result' => null // Без результата, ждёт действия
                ];

                // Вставляем сразу после текущего сотрудника
                array_splice($agreementList[$i], $j + 1, 0, [$repeatEntry]);

                // Сдвигаем счётчик, чтобы не обрабатывать только что добавленную запись
                $j++;
            }
        }
    }
}

// Определяем общий статус документа
$globalStats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'redirected' => 0,
    'rejected' => 0
];

foreach ($agreementList as $section) {
    checkSectionStatus($section, $globalStats);
}

// Определяем финальный статус документа
$finalStatus = 0; // По умолчанию - на согласовании
$finalMessage = $message;

if ($globalStats['rejected'] > 0) {
    // Есть отклонения - документ отклонён
    $finalMessage .= '<br>Документ отклонён.';
} elseif ($globalStats['pending'] > 0) {
    // Есть ожидающие - документ на согласовании
    $finalStatus = 0;
    $finalMessage .= '<br>Документ на согласовании.';

    // ============ ОТПРАВЛЯЕМ УВЕДОМЛЕНИЯ СЛЕДУЮЩИМ УЧАСТНИКАМ ============
    try {
        $isNewDocument = false; // По умолчанию false
        $notifiedUsers = sendNotificationsToNextActors(
            $db,
            $alert,
            $agreementList,
            $docId,
            $agr->name,
            $_SESSION['user_id'],
            $isNewDocument
        );
        $gui = new \Core\Gui();
        if (!empty($notifiedUsers)) {
            $finalMessage .= '<br>Уведомления отправлены: ' . count($notifiedUsers) . ' сотрудник'.
                $gui->postfix($notifiedUsers, 'у', 'ам', 'ам').'.';
        }
    } catch (\Exception $e) {
        error_log('Ошибка отправки уведомлений: ' . $e->getMessage());
    }

} elseif ($globalStats['approved'] > 0 && $globalStats['pending'] == 0 && $globalStats['rejected'] == 0) {
    // Все согласовали - документ согласован
    $finalStatus = 1;
    $finalMessage .= '<br>Документ согласован.';

    //Присваиваем новый номер и дату согласования документу
    $updateData['doc_number'] = $newDocNumber;
    $updateData['docdate'] = date('Y-m-d');

    //В случае подписания приказа о проверке отправляем уведомление руководителю
    if ($docType == 1) {
        try {
            $alert->notificationOrder(
                $agr->executors_head,
                $docId,
                $agr->name
            );
        } catch (\RedBeanPHP\RedException $e) {
            $finalMessage .= $e->getMessage();
        }
    }

} elseif ($globalStats['redirected'] > 0 && $globalStats['pending'] == 0 && $globalStats['rejected'] == 0) {
    // Только перенаправления (редкий случай)
    $finalStatus = 0;
    $finalMessage .= '<br>Документ в процессе перенаправлений.';
}

// Обновляем документ

$updateData['status'] = $finalStatus;
$updateData['agreementlist'] = json_encode($agreementList, $options);

if ($finalStatus == 1) {
    $updateData['docdate'] = date('Y-m-d');
    $updateData['doc_number'] = $newDocNumber;
    $result = $db->update('agreement', $docId, $updateData);

    // Активируем план проверок
    if ($docType == 3) {
        $plan = $db->update('checksplans', $planId, ['active' => 1, 'doc_number' => $newDocNumber]);
    }
} else {
    $result = $db->update('agreement', $docId, $updateData);
}

echo json_encode(array(
    'result' => $result,
    'resultText' => $finalMessage . '<script>el_app.reloadMainContent();</script>',
    'resultAgreement' => $agreementList,
    'resultStats' => $globalStats,
    'errorFields' => []
)
);