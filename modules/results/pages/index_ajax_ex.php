<?php
use Core\Gui;
use Core\Db;


require_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$gui = new Gui;
$db = new Db;

function html($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}
// Получаем список отчетов для текущего пользователя
$reports = R::findAll('reports', ' ORDER BY created_at DESC');

// Для каждого отчета получаем название шаблона
foreach ($reports as &$report) {
    $template = R::load('report_templates', $report->template_id);
    $report->template_name = $template->name;
}
unset($report); // Разрываем ссылку


/*R::exec("CREATE TYPE inspection_status AS ENUM ('planned', 'in_progress', 'completed', 'cancelled')");
R::exec("CREATE TYPE inspection_result AS ENUM ('no_violations', 'violations_found', 'critical_violations')");
R::exec("CREATE TYPE violation_type AS ENUM ('sanitary', 'documentation', 'safety', 'financial', 'other')");
R::exec("CREATE TYPE severity_level AS ENUM ('low', 'medium', 'high')");
R::exec("CREATE TYPE report_type AS ENUM ('full', 'short')");
R::exec("CREATE TYPE user_role AS ENUM ('admin', 'inspector', 'viewer')");

// Создание таблиц
R::exec('CREATE TABLE institution (
    id SERIAL PRIMARY KEY,
    name VARCHAR(500) NOT NULL,
    type VARCHAR(100) NOT NULL,
    region VARCHAR(100) NOT NULL,
    address TEXT,
    director VARCHAR(200),
    phone VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)'
);*/
?>

<div class='nav'>
    <div class='nav_01'>
        <?
        /*echo $gui->buildTopNav([
            'title' => 'Справочники',
            'registryList' => '',
            'renew' => 'Сбросить все фильтры',
            'create' => 'Новый справочник',
            'clone' => 'Копия справочника',
            'delete' => 'Удалить выделенные',
            'list_props' => 'Поля справочников',
            'logout' => 'Выйти'
        ]
        );*/
        ?>
    </div>

</div>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
        .container { margin: 0 auto; padding: 20px; max-width: 1300px; background-color: #fff; border-radius: 4px;height: 80vh; }
        .btn {
            padding: 8px 12px;
            margin: 5px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background-color: #007bff; color: white; }
        .btn-success { background-color: #28a745; color: white; }
        .btn-danger { background-color: #dc3545; color: white; }
        .actions { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        tr:hover { background-color: #f5f5f5; }
    </style>
</head>
<div class='scroll_wrap'>
<div class="container">
    <h1>Отчеты</h1>

    <div class="actions">
        <a href="create.php" class="btn btn-primary">Создать новый отчет</a>
    </div>

    <table id="reportsTable" class="display">
        <thead>
        <tr>
            <th>Название</th>
            <th>Шаблон</th>
            <th>Формат</th>
            <th>Дата создания</th>
            <th>Действия</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($reports as $report): ?>
            <tr>
                <td><?= html($report->name) ?></td>
                <td><?= html($report->template_name) ?></td>
                <td><?= $report->format == 'full' ? 'Полный' : 'Сокращенный' ?></td>
                <td><?= date('d.m.Y H:i', strtotime($report->created_at)) ?></td>
                <td>
                    <div class="action-buttons">
                        <a href="view.php?id=<?= $report->id ?>" class="btn btn-primary">Просмотр</a>
                        <?php if ($report->file_path): ?>
                            <a href="<?= $report->file_path ?>" class="btn btn-success">Скачать</a>
                        <?php else: ?>
                            <a href="export.php?id=<?= $report->id ?>&format=pdf" class="btn btn-success">PDF</a>
                            <a href="export.php?id=<?= $report->id ?>&format=xlsx" class="btn btn-success">Excel</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#reportsTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/ru.json'
            },
            columnDefs: [
                { orderable: false, targets: 4 } // Отключаем сортировку для колонки с кнопками
            ],
            scrollX: true,
            initComplete: function() {
                // Добавляем placeholder для поиска
                $('.dataTables_filter input').attr('placeholder', 'Поиск по отчетам...');
            }
        });
    });
</script></div>
</body>
</html>