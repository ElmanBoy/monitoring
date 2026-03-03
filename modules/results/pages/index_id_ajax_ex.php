<?php

$reportId = $_GET['id'] ?? 0;

// Загружаем отчет и связанные данные через RedBean
$report = R::load('report', $reportId);
if (!$report->id) {
    die('Отчет не найден');
}

// Загружаем шаблон отчета
$template = R::load('report_template', $report->template_id);

// Получаем видимые поля отчета
$fields = R::find('report_field',
    ' template_id = ? AND is_visible = TRUE ORDER BY display_order',
    [$report->template_id]
);

// Получаем данные отчета в зависимости от типа
$data = [];
switch ($report->template_id) {
    case 1: // Отчет по результатам проверки
        $data = R::getAll("
            SELECT 
                i.name AS institution_name,
                it.name AS inspection_type,
                ins.start_date, 
                ins.end_date, 
                ins.inspector_name, 
                CASE ins.result
                    WHEN 'no_violations' THEN 'Без нарушений'
                    WHEN 'violations_found' THEN 'Нарушения'
                    WHEN 'critical_violations' THEN 'Критические нарушения'
                END AS result,
                (SELECT COUNT(*) FROM violation v WHERE v.inspection_id = ins.id) AS violations_count
            FROM inspection ins
            JOIN institution i ON ins.institution_id = i.id
            LEFT JOIN inspection_type it ON ins.type_id = it.id
            ORDER BY ins.start_date DESC
        ");
        break;

    case 2: // Отчет по устранению нарушений
        $data = R::getAll("
            SELECT 
                i.name AS institution_name,
                v.type,
                v.description,
                CASE v.severity
                    WHEN 'low' THEN 'Низкая'
                    WHEN 'medium' THEN 'Средняя'
                    WHEN 'high' THEN 'Высокая'
                END AS severity,
                v.deadline,
                CASE WHEN v.is_fixed THEN 'Да' ELSE 'Нет' END AS is_fixed,
                v.fix_date,
                v.fix_description,
                ins.start_date AS inspection_date,
                ins.inspector_name
            FROM violation v
            JOIN inspection ins ON v.inspection_id = ins.id
            JOIN institution i ON ins.institution_id = i.id
            ORDER BY v.deadline ASC
        ");
        break;

    // Другие типы отчетов...
}

// Получаем вложенные строки отчета
$nestedRows = R::find('report_nested_row',
    ' report_id = ? ORDER BY parent_id NULLS FIRST, sort_order',
    [$report->id]
);

// Функция для рекурсивного вывода вложенных строк
function renderNestedRows($rows, $parentId = null, $level = 0) {
    foreach ($rows as $row) {
        if ($row->parent_id == $parentId) {
            $rowData = json_decode($row->row_data, true);
            echo '<tr class="nested-row level-' . $level . '">';
            foreach ($rowData as $value) {
                echo '<td>' . html($value) . '</td>';
            }
            echo '</tr>';
            renderNestedRows($rows, $row->id, $level + 1);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?= html($report->name) ?> - Система мониторинга</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .report-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .report-title {
            margin: 0;
            color: #333;
        }
        .report-meta {
            color: #666;
            margin-top: 10px;
        }
        .report-actions {
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            margin-right: 10px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            font-weight: bold;
        }
        .btn-pdf {
            background-color: #e74c3c;
        }
        .btn-excel {
            background-color: #2ecc71;
        }
        .btn-back {
            background-color: #3498db;
        }
        table.dataTable {
            border-collapse: collapse !important;
        }
        .nested-row {
            background-color: #f9f9f9;
        }
        .level-1 td:first-child {
            padding-left: 30px;
        }
        .level-2 td:first-child {
            padding-left: 60px;
        }
        .level-3 td:first-child {
            padding-left: 90px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="report-header">
        <h1 class="report-title"><?= html($report->name) ?></h1>
        <div class="report-meta">
            <p><strong>Шаблон:</strong> <?= html($template->name) ?></p>
            <p><strong>Формат:</strong> <?= $report->format == 'full' ? 'Полный' : 'Сокращенный' ?></p>
            <p><strong>Дата создания:</strong> <?= date('d.m.Y H:i', strtotime($report->created_at)) ?></p>
        </div>
    </div>

    <div class="report-actions">
        <a href="export.php?id=<?= $report->id ?>&format=pdf" class="btn btn-pdf">Экспорт в PDF</a>
        <a href="export.php?id=<?= $report->id ?>&format=xlsx" class="btn btn-excel">Экспорт в Excel</a>
        <a href="index.php" class="btn btn-back">Назад к списку</a>
    </div>

    <div class="report-container">
        <table id="reportData" class="display">
            <thead>
            <tr>
                <?php foreach ($fields as $field): ?>
                    <th><?= html($field->display_name) ?></th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($data as $row): ?>
                <tr>
                    <?php foreach ($fields as $field): ?>
                        <td><?= html($row[$field->field_name] ?? '') ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>

            <?php if (!empty($nestedRows)): ?>
                <?php renderNestedRows($nestedRows); ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#reportData').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json'
            },
            scrollX: true,
            scrollY: '70vh',
            scrollCollapse: true,
            fixedHeader: true,
            dom: '<"top"lf>rt<"bottom"ip>',
            pageLength: 50,
            initComplete: function() {
                // Добавляем кнопки экспорта
                $('.dataTables_wrapper .top').prepend(
                    '<div class="export-buttons" style="float: right; margin-left: 10px;">' +
                    '<a href="export.php?id=<?= $report->id ?>&format=pdf" class="btn btn-pdf">PDF</a>' +
                    '<a href="export.php?id=<?= $report->id ?>&format=xlsx" class="btn btn-excel">Excel</a>' +
                    '</div>'
                );
            }
        });
    });
</script>
</body>
</html>