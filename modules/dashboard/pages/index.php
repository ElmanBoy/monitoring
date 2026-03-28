<?
include_once $_SERVER['DOCUMENT_ROOT'].'/tmpl/page/blocks/header.php';
?>
<body>
    <div class="wrap">
        <div class="content<?=$_COOKIE['widthPage'] == 'wide' ? ' wide' : ''?>">
            <?
            include_once $_SERVER['DOCUMENT_ROOT'].'/tmpl/page/blocks/left_menu.php';
            ?>
            <div class="main_data">
                <?
                // Проверяем параметр page для подключения нужного дашборда
                if (isset($_GET['page']) && $_GET['page'] === 'monitoring') {
                    include_once $_SERVER['DOCUMENT_ROOT'] . '/modules/dashboard/pages/monitoring.php';
                } elseif (isset($_GET['page']) && $_GET['page'] === 'monitoring_institution') {
                    include_once $_SERVER['DOCUMENT_ROOT'] . '/modules/dashboard/pages/monitoring_institution_ajax.php';
                } else {
                    include_once $_SERVER['DOCUMENT_ROOT'] . '/modules/dashboard/pages/index_ajax.php';
                }
                ?>
            </div>
        </div>

    </div>
<?
include_once $_SERVER['DOCUMENT_ROOT'].'/tmpl/page/blocks/footer.php';
?>