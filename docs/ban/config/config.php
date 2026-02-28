<?php require_once('../Connections/dbconn.php'); ?>
<?
//???? ?????? ?? ???????
$ban_red="0";
$ban_green="0";
$ban_blue="0";

//??? ????
define("err_log","log/err_log.log");
//??? ?????????? ? ????? ??????
define("hostname",$hostname_dbconn);
define("username",$username_dbconn);
define("password",$password_dbconn);
define("dbname",$database_dbconn);
define("delta_hit",60);
//??????
define("sesname","diplom");
//??? ??????????? ??????
//define (FATAL,E_USER_ERROR);
//define (ERROR,E_USER_WARNING);
//define (WARNING,E_USER_NOTICE);
$errortype = array (
                1   =>  "Error",
                2   =>  "Warning",
                4   =>  "Parsing Error",
                8   =>  "Notice",
                16  =>  "Core Error",
                32  =>  "Core Warning",
                64  =>  "Compile Error",
                128 =>  "Compile Warning",
                256 =>  "User Error",
                512 =>  "User Warning",
                1024=>  "User Notice"
                );
?>
