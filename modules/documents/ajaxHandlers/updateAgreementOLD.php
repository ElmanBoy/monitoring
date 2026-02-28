<?php

use Core\Registry;
use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db = new Db();
$user_signs = [];

$docId = intval($_POST['docId']);
$message = '';

// Функция для исправления
function fixAgreementList($agreementlist): array
{
    $result = [];

    foreach ($agreementlist as $item) {
        if (is_string($item)) {
            // Если это строка, пытаемся декодировать как JSON
            $decoded = json_decode($item, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $result[] = $decoded;
            } else {
                // Если не JSON, оставляем как есть
                $result[] = $item;
            }
        } else {
            // Если уже массив, оставляем как есть
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

// Можно дополнительно проверить структуру
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

$check = $db->selectOne('agreement', ' WHERE id = ?', [$docId]);
$planId = $check->source_id;
$agreementList = json_decode($check->agreementlist, true);
$results = 0;
$signers = 0;
//print_r($user_signs);

for ($i = 0; $i < count($agreementList); $i++) { //секции
    $itemArr = $agreementList[$i];
    $itemRows = [];
    $stage = $i;
    for ($l = 0; $l < count($itemArr); $l++) { //строки в секции
        if (!isset($itemArr[$l]['stage'])) { //Если это не блок в параметрами секции
            $signers++;
        }
        $userId = $itemArr[$l]['id'];
        //Если нет перенаправления
        if (!isset($itemArr[$l]['redirect']) && !is_array($itemArr[$l]['redirect'])) {
            //Если результат - это не перенапраление
            if (in_array($itemArr[$l]['result']['id'], [1, 2, 3]) || isset($user_signs[$userId][$stage])) {
                //Если есть ЭЦП и это подписание или согласование
                if (isset($user_signs[$userId][$stage]) && in_array($user_signs[$userId][$stage]['type'], [1, 2])) {
                    $itemArr[$l]['result'] = [
                        'id' => $user_signs[$userId][$stage]['type'],
                        'date' => $user_signs[$userId][$stage]['date']
                    ];
                }
                $results++;
            }
        }
        $itemRows[] = $itemArr[$l];
    }
    $agreementList[$i] = $itemRows;
}
$message = 'Изменения в документе сохранены.';
//Если подписей/согласовантов больше или равно количеству согласовантов и подписантов
if ($results >= $signers) {
    $message .= '<br>Документ согласован.';
    $result = $db->update('agreement', $docId, [
        'status' => 1,
        'agreementlist' => json_encode($agreementList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'docdate' => date('Y-m-d')
    ]);
    $plan = $db->update('checksplans', $planId, ['active' => 1]);
} else {
    $result = $db->update('agreement', $docId, [
        'status' => 0,
        'agreementlist' => json_encode($agreementList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
}



echo json_encode(array(
    'result' => $result,
    'resultText' => $message . '<script>el_app.reloadMainContent();</script>',
    'resultAgreement' => $agreementList,
    'resultSigns' => 'signers: '.$signers.' signs: '.$results,
    'errorFields' => [])
);

