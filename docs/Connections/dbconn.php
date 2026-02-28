<?php
		//error_reporting(E_ALL);
		$hostname_dbconn = "mmc-plus.mysql";
		$database_dbconn = "mmc_plus_db";
		$username_dbconn = "mmc-plus_mysql";
		$password_dbconn = "1PkYz-Ws";
		
		$pop_mail_server = "mail.nic.ru";
		$smtp_mail_server= "mail.nic.ru";
		
		define('SITE_ROOT', $_SERVER['DOCUMENT_ROOT']);
		define('DB_TYPE', 'mysql');
		
		if(DB_TYPE=='mysql'){
			$dbconn = @mysql_pconnect($hostname_dbconn, $username_dbconn, $password_dbconn) or 
			trigger_error(mysql_error(),E_USER_ERROR);
		}elseif(DB_TYPE=='oracle'){
			putenv("ORACLE_HOME=$home_dbconn");
			putenv("NLS_LANG=AMERICAN_AMERICA.CL8MSWIN1251"); 
			$dbconn = oci_pconnect($username_dbconn, $password_dbconn, $database_dbconn);
			if (!$dbconn) {
			  $err = oci_error(); 
			  print "Error code = "     . $err['code'];
			  print "<br>Error message = "  . htmlentities($err['message']);
			  print "<br>Error position = " . $err['offset'];
			  print "<br>SQL Statement = "  . htmlentities($err['sqltext']);
			}
		}
		
		$dbconn = @mysql_pconnect($hostname_dbconn, $username_dbconn, $password_dbconn) or trigger_error(mysql_error(),E_USER_ERROR);
		include "functions.php";
		$url_arr=explode('/', $_SERVER['SCRIPT_NAME']); 
		if($url_arr[1]=='editor'){
			include_once $_SERVER['DOCUMENT_ROOT'].'/editor/e_modules/logging/logInit.php';
		}
		include "site_props.php"; 
		mysql_query("SET NAMES 'cp1251'", $dbconn);
		mysql_query("SET character_set_server='cp1251'", $dbconn);
		 $debug=false;
		 //error_reporting(E_ALL);
		?>