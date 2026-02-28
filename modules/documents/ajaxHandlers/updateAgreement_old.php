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
    'agreementlist' => json_encode($_POST['agreementList'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
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

        // Рекурсивно обновляем перенаправления
        if (isset($approver['redirect']) && is_array($approver['redirect'])) {
            // Здесь можно добавить рекурсивное обновление, если нужно

        }
    }
}

// ============ ДОБАВЛЯЕМ ПОВТОРНУЮ ЗАПИСЬ ПОСЛЕ ПЕРЕНАПРАВЛЕНИЯ ============
for ($i = 0; $i < count($agreementList); $i++) {
    $section = $agreementList[$i];
    $startIndex = isset($section[0]['stage']) ? 1 : 0;

    for ($j = $startIndex; $j < count($section); $j++) {
        $approver = $section[$j];

        if (!isset($approver['id'])) continue;

        // Проверяем, было ли только что совершено перенаправление
        if (isset($approver['result']) && is_array($approver['result']) && intval($approver['result']['id'] ?? 0) == 4) {
            $userId = $approver['id'];

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
} elseif ($globalStats['approved'] > 0 && $globalStats['pending'] == 0 && $globalStats['rejected'] == 0) {
    // Все согласовали - документ согласован
    $finalStatus = 1;
    $finalMessage .= '<br>Документ согласован.';

    //Присваиваем новый номер и дату согласования документу
    $updateData['doc_number'] = $newDocNumber;
    $updateData['docdate'] = date('Y-m-d');

    //В случае подписания приказа о проверке отправляем уведомление руководителю
    if($docType == 1){
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
$updateData['agreementlist'] = json_encode($agreementList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);


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
));