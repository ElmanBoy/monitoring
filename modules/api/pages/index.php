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
                if(isset($_GET['id']) && intval($_GET['id']) > 0){
                    include_once $_SERVER['DOCUMENT_ROOT'] . '/modules/api/pages/index_id_ajax.php';
                }else {
                    include_once $_SERVER['DOCUMENT_ROOT'] . '/modules/api/pages/index_ajax.php';
                }
                ?>
            </div>
        </div>

    </div>
<?
include_once $_SERVER['DOCUMENT_ROOT'].'/tmpl/page/blocks/footer.php';
?>