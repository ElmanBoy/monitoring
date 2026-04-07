<?php

use Core\Gui;
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

/*if (isset($_GET['id']) && intval($_GET['id']) > 0 && !isset($_POST['params'])) {
	$regId = intval($_GET['id']);
} else {
	parse_str($_POST['params'], $paramArr);
	foreach ($paramArr as $name => $value) {
		$_GET[$name] = $value;
	}
	$regId = intval($_GET['id']);
	$_GET['url'] = $_POST['url'];
}*/

/**
 * Возвращает данные для отображения иконки статуса документа
 *
 * @param int $userId ID пользователя
 * @param string $agreementListJson JSON строка agreementlist
 * @return array Данные для иконки: [icon_class, color, title, can_approve]
 */
/*function getDocumentStatusForIcon(int $userId, string $agreementListJson, int $status): array
{
    if($status != 1) {
        // Декодируем JSON
        $data = json_decode($agreementListJson, true);

        // Если некорректные данные
        if (!is_array($data)) {
            error_log('buildAgreementStatus: некорректный JSON для документа, agreementlist: ' . substr($agreementListJson, 0, 200));
            return [
                'icon_class' => 'fa-question-circle',
                'color' => 'var(--color_02)', // серый
                'title' => 'Ошибка данных',
                'can_approve' => false,
                'status_type' => 'error'
            ];
        }

        // Сортируем по stage для правильной очередности
        usort($data, function ($a, $b) {
            $stageA = isset($a[0]['stage']) ? intval($a[0]['stage']) : 999;
            $stageB = isset($b[0]['stage']) ? intval($b[0]['stage']) : 999;
            return $stageA <=> $stageB;
        }
        );

        $userFound = false;
        $currentStageAllApproved = false;

        // Проходим по этапам в порядке очереди
        foreach ($data as $section) {
            if (!is_array($section) || count($section) < 2) {
                continue;
            }

            $sectionInfo = $section[0] ?? [];
            $approvers = array_slice($section, 1);
            $stage = $sectionInfo['stage'] ?? '';

            // Проверяем всех согласующих в этом этапе
            $allInStageApproved = true;
            $userInThisStage = false;
            $userApprovedThisStage = false;

            foreach ($approvers as $approver) {
                $hasResult = !empty($approver['result']);

                if (!$hasResult) {
                    $allInStageApproved = false;
                }

                // Если это текущий пользователь
                if (($approver['id'] ?? null) == $userId) {
                    $userFound = true;
                    $userInThisStage = true;
                    $userApprovedThisStage = $hasResult;
                }
            }

            // Если все в этапе согласовали
            if ($allInStageApproved) {
                if ($userApprovedThisStage) {
                    // Пользователь уже согласовал этот документ
                    return [
                        'icon_class' => 'fa-check-circle',
                        'color' => 'var(--green)', // зелёный
                        'title' => 'Вы уже согласовали' . ($stage ? " (этап $stage)" : ''),
                        'can_approve' => false,
                        'status_type' => 'approved'
                    ];
                }
                continue; // Переходим к следующему этапу
            }

            // Этап не завершён
            if ($userInThisStage && !$userApprovedThisStage) {
                // Пользователь должен согласовать в этом этапе
                $statusTitle = $allInStageApproved
                    ? 'Требует вашего согласования' . ($stage ? " (этап $stage)" : '')
                    : 'Ожидает других согласующих' . ($stage ? " (этап $stage)" : '');

                return [
                    'icon_class' => $allInStageApproved ? 'fa-exclamation-circle' : 'fa-clock',
                    'color' => $allInStageApproved ? 'var(--blue)' : '#ffc107', // жёлтый или голубой
                    'title' => $statusTitle,
                    'can_approve' => $allInStageApproved,
                    'status_type' => $allInStageApproved ? 'requires_approval' : 'waiting_others',
                    'stage' => $stage,
                    'urgent' => $sectionInfo['urgent'] ?? ''
                ];
            } else {
                // Этап не завершён, но пользователь не в нём
                return [
                    'icon_class' => 'fa-pause-circle',
                    'color' => 'var(--color_02)', // серый
                    'title' => 'Ожидает завершения других этапов',
                    'can_approve' => false,
                    'status_type' => 'waiting_stages'
                ];
            }
        }


        // Если пользователь не найден в документе
        if (!$userFound) {
            return [
                'icon_class' => 'fa-minus-circle',
                'color' => '#adb5bd', // светло-серый
                'title' => 'Вы не участвуете в согласовании',
                'can_approve' => false,
                'status_type' => 'not_involved'
            ];
        }

        // Если все этапы пройдены и пользователь участвовал
        return [
            'icon_class' => 'fa-check-circle',
            'color' => '#28a745', // зелёный
            'title' => 'Документ согласован (вы участвовали)',
            'can_approve' => false,
            'status_type' => 'completed'
        ];
    }
}*/

/**
 * Проверяет соответствие количества подписантов требованиям
 *
 * @param string $agreementListJson JSON строка agreementlist
 * @param int $documentType Тип документа (documentacial)
 * @return array ['valid' => bool, 'error' => string]
 */
function validateSignersCount(?string $agreementListJson, int $documentType): array
{
    $data = json_decode($agreementListJson, true);

    if (!is_array($data) || empty($data)) {
        return ['valid' => false, 'error' => 'Нет данных согласования'];
    }

    // Ищем секцию подписантов (stage='')
    foreach ($data as $section) {
        if (!is_array($section) || !isset($section[0])) continue;

        // Проверяем только секцию подписантов
        $isSignersSection = (!array_key_exists('stage', $section[0]) || $section[0]['stage'] === '');

        if ($isSignersSection) {
            $participants = array_slice($section, 1);

            // Считаем участников первого уровня
            $firstLevelCount = 0;
            $signerCount = 0;
            $approverCount = 0;

            foreach ($participants as $p) {
                if (!is_array($p)) continue;

                // Пропускаем записи с перенаправлением (result.id=4),
                // НО ТОЛЬКО если это НЕ повторная запись (_is_redirector_repeat)
                // Повторные записи всегда учитываются, даже если у них есть result
                $isRepeat = !empty($p['_is_redirector_repeat']);
                $hasRedirect = isset($p['result']['id']) && intval($p['result']['id']) === 4;

                if ($hasRedirect && !$isRepeat) {
                    // Обычная запись с перенаправлением - пропускаем
                    continue;
                }

                // Учитываем участника первого уровня
                $firstLevelCount++;

                if (isset($p['role'])) {
                    $role = intval($p['role']);
                    if ($role == 1) $signerCount++;
                    if ($role == 0) $approverCount++;
                }
            }

            // Проверяем в зависимости от типа документа
            if ($documentType == 1) {
                // Приказ: только 1 подписант
                if ($firstLevelCount != 1 || $signerCount != 1 || $approverCount > 0) {
                    return [
                        'valid' => false,
                        'error' => 'Ошибка подписантов',
                        'error_full' => 'Приказ должен иметь ровно 1 подписанта без утверждающих'
                    ];
                }
            } else {
                // Остальные документы: 1 подписант + 1 утверждающий
                if ($firstLevelCount != 2 || $signerCount != 1 || $approverCount != 1) {
                    return [
                        'valid' => false,
                        'error' => 'Ошибка подписантов',
                        'error_full' => 'Документ должен иметь 2 участника первого уровня - один подписывает, второй утверждает'
                    ];
                }
            }

            return ['valid' => true, 'error' => ''];
        }
    }

    // Секция подписантов не найдена
    return ['valid' => false, 'error' => 'Секция подписантов отсутствует'];
}

/**
 * Возвращает данные для отображения иконки статуса документа
 * Учитывает последовательность согласования, перенаправления и отклонения
 *
 * @param int $userId ID пользователя
 * @param string $agreementListJson JSON строка agreementlist
 * @param int $documentStatus Общий статус документа
 * @param int $docId ID документа для загрузки подписей
 * @return array Данные для иконки: [icon_class, color, title, can_approve, ...]
 */
function getDocumentStatusForIcon(int $userId, ?string $agreementListJson, int $documentStatus, int $docId = 0): array
{
    global $db;

    // Декодируем JSON
    $data = json_decode($agreementListJson, true);

    // Загружаем подписи из таблицы cam_signs
    $user_signs = [];
    if ($docId > 0) {
        $signs = $db->select('signs', " WHERE table_name = 'agreement' AND doc_id = ?", [$docId]);
        foreach ($signs as $s) {
            $user_signs[$s->user_id][$s->section] = ['type' => $s->type, 'date' => $s->created_at];
        }
    }

    // Если нет данных согласования или пустой массив
    if (!is_array($data) || empty($data)) {
        return [
            'icon_class' => 'radio_button_unchecked',
            'color' => 'var(--color_02)',
            'title' => 'Нет данных согласования',
            'status_text' => 'Пустое согласование',
            'can_approve' => false,
            'status_type' => 'no_agreement_data'
        ];
    }

    // Функция определения статуса согласующего
    // $isInsideRedirect - флаг, что запись находится внутри redirect[] другого участника
    $getApproverStatus = function($approver, $section = null, $isInsideRedirect = false) use ($user_signs) {
        $result = $approver['result'] ?? null;

        // Если result не установлен, проверяем cam_signs
        if (!$result || !is_array($result)) {
            // ВАЖНО: НЕ проверяем user_signs для записей внутри redirect,
            // иначе будет копироваться старый статус того же пользователя
            if (!$isInsideRedirect && isset($user_signs[$approver['id']])) {
                // Если указана секция - проверяем её
                if ($section !== null && isset($user_signs[$approver['id']][$section])) {
                    $signType = intval($user_signs[$approver['id']][$section]['type']);
                    $signDate = $user_signs[$approver['id']][$section]['date'];
                    return ['status' => 'approved', 'result_id' => $signType, 'date' => $signDate];
                }
                // Если секция не указана - берём первую найденную
                if ($section === null) {
                    $firstSign = reset($user_signs[$approver['id']]);
                    $signType = intval($firstSign['type']);
                    $signDate = $firstSign['date'];
                    return ['status' => 'approved', 'result_id' => $signType, 'date' => $signDate];
                }
            }
            return ['status' => 'pending', 'result_id' => 0];
        }

        $resultId = intval($result['id'] ?? 0);

        switch ($resultId) {
            case 1: // Подписание
                return ['status' => 'approved', 'result_id' => 1, 'date' => $result['date'] ?? ''];
            case 2: // Согласование с ЭП
                return ['status' => 'approved', 'result_id' => 2, 'date' => $result['date'] ?? ''];
            case 3: // Согласование
                return ['status' => 'approved', 'result_id' => 3, 'date' => $result['date'] ?? ''];
            case 4: // Перенаправление
                return ['status' => 'redirected', 'result_id' => 4, 'date' => $result['date'] ?? ''];
            case 5: // Отклонение
                return ['status' => 'rejected', 'result_id' => 5, 'date' => $result['date'] ?? ''];
            case 6: // Возврат на доработку
                return ['status' => 'returned', 'result_id' => 6, 'date' => $result['date'] ?? ''];
            default:
                return ['status' => 'pending', 'result_id' => 0];
        }
    };

    // Рекурсивная функция для поиска пользователя ВЕЗДЕ, включая перенаправления
    $findUserInSection = function($section, $userId, $level = 0) use (&$findUserInSection) {
        if (!is_array($section) || empty($section)) {
            return null;
        }

        $sectionInfo = $section[0] ?? [];
        $approvers = array_slice($section, 1);

        foreach ($approvers as $index => $approver) {
            // Проверяем самого сотрудника
            if (($approver['id'] ?? null) == $userId) {
                return [
                    'approver' => $approver,
                    'section_info' => $sectionInfo,
                    'list_type' => isset($sectionInfo['list_type']) ? intval($sectionInfo['list_type']) : 1,
                    'stage' => $sectionInfo['stage'] ?? '',
                    'index' => $index,
                    'level' => $level,
                    'is_redirect' => false,
                    'section' => $section
                ];
            }

            // Проверяем перенаправления этого сотрудника
            if (isset($approver['redirect']) && is_array($approver['redirect'])) {
                $found = $findUserInSection($approver['redirect'], $userId, $level + 1);
                if ($found) {
                    $found['parent_approver'] = $approver;
                    $found['is_redirect'] = true;
                    return $found;
                }
            }
        }

        return null;
    };

    // Рекурсивная функция для поиска пользователя во ВСЕХ перенаправлениях, даже если он не в основном списке
    $findUserInAllRedirects = function($section, $userId, $level = 0) use (&$findUserInAllRedirects, &$findUserInSection) {
        if (!is_array($section) || empty($section)) {
            return null;
        }

        $sectionInfo = $section[0] ?? [];
        $approvers = array_slice($section, 1);

        foreach ($approvers as $index => $approver) {
            // Проверяем перенаправления этого сотрудника
            if (isset($approver['redirect']) && is_array($approver['redirect'])) {
                // Ищем в самом массиве перенаправления
                $redirectSection = $approver['redirect'];
                $redirectStartIndex = isset($redirectSection[0]['stage']) ? 1 : 0;

                for ($r = $redirectStartIndex; $r < count($redirectSection); $r++) {
                    if (!isset($redirectSection[$r]['id'])) continue;

                    if ($redirectSection[$r]['id'] == $userId) {
                        return [
                            'approver' => $redirectSection[$r],
                            'section_info' => $sectionInfo,
                            'list_type' => isset($sectionInfo['list_type']) ? intval($sectionInfo['list_type']) : 1,
                            'stage' => $sectionInfo['stage'] ?? '',
                            'index' => $r,
                            'level' => $level + 1,
                            'is_redirect' => true,
                            'parent_approver' => $approver,
                            'section' => $redirectSection
                        ];
                    }
                }

                // Рекурсивно проверяем вложенные перенаправления
                $found = $findUserInAllRedirects($approver['redirect'], $userId, $level + 1);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    };

    // Рекурсивная проверка статуса всех участников
    $checkAllApproversStatus = function($section) use (&$checkAllApproversStatus, $getApproverStatus) {
        $results = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'redirected' => 0,
            'rejected' => 0,
            'returned' => 0
        ];

        if (!is_array($section)) return $results;

        $approvers = array_slice($section, 1);

        foreach ($approvers as $approver) {
            $results['total']++;
            $status = $getApproverStatus($approver);
            $results[$status['status']]++;

            // Проверяем перенаправления
            if (isset($approver['redirect']) && is_array($approver['redirect'])) {
                $redirectResults = $checkAllApproversStatus($approver['redirect']);
                foreach ($redirectResults as $key => $value) {
                    $results[$key] += $value;
                }
            }
        }

        return $results;
    };

    // Собираем статистику по всем секциям
    $globalStats = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'redirected' => 0,
        'rejected' => 0
    ];

    foreach ($data as $section) {
        $sectionStats = $checkAllApproversStatus($section);
        foreach ($sectionStats as $key => $value) {
            $globalStats[$key] += $value;
        }
    }

    // Если вообще нет участников согласования
    if ($globalStats['total'] == 0) {
        return [
            'icon_class' => 'radio_button_unchecked',
            'color' => 'var(--color_02)',
            'title' => 'Нет участников согласования',
            'status_text' => 'Пустое согласование',
            'can_approve' => false,
            'status_type' => 'no_approvers'
        ];
    }

    // 1. Есть отклонения - документ отклонён
    if ($globalStats['rejected'] > 0) {
        return [
            'icon_class' => 'back_hand',
            'color' => 'var(--red)',
            'title' => 'Отклонён',
            'status_text' => 'Документ отклонён',
            'can_approve' => false,
            'status_type' => 'document_rejected'
        ];
    }

    // DEBUG для документа 181
    if ($docId == 181) {
        echo "<!-- DEBUG START Doc 181, User $userId, DocStatus=$documentStatus -->\n";
    }

    // Ищем пользователя во всём дереве согласования.
    // Приоритет: pending > redirected > approved — чтобы не скрывать активную очередь
    // за уже выполненным действием в другой секции.
    $userFound = false;
    $userInfo  = null;
    $allFound  = []; // все вхождения пользователя

    foreach ($data as $section) {
        // Ищем ВСЕ вхождения пользователя в секции, включая повторные записи
        $sectionInfo = $section[0] ?? [];
        $approvers = array_slice($section, 1);

        foreach ($approvers as $index => $approver) {
            if (($approver['id'] ?? null) == $userId) {
                $allFound[] = [
                    'approver' => $approver,
                    'section_info' => $sectionInfo,
                    'list_type' => isset($sectionInfo['list_type']) ? intval($sectionInfo['list_type']) : 1,
                    'stage' => $sectionInfo['stage'] ?? '',
                    'index' => $index,
                    'level' => 0,
                    'is_redirect' => false,
                    'section' => $section
                ];
                if ($docId == 181) {
                    echo "<!-- DEBUG: Added main level entry, result.id=" . ($approver['result']['id'] ?? 'NULL') . " -->\n";
                }
            }
        }

        // Также ищем в redirect
        $foundInRedirect = $findUserInAllRedirects($section, $userId);
        if ($foundInRedirect) {
            $allFound[] = $foundInRedirect;
            if ($docId == 181) {
                echo "<!-- DEBUG: Added redirect entry, result.id=" . ($foundInRedirect['approver']['result']['id'] ?? 'NULL') . ", level=" . ($foundInRedirect['level'] ?? '?') . " -->\n";
            }
        }
    }

    if (!empty($allFound)) {
        $userFound = true;
        // Выбираем запись с наивысшим приоритетом статуса.
        // Исключение: redirected с незавершённой цепочкой важнее pending —
        // пользователь ещё ждёт завершения перенаправления.
        $isRedirectChainDone = function(array $approver) use ($getApproverStatus): bool {
            if (empty($approver['redirect']) || !is_array($approver['redirect'])) return true;
            foreach ($approver['redirect'] as $rd) {
                if (!isset($rd['id'])) continue;
                $st = $getApproverStatus($rd)['status'];
                if ($st !== 'approved' && $st !== 'rejected') return false;
            }
            return true;
        };

        // Функция для проверки, есть ли незавершенный redirect у записи
        $hasUnfinishedRedirect = function($approver) use ($getApproverStatus) {
            if (empty($approver['redirect']) || !is_array($approver['redirect'])) {
                return false;
            }
            foreach ($approver['redirect'] as $rd) {
                if (!isset($rd['id'])) continue;
                // Передаем true для $isInsideRedirect
                $st = $getApproverStatus($rd, null, true);
                if ($st['status'] !== 'approved' && $st['status'] !== 'rejected') {
                    return true; // Есть незавершенная запись
                }
            }
            return false; // Все завершены
        };

        // Проверяем, есть ли у пользователя ЛЮБАЯ запись с незавершённым redirect
        $userHasAnyUnfinishedRedirect = false;
        foreach ($allFound as $entry) {
            if ($hasUnfinishedRedirect($entry['approver'])) {
                $userHasAnyUnfinishedRedirect = true;
                break;
            }
        }

        $priority = ['pending' => 0, 'returned' => 0, 'redirected' => 1, 'approved' => 2, 'rejected' => 3];
        usort($allFound, function($a, $b) use ($getApproverStatus, $priority, $isRedirectChainDone, $hasUnfinishedRedirect, $userHasAnyUnfinishedRedirect) {
            // ВАЖНО: Если у записи есть незавершённый redirect, считаем её статус как 'redirected'
            $aHasUnfinished = $hasUnfinishedRedirect($a['approver']);
            $bHasUnfinished = $hasUnfinishedRedirect($b['approver']);

            $sa = $aHasUnfinished ? 'redirected' : $getApproverStatus($a['approver'], null, !empty($a['is_redirect']))['status'];
            $sb = $bHasUnfinished ? 'redirected' : $getApproverStatus($b['approver'], null, !empty($b['is_redirect']))['status'];

            // ПРАВИЛО: _is_redirector_repeat БЕЗ незавершенного redirect имеет наивысший приоритет
            // НО: Если у пользователя где-то есть незавершённый redirect, repeat НЕ должен получать приоритет
            $aIsRepeat = !empty($a['approver']['_is_redirector_repeat']);
            $bIsRepeat = !empty($b['approver']['_is_redirector_repeat']);

            // Только если у repeat НЕТ незавершённого redirect И у пользователя НИГДЕ нет незавершённых redirects
            if ($aIsRepeat && !$aHasUnfinished && !$userHasAnyUnfinishedRedirect && !$bIsRepeat) return -1;
            if ($bIsRepeat && !$bHasUnfinished && !$userHasAnyUnfinishedRedirect && !$aIsRepeat) return 1;

            // ПРАВИЛО: Секция подписантов (stage='') имеет приоритет над этапами согласования
            // Это важно, когда пользователь участвует и в подписантах, и в согласовании
            $aStage = $a['section_info']['stage'] ?? null;
            $bStage = $b['section_info']['stage'] ?? null;
            $aIsSigners = ($aStage === '');
            $bIsSigners = ($bStage === '');

            // Если один из них - секция подписантов, а другой - этап, приоритет у подписантов
            if ($aIsSigners && !$bIsSigners) return -1; // секция подписантов важнее
            if (!$aIsSigners && $bIsSigners) return 1;  // секция подписантов важнее

            // ПРАВИЛО: Если один pending, другой approved - pending ВСЕГДА имеет приоритет
            // (даже если approved в секции подписантов)
            if ($sa === 'pending' && $sb === 'approved') return -1;
            if ($sa === 'approved' && $sb === 'pending') return 1;

            // redirected с незавершённой цепочкой получает наивысший приоритет (выше pending)
            $pa = ($sa === 'redirected' && !$isRedirectChainDone($a['approver'])) ? -1 : ($priority[$sa] ?? 9);
            $pb = ($sb === 'redirected' && !$isRedirectChainDone($b['approver'])) ? -1 : ($priority[$sb] ?? 9);
            // Среди pending: запись в redirect[] имеет приоритет над оригинальной
            // (пользователь получил перенаправление — его очередь действовать)
            if ($pa === $pb && $sa === 'pending' && $sb === 'pending') {
                $aIsRedirect = !empty($a['is_redirect']);
                $bIsRedirect = !empty($b['is_redirect']);
                if ($aIsRedirect && !$bIsRedirect) return -1;
                if (!$aIsRedirect && $bIsRedirect) return 1;
            }
            return $pa <=> $pb;
        });
        $userInfo = $allFound[0];

        // Временная отладка для документа 181
        if ($docId == 181) {
            echo "<!-- DEBUG Doc 181, Current User ID: $userId\n";
            echo "Total found entries: " . count($allFound) . "\n";
            foreach ($allFound as $idx => $entry) {
                $hasUnfin = $hasUnfinishedRedirect($entry['approver']) ? 'YES' : 'NO';
                $rawStatus = $getApproverStatus($entry['approver'], null, !empty($entry['is_redirect']))['status'];
                $effectiveStatus = $hasUnfin == 'YES' ? 'redirected' : $rawStatus;
                $isRepeat = !empty($entry['approver']['_is_redirector_repeat']) ? 'YES' : 'NO';
                $hasRedirect = !empty($entry['approver']['redirect']) ? 'YES' : 'NO';
                $resultId = isset($entry['approver']['result']['id']) ? $entry['approver']['result']['id'] : 'NULL';
                echo "Entry $idx: result.id=$resultId, raw_status=$rawStatus, effective_status=$effectiveStatus, _is_redirector_repeat=$isRepeat, has_redirect=$hasRedirect, has_unfinished=$hasUnfin, is_redirect=" . (!empty($entry['is_redirect']) ? 'YES' : 'NO') . ", level=" . ($entry['level'] ?? 0) . "\n";
            }
            $selectedHasUnfin = $hasUnfinishedRedirect($userInfo['approver']) ? 'YES' : 'NO';
            $selectedRawStatus = $getApproverStatus($userInfo['approver'], null, !empty($userInfo['is_redirect']))['status'];
            $selectedEffectiveStatus = $selectedHasUnfin == 'YES' ? 'redirected' : $selectedRawStatus;
            $selectedResultId = isset($userInfo['approver']['result']['id']) ? $userInfo['approver']['result']['id'] : 'NULL';
            echo "Selected entry: result.id=$selectedResultId, raw_status=$selectedRawStatus, effective_status=$selectedEffectiveStatus, has_unfinished=$selectedHasUnfin\n";
            echo "-->\n";
        }
    }

    // Если пользователь найден в списке согласования
    if ($userFound) {
        // Передаем флаг is_redirect, чтобы не проверять user_signs для записей внутри redirect
        $isInsideRedirect = !empty($userInfo['is_redirect']);
        $userStatus = $getApproverStatus($userInfo['approver'], null, $isInsideRedirect);

        // DEBUG для документа 181
        if ($docId == 181) {
            echo "<!-- DEBUG BEFORE SWITCH: userStatus['status']=" . $userStatus['status'] . ", isInsideRedirect=" . ($isInsideRedirect ? 'YES' : 'NO') . " -->\n";
        }

        switch ($userStatus['status']) {
            case 'approved':
                // Пользователь уже согласовал/подписал
                // Проверяем result.id для точного определения действия
                $resultId = $userStatus['result_id'] ?? 0;

                if ($resultId == 1) {
                    // result.id=1 - подписано с ЭП
                    $title = 'Вы подписали';
                } elseif ($resultId == 2 || $resultId == 3) {
                    // result.id=2 - согласовано с ЭП, result.id=3 - согласовано без ЭП
                    $title = 'Вы согласовали';
                } else {
                    // Фоллбэк: проверяем role (для секции подписантов) и type (для секций согласования)
                    $role = intval($userInfo['approver']['role'] ?? -1);
                    $type = intval($userInfo['approver']['type'] ?? -1);

                    if ($role == 1) {
                        $title = 'Вы подписали';
                    } elseif ($role == 0) {
                        $title = 'Вы согласовали';
                    } elseif ($type == 1) {
                        $title = 'Вы подписали';
                    } else {
                        $title = 'Вы согласовали';
                    }
                }

                if (!empty($userInfo['stage'])) {
                    $title .= " (этап {$userInfo['stage']})";
                }

                // Проверяем, согласовали ли все остальные
                if ($globalStats['pending'] == 0 && $globalStats['rejected'] == 0) {
                    // Все согласовали - документ завершён
                    return [
                        'icon_class' => 'task_alt',
                        'color' => 'var(--green)',
                        'title' => 'Документ согласован (вы участвовали)',
                        'status_text' => 'Согласован',
                        'can_approve' => false,
                        'status_type' => 'document_approved_user_participated'
                    ];
                } else {
                    // Ещё есть ожидающие - пользователь уже согласовал
                    return [
                        'icon_class' => 'hourglass_top',
                        'color' => 'var(--color_02)', // Серый, не синий - уже согласовал
                        'title' => $title,
                        'status_text' => 'На согласовании',
                        'can_approve' => false,
                        'status_type' => 'user_approved'
                    ];
                }

            case 'rejected':
                // Пользователь отклонил
                return [
                    'icon_class' => 'back_hand',
                    'color' => 'var(--red)',
                    'title' => 'Вы отклонили',
                    'status_text' => 'Отклонён',
                    'can_approve' => false,
                    'status_type' => 'user_rejected',
                    'stage' => $userInfo['stage'] ?? ''
                ];

            case 'redirected':
                // DEBUG для документа 181
                if ($docId == 181) {
                    echo "<!-- DEBUG IN CASE REDIRECTED -->\n";
                }

                // Пользователь перенаправил
                // Проверяем, завершено ли перенаправление РЕКУРСИВНО по всей цепочке
                $isRedirectCompletedRecursive = function($redirectArr) use (&$isRedirectCompletedRecursive, $getApproverStatus) {
                    if (!is_array($redirectArr)) return true;

                    foreach ($redirectArr as $rd) {
                        if (!isset($rd['id'])) continue;

                        $rdStatus = $getApproverStatus($rd, null, true);

                        // Если pending или rejected - цепочка не завершена
                        if ($rdStatus['status'] === 'pending' || $rdStatus['status'] === 'rejected') {
                            return false;
                        }

                        // Если redirected - проверяем вложенный redirect рекурсивно
                        if ($rdStatus['status'] === 'redirected') {
                            if (isset($rd['redirect']) && is_array($rd['redirect'])) {
                                if (!$isRedirectCompletedRecursive($rd['redirect'])) {
                                    return false;
                                }
                            } else {
                                // redirected но нет вложенного redirect - не завершено
                                return false;
                            }
                        }

                        // approved - продолжаем проверку
                    }

                    return true;
                };

                $redirectCompleted = false;
                if (isset($userInfo['approver']['redirect']) && is_array($userInfo['approver']['redirect'])) {
                    $redirectCompleted = $isRedirectCompletedRecursive($userInfo['approver']['redirect']);
                }

                if ($redirectCompleted) {
                    // Перенаправление завершено
                    // ВАЖНО: Проверяем, не завершил ли redirect САМ ПОЛЬЗОВАТЕЛЬ
                    // Если да - показываем "Вы согласовали", а не "Требуется подпись"
                    $userApprovedInRedirect = false;

                    $checkUserApprovedInChain = function($redirectArr, $userId) use (&$checkUserApprovedInChain, $getApproverStatus) {
                        foreach ($redirectArr as $rd) {
                            if (!isset($rd['id'])) continue;

                            if (intval($rd['id']) === $userId) {
                                $rdStatus = $getApproverStatus($rd, null, true);
                                if ($rdStatus['status'] === 'approved') {
                                    return true;
                                }
                            }

                            // Рекурсивно проверяем вложенные redirect
                            if (isset($rd['redirect']) && is_array($rd['redirect'])) {
                                if ($checkUserApprovedInChain($rd['redirect'], $userId)) {
                                    return true;
                                }
                            }
                        }
                        return false;
                    };

                    if (isset($userInfo['approver']['redirect']) && is_array($userInfo['approver']['redirect'])) {
                        $userApprovedInRedirect = $checkUserApprovedInChain($userInfo['approver']['redirect'], $userId);
                    }

                    if ($userApprovedInRedirect) {
                        // Пользователь уже согласовал в цепочке redirect
                        // Показываем статус "Вы согласовали"
                        if ($documentStatus == 0) {
                            // Документ ещё на согласовании (есть другие ожидающие)
                            $title = 'Вы согласовали (ожидание других)';
                            return [
                                'icon_class' => 'hourglass_top',
                                'color' => 'var(--color_02)',
                                'title' => $title,
                                'status_text' => 'На согласовании',
                                'can_approve' => false,
                                'status_type' => 'user_approved'
                            ];
                        } else {
                            // Документ полностью согласован
                            return [
                                'icon_class' => 'task_alt',
                                'color' => 'var(--green)',
                                'title' => 'Документ согласован (вы участвовали)',
                                'status_text' => 'Согласован',
                                'can_approve' => false,
                                'status_type' => 'document_approved_user_participated'
                            ];
                        }
                    }

                    // Пользователь НЕ согласовал в redirect - требуется его действие
                    // Проверяем, может ли он действовать сейчас
                    $canActAfterRedirect = true;

                    // Для последовательного согласования проверяем предыдущих
                    if ($userInfo['list_type'] == 1 && $userInfo['index'] > 0) {
                        $approvers = array_slice($userInfo['section'], 1);
                        for ($i = 0; $i < $userInfo['index']; $i++) {
                            $prevStatus = $getApproverStatus($approvers[$i]);
                            if ($prevStatus['status'] !== 'approved' && $prevStatus['status'] !== 'redirected') {
                                $canActAfterRedirect = false;
                                break;
                            }
                        }
                    }

                    if ($canActAfterRedirect) {
                        // Может действовать после перенаправления
                        $type = intval($userInfo['approver']['type'] ?? 1);
                        $title = $type == 1 ? 'Требуется ваша подпись' : 'Требуется ваше согласование';

                        if (!empty($userInfo['stage'])) {
                            $title .= " (этап {$userInfo['stage']})";
                        }

                        return [
                            'icon_class' => 'radio_button_unchecked',
                            'color' => 'var(--blue)', // СИНИЙ - требуется действие после перенаправления
                            'title' => $title . ' (после перенаправления)',
                            'can_approve' => true,
                            'status_type' => 'requires_action_after_redirect',
                            'status_text' => 'На согласовании',
                            'stage' => $userInfo['stage'] ?? '',
                            'urgent' => $userInfo['section_info']['urgent'] ?? '',
                            'list_type' => $userInfo['list_type'],
                            'is_redirect' => $userInfo['is_redirect'] ?? false
                        ];
                    } else {
                        // Должен ждать других
                        return [
                            'icon_class' => 'forward',
                            'color' => 'var(--color_02)',
                            'title' => 'Вы перенаправили (ожидание других)',
                            'can_approve' => false,
                            'status_type' => 'user_redirected_waiting',
                            'status_text' => 'На согласовании',
                            'stage' => $userInfo['stage'] ?? ''
                        ];
                    }
                } else {
                    // Перенаправление ещё не завершено
                    // ВАЖНО: Проверяем, есть ли в цепочке redirect запись текущего пользователя со статусом pending
                    // Если да - показываем "Требуется действие", а не "Вы перенаправили"
                    $userPendingInRedirect = false;

                    $findUserInRedirectChain = function($redirectArr, $userId) use (&$findUserInRedirectChain, $getApproverStatus) {
                        foreach ($redirectArr as $rd) {
                            if (!isset($rd['id'])) continue;

                            if (intval($rd['id']) === $userId) {
                                $rdStatus = $getApproverStatus($rd, null, true);
                                if ($rdStatus['status'] === 'pending') {
                                    return true;
                                }
                            }

                            // Рекурсивно проверяем вложенные redirect
                            if (isset($rd['redirect']) && is_array($rd['redirect'])) {
                                if ($findUserInRedirectChain($rd['redirect'], $userId)) {
                                    return true;
                                }
                            }
                        }
                        return false;
                    };

                    if (isset($userInfo['approver']['redirect']) && is_array($userInfo['approver']['redirect'])) {
                        $userPendingInRedirect = $findUserInRedirectChain($userInfo['approver']['redirect'], $userId);
                    }

                    if ($userPendingInRedirect) {
                        // Пользователь ожидает в цепочке redirect - требуется его действие
                        $type = intval($userInfo['approver']['type'] ?? 1);
                        $title = $type == 1 ? 'Требуется ваша подпись' : 'Требуется ваше согласование';

                        if (!empty($userInfo['stage'])) {
                            $title .= " (этап {$userInfo['stage']})";
                        }

                        return [
                            'icon_class' => 'radio_button_unchecked',
                            'color' => 'var(--blue)', // СИНИЙ - требуется действие
                            'title' => $title,
                            'can_approve' => true,
                            'status_type' => 'requires_action',
                            'status_text' => $type == 1 ? 'Требуется подпись' : 'На согласовании',
                            'stage' => $userInfo['stage'] ?? '',
                            'urgent' => $userInfo['section_info']['urgent'] ?? '',
                            'list_type' => $userInfo['list_type'],
                            'is_redirect' => true
                        ];
                    } else {
                        // Перенаправление активно, но пользователь не ожидает в цепочке
                        return [
                            'icon_class' => 'forward',
                            'color' => 'var(--color_02)',
                            'title' => 'Вы перенаправили',
                            'can_approve' => false,
                            'status_type' => 'user_redirected',
                            'status_text' => 'На согласовании',
                            'stage' => $userInfo['stage'] ?? ''
                        ];
                    }
                }

            case 'pending':
                // DEBUG для документа 181
                if ($docId == 181) {
                    echo "<!-- DEBUG IN CASE PENDING -->\n";
                }

                // Пользователь ожидает согласования
                // Проверяем, может ли пользователь действовать сейчас
                $canAct = true;

                // Для последовательного согласования проверяем предыдущих
                if ($userInfo['list_type'] == 1 && $userInfo['index'] > 0) {
                    $approvers = array_slice($userInfo['section'], 1);
                    $currentRole = intval($userInfo['approver']['role'] ?? 0);
                    for ($i = 0; $i < $userInfo['index']; $i++) {
                        if (!isset($approvers[$i]['id'])) continue;
                        // Пропускаем role=0 (утверждающего) стоящего перед role=1 (подписывающим)
                        // Это некорректный порядок — подписывающий не должен ждать утверждающего
                        if ($currentRole === 1 && intval($approvers[$i]['role'] ?? 0) === 0) continue;
                        $prevStatus = $getApproverStatus($approvers[$i]);
                        if ($prevStatus['status'] !== 'approved' && $prevStatus['status'] !== 'redirected') {
                            $canAct = false;
                            break;
                        }
                    }
                }

                // Для параллельного согласования проверяем незавершённые redirect у предыдущих
                if ($canAct && $userInfo['list_type'] == 2 && $userInfo['index'] > 0) {
                    $approvers = array_slice($userInfo['section'], 1);
                    for ($i = 0; $i < $userInfo['index']; $i++) {
                        if (!isset($approvers[$i]['id'])) continue;
                        if (!empty($approvers[$i]['_is_redirector_repeat'])) continue;
                        $prevStatus = $getApproverStatus($approvers[$i]);
                        // pending или redirected с незавершённой цепочкой — ждём
                        if (($prevStatus['status'] === 'redirected' || $prevStatus['status'] === 'pending')
                            && !empty($approvers[$i]['redirect'])
                            && !$isRedirectChainDone($approvers[$i])) {
                            $canAct = false;
                            break;
                        }
                    }
                }

                // Для этапов: если есть номер этапа, проверяем предыдущие этапы
                if ($canAct && !empty($userInfo['stage']) && $userInfo['stage'] !== '') {
                    $currentStage = intval($userInfo['stage']);

                    // Проходим по всем секциям
                    foreach ($data as $section) {
                        $sectionInfo = $section[0] ?? [];
                        $sectionStage = isset($sectionInfo['stage']) && $sectionInfo['stage'] !== ''
                            ? intval($sectionInfo['stage'])
                            : 999;

                        // Пропускаем текущий и последующие этапы
                        if ($sectionStage >= $currentStage) {
                            continue;
                        }

                        // Проверяем завершённость предыдущего этапа
                        $sectionApprovers = array_slice($section, 1);
                        foreach ($sectionApprovers as $approver) {
                            $status = $getApproverStatus($approver);
                            if ($status['status'] !== 'approved' && $status['status'] !== 'redirected') {
                                $canAct = false;
                                break 2;
                            }
                        }
                    }
                }

                if ($canAct) {
                    // МОЖЕТ ДЕЙСТВОВАТЬ СЕЙЧАС - синий цвет
                    $type = intval($userInfo['approver']['type'] ?? 1);
                    $title = $type == 1 ? 'Требуется ваша подпись' : 'Требуется ваше согласование';

                    if (!empty($userInfo['stage'])) {
                        $title .= " (этап {$userInfo['stage']})";
                    }

                    return [
                        'icon_class' => 'radio_button_unchecked',
                        'color' => 'var(--blue)', // СИНИЙ - требуется действие сейчас
                        'title' => $title,
                        'can_approve' => true,
                        'status_type' => 'requires_action',
                        'stage' => $userInfo['stage'] ?? '',
                        'urgent' => $userInfo['section_info']['urgent'] ?? '',
                        'list_type' => $userInfo['list_type'],
                        'status_text' => 'На согласовании',
                        'is_redirect' => $userInfo['is_redirect'] ?? false
                    ];
                } else {
                    // ДОЛЖЕН ЖДАТЬ ДРУГИХ - серый цвет
                    $title = 'Ожидание других согласующих';
                    if (!empty($userInfo['stage'])) {
                        $title .= " (этап {$userInfo['stage']})";
                    }

                    return [
                        'icon_class' => 'hourglass_top',
                        'color' => 'var(--color_02)', // СЕРЫЙ - должен ждать
                        'title' => $title,
                        'can_approve' => false,
                        'status_type' => 'waiting_others',
                        'status_text' => 'На согласовании',
                        'stage' => $userInfo['stage'] ?? ''
                    ];
                }
        }
    }

    // Пользователь НЕ найден в списке согласования
    // Проверяем общий статус документа

    // 2. Есть ожидающие - документ на согласовании
    if ($globalStats['pending'] > 0) {
        return [
            'icon_class' => 'radio_button_unchecked',
            'color' => 'var(--color_02)',
            'title' => 'Вы не участвуете в согласовании',
            'can_approve' => false,
            'status_type' => 'not_involved',
            'status_text' => 'На согласовании'
        ];
    }

    // 3. Все согласовали (нет pending и rejected) - документ согласован
    if ($globalStats['approved'] > 0 && $globalStats['pending'] == 0 && $globalStats['rejected'] == 0) {
        return [
            'icon_class' => 'task_alt',
            'color' => 'var(--green)',
            'title' => 'Документ согласован (вы не участвовали)',
            'can_approve' => false,
            'status_type' => 'document_approved_user_not_involved',
            'status_text' => 'Согласован'
        ];
    }

    // 4. Только перенаправления (редкий случай)
    if ($globalStats['redirected'] > 0 && $globalStats['pending'] == 0 && $globalStats['rejected'] == 0) {
        return [
            'icon_class' => 'forward',
            'color' => 'var(--color_02)',
            'title' => 'Документ в процессе перенаправлений',
            'can_approve' => false,
            'status_type' => 'redirects_only',
            'status_text' => 'На согласовании'
        ];
    }

    // Запасной вариант
    return [
        'icon_class' => 'radio_button_unchecked',
        'color' => 'var(--color_02)',
        'title' => 'Неопределённый статус согласования',
        'can_approve' => false,
        'status_type' => 'unknown',
        'status_text' => 'Не известно',
    ];
}





$regId = 66;

$gui = new Gui;
$db = new Db;
$auth = new Auth();

$table = $db->selectOne('registry', ' where id = ?', [$regId]);
$parent_item = $db->selectOne('documents', 'where parent=' . $regId . ' LIMIT 1');
$parents = $db->getRegistry('registry');
$documentacial = $db->getRegistry('documentdocuments');
$items = $db->getRegistry($table->table_name);

$subQuery = '';

$gui->set('module_id', 17);

$regs = $gui->getTableData($table->table_name);
?>
<style>
    .tab-pane{
        z-index: 2;
    }
    #button_nav_create{
        display: none;
    }
</style>
<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
            'title'        => 'Документы',
            'renew'        => 'Сбросить все фильтры',
            'create'       => 'Создать приказ',
            'archive'      => 'Переместить в архив',
            'filter_panel' => 'Открыть панель фильтров',
            'logout'       => 'Выйти'
        ]);
        ?>

        <? /*div class="button icon text" title="Журнал работ">
			<span class="material-icons">fact_check</span>Журнал работ
		</div*/ ?>
    </div>

</div>
<div class="scroll_wrap">
    <form method="post" id="registry_items_delete" class="ajaxFrm">
        <input type="hidden" name="registry_id" id="registry_id" value="<?= $regId ?>">
    </form>
    <form method="post" id="registry_items_archive" class="ajaxFrm">
        <input type="hidden" name="registry_id" value="<?= $regId ?>">
    </form>
    <form method="post" id="registry_items_restore" class="ajaxFrm">
        <input type="hidden" name="registry_id" value="<?= $regId ?>">
    </form>
    <form method="post" id="registry_items_delete_real" class="ajaxFrm">
        <input type="hidden" name="registry_id" value="<?= $regId ?>">
        <ul class='tab-pane'>
            <?php
            foreach ($documentacial['array'] as $index => $name){
                if($index != 6) {
                    $class = $_COOKIE['document_active_pane'] == $index ? ' class="active"' : '';
                    echo '<li id="tab_' . $index . '"' . $class . '>' . $name . '</li>' . "\n";
                }
            }
            ?>
        </ul>
        <table class="table_data" id="tbl_registry_items">
            <thead>
            <tr class="fixed_thead">
                <th>
                    <div class="custom_checkbox">
                        <label class="container" title="Выделить все">
                            <input type="checkbox" id="check_all"><span class="checkmark"></span>
                        </label>
                    </div>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'documents',
                        '№',
                        'id',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'documents',
                        'Статус',
                        'active',
                        'constant',
                        ['1' => 'Активный', '0' => 'Заблокирован']
                    );
                    ?>
                </th>
                <th class="sort">
                    <?
                    echo $gui->buildSortFilter(
                        'documents',
                        'Наименование',
                        'name',
                        'el_data',
                        []
                    );
                    ?>
                </th>
                <th class='sort'>
                    <?
                    echo $gui->buildSortFilter(
                        'documents',
                        'Тип документа',
                        'documentacial',
                        'constant',
                        $documentacial['array']
                    );
                    ?>
                </th>
                <th>
                    <div class="head_sort_filter">Примечания</div>
                </th>
            </tr>
            </thead>


            <tbody>
            <!-- row -->
            <?
            //Выводим документы
            $tab = 10;

            foreach ($regs as $reg) {
                if ($regId == 14 && ($auth->haveUserRole(3) || $auth->haveUserRole(1))) {
                    $reg = (object)$reg;
                }
                $itemArr = explode(',', $reg->parent_items);
                $itemList = [];
                $itemStr = '';
                $aCount = $reg->ext_answers;
                $allowEdit = false;
                $edit_plan_id = 0;
                $planUid = null;
                $edit_ins = '';
                $tab++;
                foreach ($itemArr as $i) {
                    $itemList[] = $items['array'][$i];
                }
                if (count($itemList) > 0 && strlen($itemList[0]) > 0) {
                    $itemStr = ' - ' . implode(', ', $itemList);
                }
                $style = '';
                $title = '';
                $agrStatus = getDocumentStatusForIcon($_SESSION['user_id'],
                    $reg->agreementlist, $reg->status, $reg->id);

                // Временная отладка для документа 181
                if ($reg->id == 181) {
                    echo "<!-- DOC 181 RENDER: User=" . $_SESSION['user_id'] . ", status_type=" . $agrStatus['status_type'] . ", status_text=" . $agrStatus['status_text'] . " -->\n";
                }

                // Проверяем количество подписантов
                $signersValidation = validateSignersCount($reg->agreementlist, intval($reg->documentacial));

                // ВАЖНО: Если документ отклонён - показываем статус отклонения, даже если валидация не прошла
                if ($agrStatus['status_type'] == 'user_rejected' || $agrStatus['status_type'] == 'document_rejected') {
                    $icon = $agrStatus['icon_class'];
                    $statusText = $agrStatus['status_text'];
                    $class = 'redText';
                    $style = ' style="color:var(--red); display: inline;vertical-align: bottom;"';
                    $title = ' title="' . $agrStatus['title'] . '"';
                } elseif (!$signersValidation['valid']) {
                    // Если валидация не прошла - показываем предупреждение
                    $icon = 'warning';
                    $statusText = $signersValidation['error'];
                    $class = 'orange';
                    $style = ' style="color:#ff9800; display: inline;vertical-align: bottom;"'; // Оранжевый цвет для иконки
                    $title = ' title="' . ($signersValidation['error_full'] ?? $signersValidation['error']) . '"';
                } elseif($reg->status == 1 || $agrStatus['icon_class'] == 'task_alt'){
                    // Валидация прошла и документ согласован
                    $icon = 'task_alt';
                    $statusText = 'Согласован';
                    $class = 'green';
                    $style = '';
                }else{
                    // Валидация прошла, документ в процессе согласования
                    switch($agrStatus['status_type']){
                        case '':
                            break;
                    }

                    $class = $agrStatus['color'] == 'var(--color_02)' ? 'grey' : 'blue';
                    if($agrStatus['status_type'] == 'user_rejected' || $agrStatus['status_type'] == 'document_rejected'){
                        $class = 'redText';
                    }
                    if(strlen($reg->agreementlist) > 0) {

                        $title = ' title="' . $agrStatus['title'] . '"';
                        $icon = $agrStatus['icon_class'];
                        if ($agrStatus['status_type'] != 'not_involved') {
                            $style = ' style="color:' . $agrStatus['color'] . '"';
                        }
                    }
                    $statusText = $agrStatus['status_text'] ?? '';
                }

                if($reg->status != 1 && $reg->approved != 1 && ( $_SESSION['user_id'] == $reg->author || $auth->isAdmin() ) ){
                    $allowEdit = true;
                }

                switch(intval($reg->documentacial)){
                    case 1: //Приказ
                        if (intval($reg->source_id) > 0) {
                            //$planUid = $db->selectOne('checkinstitutions', ' WHERE id = ? LIMIT 1', [$reg->source_id]);
                            //$row_plan = $db->selectOne('checksplans', ' WHERE uid = ? ORDER BY version DESC LIMIT 1', [$planUid->plan_uid]);
                            $edit_plan_id = $reg->plan_id;
                            $edit_ins = $reg->ins_id;
                        }
                        break;
                    case 3: //План
                        $edit_doc_id = $reg->source_id;
                        $row_plan = $db->selectOne('checksplans', ' WHERE id = ?', [$edit_doc_id]);
                        $edit_plan_id = $row_plan->id;
                        break;
                }

                echo '<tr data-id="' . $reg->id . '" data-parent="' . $regId . '" tabindex="0" class="noclick">'.
                    (($allowEdit) ? '
                    <td>
                        <div class="custom_checkbox">
                            <label class="container"><input type="checkbox" name="reg_id[]" tabindex="-1" value="' . $reg->id . '">
                            <span class="checkmark"></span></label>
                        </div>
                    </td>' : '<td>&nbsp;</td>').'
                    <td>' . $reg->id . '</td>
                    <td class="status '.$class.'"'.$title.'><span class="material-icons '.$class.'"'.$style.'>' . $icon . '</span> '.$statusText.'</td>
                    <td class="group">' . stripslashes($reg->name) . '</td>
                    <td>'.$documentacial['array'][$reg->documentacial].'</td>
                    <td>' . $reg->comment . '</td>
                    <td class="link" style="justify-content: end;">'.
                    (($allowEdit) ? '
                        <span class="material-icons doc_edit" data-plan="'.$edit_plan_id.'" data-ins="'.$edit_ins.'" 
                        data-id="'.$reg->id.'" data-doctype="'.$reg->documentacial.'" title="Редактирование документа">edit</span>' : '').'
                        <span class="material-icons agreementDoc" data-id="'.$reg->id.'" title="Согласование документа">verified</span>
                        <span class="material-icons viewDoc" data-id="'.$reg->id.'" title="Просмотр документа">picture_as_pdf</span>
                    </td>
                </tr>';
            }
            ?>
            </tbody>
        </table>
    </form><!-- /registry_items_delete_real -->
    <?
    echo $gui->paging();
    ?>
</div>
<script src='/js/assets/agreement_list.js'></script>
<script src="/modules/documents/js/registry_items.js?v=<?= $gui->genpass() ?>"></script>
<script>
    $(document).ready(function(){
        let filterParams = el_tools.getFilterParams(),
            $button_nav_create = $("#button_nav_create");
        if(isNaN(parseInt(filterParams.documentacial))){
            $('.tab-pane li').removeClass('active');
        }

        $(document).on('content_load', function (event, data) {
            if (data.params === 'filter=documentacial:1'){
                $button_nav_create.off("click").on("click", function(){
                    el_app.dialog_open('order_staff', {}, 'calendar');
                }).show();
            }else{
                $button_nav_create.hide();
            }
        });
        if (document.location.search === '?filter=documentacial:1' || filterParams.documentacial === '1') {
            $button_nav_create.show();
        }

        $(".doc_edit").off("click").on("click", function(){
            let doc_type = $(this).data("doctype"),
                doc_id = $(this).data("id"),
                plan_id = $(this).data('plan'),
                ins_id = $(this).data("ins"),
                module = "",
                handler = "";
            switch(doc_type){
                case 1:
                    module = "calendar";
                    handler = "order_staff";
                    break;
                case 2:
                    module = 'roadmap';
                    handler = 'registry_items_edit';
                    break;
                case 3:
                    module = 'plans';
                    handler = 'registry_edit';
                    break;
                case 5:
                    module = 'roadmap';
                    handler = 'view_road';
                    break;
            }
            if (handler === "") {
                alert("Редактирование этого типа документа не реализовано.");
                return;
            }
            // Передаём правильные параметры в зависимости от типа документа
            if (doc_type === 5) {
                // График устранения - передаём roadId
                el_app.dialog_open(handler, {roadId: doc_id}, module);
            } else if (doc_type === 2) {
                // Акт - передаём как массив [doc_id, parent_id] для registry_items_edit
                el_app.dialog_open(handler, [doc_id, 66], module);
            } else {
                // Остальные типы - стандартные параметры
                el_app.dialog_open(handler, {doc_id: doc_id, ins_id: ins_id, plan_id: plan_id}, module);
            }
        });

        $('#registry_items_delete_real .tab-pane li').on('click', function () {
            let docType = $(this).attr('id').replace('tab_', '');
            $(this).closest('.tab-pane').find('li').removeClass('active');
            $(this).addClass('active');
            el_tools.setcookie('document_active_pane', docType);
            el_app.setMainContent('/documents', 'filter=documentacial:' + docType);
        });
    });




    <?php
    $open_dialog = 0;
    if(isset($_POST['params'])){
        $postArr = explode('=', $_POST['params']);
        if($postArr[0] == 'open_dialog'){
            $open_dialog = intval($postArr[1]);
        }
    }/*elseif(isset($_GET['open_dialog']) && intval($_GET['open_dialog']) > 0){
        $open_dialog = intval($_GET['open_dialog']);
    }*/
    if($open_dialog > 0){
        echo 'el_app.setMainContent("/documents");
        el_app.dialog_open("agreement", {"docId": '.$open_dialog.', "taskId": '.$open_dialog.'}, "documents");';
    }
    ?>
</script>