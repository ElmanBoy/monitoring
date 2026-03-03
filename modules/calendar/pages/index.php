<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/tmpl/page/blocks/header.php';
?>
<body>
    <div class="wrap">
        <div class="content<?=$_COOKIE['widthPage'] == 'wide' ? ' wide' : ''?>">
            <?php
            include_once $_SERVER['DOCUMENT_ROOT'].'/tmpl/page/blocks/left_menu.php';
            ?>
            <div class="main_data">
                <?php
                /*if(isset($_GET['plan']) && intval($_GET['plan']) > 0){
                    include_once $_SERVER['DOCUMENT_ROOT'] . '/modules/calendar/pages/view_plan.php';
                }elseif(isset($_GET['id']) && intval($_GET['id']) > 0){
                    include_once $_SERVER['DOCUMENT_ROOT'] . '/modules/calendar/pages/index_id_ajax.php';
                }else {*/
                    include_once $_SERVER['DOCUMENT_ROOT'] . '/modules/calendar/pages/index_ajax.php';
                //}
                ?>
            </div>
        </div>

    </div>
<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/tmpl/page/blocks/footer.php';
?>