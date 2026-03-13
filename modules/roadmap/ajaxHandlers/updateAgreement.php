<?php

use Core\Db;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$db     = new Db();
$docId  = intval($_POST['docId']);

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

for ($i = 0; $i < count($agreementList); $i++) {
    $itemArr = $agreementList[$i];
    if (is_string($itemArr)) $itemArr = json_decode($itemArr, true);
    $signers += count($itemArr) - 1;
    for ($l = 1; $l < count($itemArr); $l++) {
        if (!isset($itemArr[$l]['redirect']) || !is_array($itemArr[$l]['redirect'])) {
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