<?php
session_start();

echo (isset($_SESSION['login'])) ? '1' : '0';
?>