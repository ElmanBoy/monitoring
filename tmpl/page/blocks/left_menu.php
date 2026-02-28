<?php
use Core\Gui;
use Core\Auth;

include_once $_SERVER['DOCUMENT_ROOT'] . '/core/connect.php';
$gui = new Gui();
$auth = new Auth();
?>
<div class="main_nav">
    <div class="item logo">
        <img src="/images/logo.svg">
        <div class="title"></div>
    </div>
	<div class="nav_scroll">
        <div id="wide_control">
            <span class="material-icons left" title="Свернуть меню" style="display: <?=$_COOKIE['widthPage'] == 'wide' ? 'none' : 'inline'?>">chevron_left</span>
            <span class='material-icons right' title='Развернуть меню' style="display: <?=$_COOKIE['widthPage'] == 'wide' ? 'inline' : 'none'?>">chevron_right</span>
        </div>
    <?php
    echo $gui->buildLeftMenu();
    ?>
    </div>
</div>