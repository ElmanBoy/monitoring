<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/tmpl/page/blocks/header.php';
?>
    <body>
<div class="wrap">
    <div class="content<?= $_COOKIE['widthPage'] == 'wide' ? ' wide' : '' ?>">
        <?php
        include_once $_SERVER['DOCUMENT_ROOT'] . '/tmpl/page/blocks/left_menu.php';
        ?>
        <div class="main_data">
            <?php
            include_once $_SERVER['DOCUMENT_ROOT'] . '/modules/.violations/pages/index_ajax.php';
            ?>
        </div>
    </div>
</div>
<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/tmpl/page/blocks/footer.php';
?>