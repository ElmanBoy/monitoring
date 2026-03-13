<?php
/**
 * Крон: активация плана-графика устранения нарушений
 *
 * Запускается, например, каждые 5 минут:
 * * /5 * * * * php /var/www/html/cron/violations_schedule.php
 *
 * Логика:
 *  - Находит акты (documentacial=2), которые только что созданы (нет записи в cron_locks)
 *  - Для каждого акта находит связанные checksviolations через checkstaff
 *  - Выставляет schedule_status = 0 тем нарушениям, у которых schedule_status IS NULL
 *    (т.е. поле только что добавлено миграцией и ещё не инициализировано)
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

use Core\Db;

$db = new Db();

// Находим акты, у которых есть нарушения без инициализированного schedule_status
// agreement.source_table = 'checkstaff', documentacial = 2
// Нарушения связаны через checksviolations.tasks → checkstaff.id
// checkstaff.check_uid + institution → agreement.plan_id + ins_id

$newActs = $db->db::getAll('
    SELECT DISTINCT
        a.id        AS agreement_id,
        a.plan_id,
        a.ins_id,
        a.source_id AS checkstaff_id
    FROM ' . TBL_PREFIX . 'agreement a
    WHERE a.documentacial = 2
      AND a.active = 1
      AND EXISTS (
          SELECT 1
          FROM ' . TBL_PREFIX . 'checksviolations cv
          JOIN ' . TBL_PREFIX . 'checkstaff cs ON cs.id = cv.tasks
          WHERE cs.institution = a.ins_id
            AND cv.schedule_status IS NULL
      )
'
);

if (empty($newActs)) {
    exit(0);
}

foreach ($newActs as $act) {
    $insId = intval($act['ins_id']);
    $planId = intval($act['plan_id']);

    // Находим все задания (checkstaff) по данному плану и учреждению
    $plan = $db->selectOne('checksplans', ' WHERE id = ?', [$planId]);
    if (!$plan) continue;

    $staffRows = $db->select('checkstaff', ' WHERE check_uid = ? AND institution = ?', [
        $plan->uid,
        $insId
    ]
    );

    if (empty($staffRows)) continue;

    $taskIds = array_map(fn($s) => intval($s->id), $staffRows);
    $taskIdsStr = implode(',', $taskIds);

    // Инициализируем schedule_status = 0 для нарушений, у которых он ещё NULL
    $db->db::exec('
        UPDATE ' . TBL_PREFIX . "checksviolations
        SET schedule_status = 0
        WHERE tasks IN ($taskIdsStr)
          AND schedule_status IS NULL
    "
    );

    echo "[OK] Акт #{$act['agreement_id']}: инициализированы нарушения по учреждению #{$insId}\n";
}

exit(0);