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
$agr = $db->selectOne('agreement', ' WHERE id = ?', [$docId]);
$initiatorId = intval($agr->initiator ?? 0);
if ($initiatorId === 0) {
    $initiatorId = intval($agr->author ?? 0);
}
$agreementList = $_POST['agreementList'];

// ============ Декодируем JSON и преобразуем в правильную структуру ============
function convertArrayToObject($arr) {
    if (!is_array($arr)) {
        return $arr;
    }

    // Проверяем, является ли это массивом с числовыми индексами
    $isNumericArray = true;
    foreach (array_keys($arr) as $key) {
        if (!is_int($key)) {
            $isNumericArray = false;
            break;
        }
    }

    // Если это ассоциативный массив (уже объект), возвращаем как есть
    if (!$isNumericArray) {
        $result = [];
        foreach ($arr as $key => $value) {
            $result[$key] = convertArrayToObject($value);
        }
        return $result;
    }

    // Если это числовой массив, преобразуем в объект на основе структуры
    if (count($arr) > 0 && is_array($arr[0])) {
        // Проверяем, похоже ли на секцию согласования
        if (isset($arr[0][0]) && ($arr[0][0] === '1' || $arr[0][0] === '' || $arr[0][0] === 'stage')) {
            // Это заголовок секции
            $section = [];
            if (count($arr[0]) >= 3) {
                $section['stage'] = $arr[0][0] === '' ? '' : $arr[0][0];
                $section['urgent'] = $arr[0][1] ?? '1';
                $section['list_type'] = $arr[0][2] ?? '2';
            }

            // Остальные элементы - сотрудники
            $result = [$section];
            for ($i = 1; $i < count($arr); $i++) {
                $user = [];
                if (count($arr[$i]) >= 2) {
                    $user['id'] = intval($arr[$i][0]);
                    $user['type'] = intval($arr[$i][1]);

                    // Дополнительные поля
                    if (isset($arr[$i][2]) && $arr[$i][2] !== '') {
                        $user['vrio'] = $arr[$i][2];
                    }
                    if (isset($arr[$i][3]) && $arr[$i][3] !== '') {
                        $user['role'] = $arr[$i][3];
                    }
                    if (isset($arr[$i][4]) && $arr[$i][4] !== '') {
                        $user['urgent'] = $arr[$i][4];
                    }

                    // Результат
                    if (isset($arr[$i][5]) && is_array($arr[$i][5]) && count($arr[$i][5]) >= 2) {
                        $user['result'] = [
                            'id' => intval($arr[$i][5][0]),
                            'date' => $arr[$i][5][1]
                        ];
                    }

                    // Перенаправление
                    if (isset($arr[$i][6]) && is_array($arr[$i][6])) {
                        $user['redirect'] = [];
                        foreach ($arr[$i][6] as $redirect) {
                            if (is_array($redirect) && count($redirect) >= 2) {
                                $user['redirect'][] = [
                                    'id' => intval($redirect[0]),
                                    'type' => intval($redirect[1])
                                ];
                            }
                        }
                    }
                }
                $result[] = $user;
            }
            return $result;
        }
    }

    return $arr;
}

// Декодируем JSON и преобразуем структуру
$decodedAgreementList = [];
foreach ($agreementList as $item) {
    if (is_string($item)) {
        $decoded = json_decode($item, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $decodedAgreementList[] = convertArrayToObject($decoded);
        } else {
            $decodedAgreementList[] = $item;
        }
    } else {
        $decodedAgreementList[] = convertArrayToObject($item);
    }
}
$agreementList = $decodedAgreementList;

// Нормализуем булевые значения и пустые result
function normalizeAgreementSection(array $section): array
{
    foreach ($section as $k => &$item) {
        if (!is_array($item)) continue;
        // Нормализуем _is_redirector_repeat
        if (isset($item['_is_redirector_repeat'])) {
            $item['_is_redirector_repeat'] = ($item['_is_redirector_repeat'] === true
                || $item['_is_redirector_repeat'] === 'true'
                || $item['_is_redirector_repeat'] === 1);
        }
        // Нормализуем result
        if (array_key_exists('result', $item)) {
            if ($item['result'] === '' || $item['result'] === 'null') {
                $item['result'] = null;
            } elseif (is_array($item['result']) && isset($item['result']['id'])) {
                $item['result']['id'] = intval($item['result']['id']);
            }
        }
        // Рекурсивно обрабатываем redirect
        if (isset($item['redirect']) && is_array($item['redirect'])) {
            $item['redirect'] = normalizeAgreementSection($item['redirect']);
        }
    }
    return $section;
}
foreach ($agreementList as &$section) {
    $section = normalizeAgreementSection($section);
}
unset($section);

// ============ ВАЛИДАЦИЯ СЕКЦИИ ПОДПИСАНТОВ ============
// Проверяем соответствие бизнес-правилам из /IGNORE/Agreement.md
// ВАЖНО: Считаем только участников ПЕРВОГО УРОВНЯ (не учитываем redirect и _is_redirector_repeat)
$validationErrors = [];
$documentType = intval($agr->documentacial ?? 0);

foreach ($agreementList as $sectionIdx => &$section) {
    if (!is_array($section) || !isset($section[0])) continue;

    // Проверяем секцию подписантов (stage="" или не указан)
    $isSignersSection = (!array_key_exists('stage', $section[0]) || $section[0]['stage'] === '');

    if ($isSignersSection) {
        $participants = array_slice($section, 1); // Пропускаем метаданные

        // Считаем только участников ПЕРВОГО УРОВНЯ (не перенаправленных и не возвраты)
        $firstLevelParticipants = [];
        $signerCount = 0;      // type=1
        $approverCount = 0;    // type=2

        foreach ($participants as $p) {
            if (!is_array($p)) continue;

            // Пропускаем участников, которые являются возвратом после перенаправления
            if (!empty($p['_is_redirector_repeat'])) {
                continue;
            }

            // Это участник первого уровня
            $firstLevelParticipants[] = $p;

            if (!isset($p['type']) && !isset($p['role'])) continue;
            $type = isset($p['type']) ? intval($p['type']) : (isset($p['role']) ? (intval($p['role']) == 1 ? 1 : 2) : 0);

            if ($type == 1) $signerCount++;
            if ($type == 2) $approverCount++;
        }

        $participantCount = count($firstLevelParticipants);

        // Правило 1: Максимум 2 участника первого уровня в секции подписантов
        if ($participantCount > 2) {
            $validationErrors[] = "Секция подписантов: превышено максимальное количество участников первого уровня (найдено: {$participantCount}, максимум: 2). Перенаправления не учитываются.";
        }

        // Правило 2: Максимум 1 подписант (type=1) первого уровня
        if ($signerCount > 1) {
            $validationErrors[] = "Секция подписантов: слишком много подписантов первого уровня (найдено: {$signerCount}, максимум: 1)";
        }

        // Правило 3: Максимум 1 утверждающий (type=2) первого уровня
        if ($approverCount > 1) {
            $validationErrors[] = "Секция подписантов: слишком много утверждающих первого уровня (найдено: {$approverCount}, максимум: 1)";
        }

        // Правило 4: Для приказов (documentacial=1) только 1 подписант первого уровня, без утверждающих
        if ($documentType == 1) {
            if ($participantCount != 1 || $signerCount != 1 || $approverCount > 0) {
                $validationErrors[] = "Приказ: должен иметь ровно 1 подписанта первого уровня без утверждающих (сейчас: {$participantCount} участник(ов) первого уровня, {$approverCount} утверждающих)";
            }
        }
    }
}
unset($section);

// Если есть ошибки валидации - возвращаем их
if (count($validationErrors) > 0) {
    echo json_encode([
        'result' => false,
        'resultText' => implode("\n", $validationErrors),
        'html' => ''
    ]);
    exit;
}

// Автоисправление порядка подписантов: role=1 (подписывает) должен быть перед role=0 (утверждает)
$agreementListChanged = false;
foreach ($agreementList as &$section) {
    if (!is_array($section) || !isset($section[0])) continue;
    // Только секция подписантов (stage="")
    if (!array_key_exists('stage', $section[0]) || $section[0]['stage'] !== '') continue;

    $startIdx = 1;
    $items = array_slice($section, $startIdx);

    // Сортируем: role=1 перед role=0, _is_redirector_repeat в конец
    usort($items, function($a, $b) {
        $aRepeat = !empty($a['_is_redirector_repeat']) ? 1 : 0;
        $bRepeat = !empty($b['_is_redirector_repeat']) ? 1 : 0;
        if ($aRepeat !== $bRepeat) return $aRepeat - $bRepeat;
        $ra = intval($a['role'] ?? 0);
        $rb = intval($b['role'] ?? 0);
        return $rb - $ra; // role=1 раньше role=0
    });

    // Проверяем изменился ли порядок
    $original = array_slice($section, $startIdx);
    $originalIds = array_column($original, 'id');
    $sortedIds   = array_column($items, 'id');
    if ($originalIds !== $sortedIds) {
        array_splice($section, $startIdx, count($items), $items);
        $agreementListChanged = true;
    }
}
unset($section);

// Если порядок изменился — сохраняем в БД
if ($agreementListChanged && $docId > 0) {
    $options = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
    $db->update('agreement', $docId, [
        'agreementlist' => json_encode($agreementList, $options)
    ]);
}

// Получаем все необходимые данные
$users = $db->getRegistry('users', '', [], ['surname', 'name', 'middle_name', 'institution', 'ministries', 'division', 'position']);
$urgent_types = [
    1 => 'Обычный',
    2 => '<span style="color: #d8720b">Срочный</span>',
    3 => '<span style="color: #d8110b">Незамедлительно</span>'
];

// Получаем подписи
$signs = $db->select('signs', " where table_name = 'agreement' AND  doc_id = ?", [$docId]);
if (count($signs) > 0) {
    foreach ($signs as $s) {
        $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
    }
}

// Получаем данные об учреждениях для title
$ins = $db->getRegistry('institutions', '', [], ['short']);
$mins = $db->getRegistry('ministries');
$units = $db->getRegistry('units');

// Функция для проверки завершенности этапа
function checkStageComplete(array $itemArr): bool
{
    $itemUsers = [];
    $itemResults = [];
    foreach ($itemArr as $item) {
        if (is_array($item) && isset($item['id'])) {
            $itemUsers[] = $item['id'];
            if (isset($item['result']) && is_array($item['result']) && isset($item['result']['id'])) {
                $resultId = intval($item['result']['id']);
                if (!in_array($resultId, [4, 5, 6])) {
                    $itemResults[] = $item['result'];
                }
            }
        }
    }
    return count($itemUsers) > 0 && count($itemUsers) <= count($itemResults);
}

// Определяем типы согласования для заголовка
$list_types = [];
for ($i = 0; $i < count($agreementList); $i++) {
    $itemArr = $agreementList[$i];
    if (is_array($itemArr) && isset($itemArr[0]) && is_array($itemArr[0]) && isset($itemArr[0]['list_type'])) {
        $list_type = (string)$itemArr[0]['list_type'];
        if (!in_array($list_type, $list_types)) {
            $list_types[] = $list_type;
        }
    }
}

$listTypeText = 'параллельное';
if (count($list_types) > 0) {
    $listTypeText = (count($list_types) > 1) ? 'смешанное' : (($list_types[0] == '1') ? 'последовательное' : 'параллельное');
}

// Генерируем HTML всей таблицы ПОЛНОСТЬЮ
$html = '<style>
/* Правило 5: индикация очерёдности для текущего пользователя */
.agreement-table tr.my-turn-active td { color: #000; font-weight: 500; }
.agreement-table tr.my-turn-waiting td { color: #9e9e9e; }
.agreement-table tr.my-turn-active td:first-child::before {
    content: "";
    display: inline-block;
    width: 6px; height: 6px;
    background: #086a9b;
    border-radius: 50%;
    margin-right: 4px;
    vertical-align: middle;
}
</style>' .
    '<div class="agreement_list"><h4>ЛИСТ СОГЛАСОВАНИЯ</h4>' .
    '<div class="list_type">Тип согласования: <strong>' . $listTypeText . '</strong></div>' .
    '<table class="agreement-table">' .
    '<thead><tr>' .
    '<th>№</th><th style="width: 30%;">ФИО</th>' .
    '<th>Срок согласования</th>' .
    '<th style="width:30%">Результат согласования</th>' .
    '<th>Комментарии</th>' .
    '</tr></thead>';

// Листаем секции
for ($i = 0; $i < count($agreementList); $i++) {
    $itemArr = $agreementList[$i];

    if (!is_array($itemArr) || count($itemArr) == 0) {
        continue;
    }

    // Определяем завершен ли этап
    $stageComplete = checkStageComplete($itemArr);

    $html .= '<tbody' . ($stageComplete ? '' : ' class="notComplete"') . '>';
    $html .= '<tr><td class="divider" colspan="5">';

    // Заголовок этапа
    if (isset($itemArr[0]['stage']) && $itemArr[0]['stage'] !== '' && intval($itemArr[0]['stage']) > 0) {
        $html .= '<strong>Этап ' . intval($itemArr[0]['stage']) . '</strong><br>';
    } else {
        $html .= '<strong>Подписанты</strong><br>';
    }

    // Тип согласования
    $listType = isset($itemArr[0]['list_type']) ? $itemArr[0]['list_type'] : '2';
    $html .= 'Тип согласования: <strong>' .
        ((string)$listType == '1' ? 'последовательное' : 'параллельное') .
        '</strong>';

    // Кодируем JSON с ключами
    $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;
    $jsonString = json_encode($itemArr, $jsonOptions);

    $html .= '<input type="hidden" name="addAgreement" id="ag' . $i . '" value=\'' . $jsonString . '\'>';
    $html .= '</td></tr>';

    // Генерируем строки согласования через buildAgreementList
    $html .= $reg->buildAgreementList($itemArr, $i, $users, $urgent_types, $user_signs, $reg, 0, $agreementList, $initiatorId);

    $html .= '</tbody>';
}

$html .= '</table></div>';

echo json_encode(['result' => true, 'html' => $html]);
exit;