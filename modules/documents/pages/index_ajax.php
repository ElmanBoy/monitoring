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
 * Возвращает данные для отображения иконки статуса документа
 * Учитывает последовательность согласования, перенаправления и отклонения
 *
 * @param int $userId ID пользователя
 * @param string $agreementListJson JSON строка agreementlist
 * @param int $documentStatus Общий статус документа
 * @return array Данные для иконки: [icon_class, color, title, can_approve, ...]
 */
function getDocumentStatusForIcon(int $userId, ?string $agreementListJson, int $documentStatus): array
{
    // Декодируем JSON
    $data = json_decode($agreementListJson, true);

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
    $getApproverStatus = function($approver) {
        $result = $approver['result'] ?? null;

        if (!$result || !is_array($result)) {
            return ['status' => 'pending', 'result_id' => 0];
        }

        $resultId = intval($result['id'] ?? 0);

        switch ($resultId) {
            case 1: // Подписание
                return ['status' => 'approved', 'result_id' => 1];
            case 2: // Согласование с ЭП
                return ['status' => 'approved', 'result_id' => 2];
            case 3: // Согласование
                return ['status' => 'approved', 'result_id' => 3];
            case 4: // Перенаправление
                return ['status' => 'redirected', 'result_id' => 4];
            case 5: // Отклонение
                return ['status' => 'rejected', 'result_id' => 5];
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
            'rejected' => 0
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

    // Сначала ищем пользователя во всём дереве согласования (включая перенаправления)
    $userFound = false;
    $userInfo = null;

    foreach ($data as $section) {
        // Сначала ищем в основном списке
        $found = $findUserInSection($section, $userId);
        if ($found) {
            $userFound = true;
            $userInfo = $found;
            break;
        }

        // Если не нашли в основном списке, ищем во всех перенаправлениях
        $foundInRedirect = $findUserInAllRedirects($section, $userId);
        if ($foundInRedirect) {
            $userFound = true;
            $userInfo = $foundInRedirect;
            break;
        }
    }

    // Если пользователь найден в списке согласования
    if ($userFound) {
        $userStatus = $getApproverStatus($userInfo['approver']);

        switch ($userStatus['status']) {
            case 'approved':
                // Пользователь уже согласовал/подписал
                $type = intval($userInfo['approver']['type'] ?? 1);
                $title = $type == 1 ? 'Вы подписали' : 'Вы согласовали';
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
                // Пользователь перенаправил
                // Проверяем, завершено ли перенаправление
                $redirectCompleted = true;
                if (isset($userInfo['approver']['redirect']) && is_array($userInfo['approver']['redirect'])) {
                    $redirectApprovers = array_slice($userInfo['approver']['redirect'], 1);
                    foreach ($redirectApprovers as $redirectApprover) {
                        $redirectStatus = $getApproverStatus($redirectApprover);
                        if ($redirectStatus['status'] !== 'approved') {
                            $redirectCompleted = false;
                            break;
                        }
                    }
                }

                if ($redirectCompleted) {
                    // Перенаправление завершено - пользователь должен согласовать заново
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

            case 'pending':
                // Пользователь ожидает согласования
                // Проверяем, может ли пользователь действовать сейчас
                $canAct = true;

                // Для последовательного согласования проверяем предыдущих
                if ($userInfo['list_type'] == 1 && $userInfo['index'] > 0) {
                    $approvers = array_slice($userInfo['section'], 1);
                    for ($i = 0; $i < $userInfo['index']; $i++) {
                        $prevStatus = $getApproverStatus($approvers[$i]);
                        if ($prevStatus['status'] !== 'approved' && $prevStatus['status'] !== 'redirected') {
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
                    $reg->agreementlist, $reg->status);
                if($reg->status == 1 || $agrStatus['icon_class'] == 'task_alt'){
                    $icon = 'task_alt';
                    $status = 'Согласован';
                    $class = 'green';

                }else{
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
                    <td class="status '.$class.'"'.$title.'><span class="material-icons '.$class.'"'.$style.'>' . $icon . '</span> '.$agrStatus['status_text'].'</td>
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
                case 3:
                    module = 'plans';
                    handler = 'registry_edit';
                    break;
            }
            el_app.dialog_open(handler, {doc_id: doc_id, ins_id: ins_id, plan_id: plan_id}, module);
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