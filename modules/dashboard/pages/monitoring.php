<?php
/**
 * Дашборд мониторинга устранения нарушений
 */

use Core\Gui;
use Core\Db;
use Core\Auth;

require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';

$gui = new Gui();
$db = new Db();
$auth = new Auth();

$gui->set('module_id', 19);

// Получаем данные из представления
$stats = $db->db::getAll("
    SELECT * FROM v_dashboard_institutions
    WHERE total_violations > 0
    ORDER BY violations_overdue DESC, institution_name
");

// Общая статистика
$totalStats = [
    'institutions' => count($stats),
    'total_violations' => array_sum(array_column($stats, 'total_violations')),
    'violations_fixed' => array_sum(array_column($stats, 'violations_fixed')),
    'violations_check' => array_sum(array_column($stats, 'violations_check')),
    'violations_pending' => array_sum(array_column($stats, 'violations_pending')),
    'violations_overdue' => array_sum(array_column($stats, 'violations_overdue')),
];

$totalStats['fix_rate'] = $totalStats['total_violations'] > 0
    ? round(($totalStats['violations_fixed'] / $totalStats['total_violations']) * 100, 1)
    : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Мониторинг устранения нарушений</title>
    <style>
        .monitoring_container {
            padding: 20px;
        }

        .stats_cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat_card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #2196F3;
        }

        .stat_card.success { border-left-color: #4CAF50; }
        .stat_card.warning { border-left-color: #FF9800; }
        .stat_card.danger { border-left-color: #f44336; }
        .stat_card.info { border-left-color: #2196F3; }

        .stat_card .label {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }

        .stat_card .value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }

        .stat_card .subtext {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .monitoring_table {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .monitoring_table table {
            width: 100%;
            border-collapse: collapse;
        }

        .monitoring_table th {
            background: #f5f5f5;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #333;
            border-bottom: 2px solid #ddd;
        }

        .monitoring_table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }

        .monitoring_table tr:hover {
            background: #f9f9f9;
        }

        .progress_bar {
            width: 100%;
            height: 20px;
            background: #eee;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress_fill {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            transition: width 0.3s;
        }

        .progress_text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 11px;
            font-weight: bold;
            color: #333;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge.success { background: #c8e6c9; color: #2e7d32; }
        .badge.warning { background: #fff3e0; color: #e65100; }
        .badge.danger { background: #ffcdd2; color: #c62828; }
        .badge.info { background: #bbdefb; color: #1565c0; }
    </style>
</head>
<body>

<div class="nav">
    <div class="nav_01">
        <?
        echo $gui->buildTopNav([
            'title' => 'Мониторинг устранения нарушений',
            'renew' => 'Обновить данные',
            'logout' => 'Выйти'
        ]);
        ?>
    </div>
</div>

<div class="scroll_wrap">
    <div class="monitoring_container">

        <h2 style="margin-bottom: 20px;">Общая статистика</h2>

        <div class="stats_cards">
            <div class="stat_card info">
                <div class="label">Учреждений с нарушениями</div>
                <div class="value"><?= $totalStats['institutions'] ?></div>
            </div>

            <div class="stat_card">
                <div class="label">Всего нарушений</div>
                <div class="value"><?= $totalStats['total_violations'] ?></div>
            </div>

            <div class="stat_card success">
                <div class="label">Устранено</div>
                <div class="value"><?= $totalStats['violations_fixed'] ?></div>
                <div class="subtext"><?= $totalStats['fix_rate'] ?>% от общего числа</div>
            </div>

            <div class="stat_card info">
                <div class="label">На проверке</div>
                <div class="value"><?= $totalStats['violations_check'] ?></div>
            </div>

            <div class="stat_card warning">
                <div class="label">Ожидают устранения</div>
                <div class="value"><?= $totalStats['violations_pending'] ?></div>
            </div>

            <div class="stat_card danger">
                <div class="label">Просрочено</div>
                <div class="value"><?= $totalStats['violations_overdue'] ?></div>
            </div>
        </div>

        <h2 style="margin: 30px 0 20px;">Статистика по учреждениям</h2>

        <div class="monitoring_table">
            <table>
                <thead>
                    <tr>
                        <th>Учреждение</th>
                        <th style="width: 100px; text-align: center;">Всего</th>
                        <th style="width: 250px;">Прогресс устранения</th>
                        <th style="width: 100px; text-align: center;">На проверке</th>
                        <th style="width: 100px; text-align: center;">Просрочено</th>
                        <th style="width: 120px;">Последняя активность</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats as $row):
                        $fixRate = $row['total_violations'] > 0
                            ? round(($row['violations_fixed'] / $row['total_violations']) * 100, 1)
                            : 0;

                        $statusClass = 'info';
                        if ($row['violations_overdue'] > 0) $statusClass = 'danger';
                        elseif ($fixRate >= 80) $statusClass = 'success';
                        elseif ($fixRate >= 50) $statusClass = 'warning';
                    ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['institution_name']) ?></strong>
                            </td>
                            <td style="text-align: center;">
                                <strong><?= $row['total_violations'] ?></strong>
                            </td>
                            <td>
                                <div class="progress_bar">
                                    <div class="progress_fill" style="width: <?= $fixRate ?>%"></div>
                                    <div class="progress_text">
                                        <?= $row['violations_fixed'] ?> / <?= $row['total_violations'] ?> (<?= $fixRate ?>%)
                                    </div>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($row['violations_check'] > 0): ?>
                                    <span class="badge info"><?= $row['violations_check'] ?></span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($row['violations_overdue'] > 0): ?>
                                    <span class="badge danger"><?= $row['violations_overdue'] ?></span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 12px; color: #666;">
                                <?= $row['last_schedule_date'] ? date('d.m.Y', strtotime($row['last_schedule_date'])) : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($stats)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                <span class="material-icons" style="font-size: 48px; display: block; margin-bottom: 10px;">check_circle</span>
                                Нарушений для мониторинга не найдено
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html>
