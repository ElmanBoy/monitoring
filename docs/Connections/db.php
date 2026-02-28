<?
//Получить все имена полей таблицы, пример
//SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name =`phpSP_users`


function el_dbconnect(){
global $hostname_dbconn, $username_dbconn, $password_dbconn;
	if(DB_TYPE=='mysql'){
		$dbconn = @mysql_pconnect($hostname_dbconn, $username_dbconn, $password_dbconn) or
		trigger_error(mysql_error(),E_USER_ERROR);
	}elseif(DB_TYPE=='oracle'){
		putenv("ORACLE_HOME=$home_dbconn");
		putenv("NLS_LANG=AMERICAN_AMERICA.CL8MSWIN1251"); 
		$dbconn = oci_pconnect($username_dbconn, $password_dbconn, $database_dbconn);
	}
	if (!$dbconn) {
	  el_dberror($dbconn);
	}else{
		return $dbconn;
	}
}

function el_database(){
global $database_dbconn;
return $database_dbconn;
}

//prepare vars before inserting in db
function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = ""){
  $theValue = (!get_magic_quotes_gpc()) ? addslashes($theValue) : $theValue;

  switch ($theType) {
    case "text":
	case "blob":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? "'" . doubleval($theValue) . "'" : "NULL";
      break;
    case "date":
	case "datetime":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
  }
  return $theValue;
}

// add slashes for vars
function quote_smart($value){
   if (get_magic_quotes_gpc()) {
       $value = stripslashes($value);
   }
   if (!is_numeric($value)) {
       $value = "'" . mysql_real_escape_string($value) . "'";
   }
   return $value;
}

//Execute any query
function el_sql($query, $result){
global $database_dbconn, $dbconn;
	mysql_select_db($database_dbconn, $dbconn);
	$query_result = "$query";
	$result = mysql_query($query_result, $dbconn) or die(mysql_error());
	$row_result = mysql_fetch_assoc($result);
	return $row_result;
}


//insert data in db
function el_dbinsert($table, $insertvars, $debug=false, $showQuery=false){
	global $database_dbconn, $dbconn, $_POST;
	$insers="";
	$inserv="";
	$insertfield1="";
	$result0=mysql_query("SELECT * FROM ".$table);
	$fields=mysql_num_fields($result0);
	$c=0;
	$err=0;
	while(list($insertfield, $insertvar)=each($insertvars)){
		if(substr_count($insertfield, ' *')>0 && strlen($insertvar)<1){
			$err++;
		}
		$insertfield=str_replace(' *','',$insertfield);
		if($err==0){
			$c++;
			if($c==count($insertvars)){
				$end='';
			}else{
				$end=', ';
			}
			$num=0.5;
			for($i=0; $i<$fields; $i++){
				if($insertfield==mysql_field_name($result0, $i)){
					$num=$i;
				}
				
			}
			if($num==0.5){
				echo 'Поле '.$insertfield.' в таблице '.$table.' не найдено.';
			}
			$type  = mysql_field_type($result0, $num);
			($type=='blob')?$type='text':$type=$type;
			$inserv.=GetSQLValueString($insertvar, $type).$end;
			$insertfield1.=$insertfield.$end;
		}else{
			echo '<font color=red>Заполните поле '.str_replace(' *','',$insertfield).'</font>';
		}
	}
	
	$insertSQL = "INSERT INTO ".$table." (".$insertfield1.") VALUES (".$inserv.")";
	if($showQuery==true)echo $insertSQL;
	  mysql_select_db($database_dbconn, $dbconn);
	  if(mysql_query($insertSQL, $dbconn)){
		return true;
	  }else{ 
		echo mysql_error();
		return false;
	  }
}

function el_dberror($conn){
	//var_dump($err);
	global $dbconn;
	if(DB_TYPE=='mysql'){
		print mysql_errno($dbconn) . ": " . mysql_error($dbconn);
	}elseif(DB_TYPE=='oracle'){
		$err=oci_error($conn);
		if(strlen($err['message'])>0 || strlen($err['code'])>0){
			print "Error code = "     . $err['code'];
			print "<br>Error message = "  . htmlentities($err['message']);
			print "<br>Error position = " . $err['offset'];
			print "<br>SQL Statement = "  . htmlentities($err['sqltext']);
		}
	}
}

function el_dbseek($result, $position){
	global $dbconn; //error_reporting(E_WARNING);
	if(DB_TYPE=='mysql'){
		$out=mysql_data_seek($result, $position);
	}elseif(DB_TYPE=='oracle'){
		oci_fetch($result);
		$lob = oci_new_descriptor($dbconn);//OCIResult($result, 1);
		$out=$lob->seek($position, OCI_SEEK_SET);
	} //echo $out;
	if(!$out) el_dberror($result);
}

function el_dbinsertid(){
	//
}

function el_dbcommit(){
	global $dbconn; 
	if(DB_TYPE=='mysql'){
		//
	}elseif(DB_TYPE=='oracle'){
		$committed = oci_commit($dbconn);	
		if(!$committed) el_dberror($dbconn);
	}
}

function el_dblongtext($result, $fieldSet, $fieldName){
	global $dbconn;
	$fieldName=strtoupper($fieldName);
	if(DB_TYPE=='mysql'){
		return $fieldSet[$fieldName];
	}elseif(DB_TYPE=='oracle'){
		$column_type  = oci_field_type($result, $fieldName);
		if($column_type=='CLOB' && $fieldSet[$fieldName]!=NULL){
			$lob = oci_result($result, $fieldName);//oci_new_descriptor($dbconn);
			return $lob->load();
		}else{
			return $fieldSet[$fieldName];
		}
	}
}

//select from db
function el_dbselect($query, $limit, $resultout, $mode='result', $debug=false, $showQuery=false){
	global $database_dbconn, $dbconn, $_GET;
	$maxRows_result = $limit;
	$pageNum_result = 0;
	if (isset($_GET['pn'])) {
	  $pageNum_result = $_GET['pn'];
	}
	$startRow_result = $pageNum_result * $maxRows_result;
	$query_limit_result = sprintf("%s LIMIT %d, %d", $query, $startRow_result, $maxRows_result);
	$result_query=($limit>0)?$query_limit_result:$query;
	if(DB_TYPE=='mysql'){
		mysql_select_db($database_dbconn, $dbconn);
		if($showQuery)echo '<br>'.$result_query.'<br>';
		$result=mysql_query($result_query, $dbconn);
	}elseif(DB_TYPE=='oracle'){
		$stmt = oci_parse($dbconn, $result_query);
		oci_execute($stmt);
		$result=$stmt;
	}
	if($result!=FALSE){
		if($mode=='result'){
			return $result;
		}elseif($mode=='row'){
			return $row_result = el_dbfetch($result);
		}
	}else{ 
		if($debug==true){echo 'Ошибка в запросе:'.$result_query;el_dberror($result);}
		return FALSE;
	}
}

function el_dbfetch($result, $type='ASSOC', $debug=false){
	global $dbconn;
	$errStr='';
	if(DB_TYPE=='mysql'){
		switch ($type){
			case 'BOTH': $out=mysql_fetch_array($result, MYSQL_BOTH); break;
			case 'NUM': $out=mysql_fetch_array($result, MYSQL_NUM); break;
			case 'ASSOC':
			case 'LOBS':
			default: $out=mysql_fetch_array($result, MYSQL_ASSOC); break;
		}
	}elseif(DB_TYPE=='oracle'){
		switch ($type){
			case 'BOTH': $out=oci_fetch_array($result, OCI_BOTH); break;
			case 'NUM': $out=oci_fetch_array($result, OCI_NUM); break;
			case 'LOBS':$out=oci_fetch_array($result, OCI_RETURN_LOBS); break;
			case 'ASSOC': 
			default: $out=oci_fetch_array($result, OCI_ASSOC); break;
		}
	} 
	if($out!=FALSE){
		return $out;
	}else{
		if($debug==true)el_dberror($result);
		return FALSE;
	}
}

function el_dbnumrows($result){
	if(DB_TYPE=='mysql'){
		return mysql_num_rows($result);
	}elseif(DB_TYPE=='oracle'){
		//$rows=el_dbfetch($result);
		return oci_num_rows($result);
	}
}

function el_dbresultfree($result){
	if(DB_TYPE=='mysql'){
		mysql_free_result($result);
	}elseif(DB_TYPE=='oracle'){
		oci_free_statement($result);
	}
}
//Parsing dump for db
function el_mysqldump($url, $ignoreerrors = false) {
   $file_content = file($url);
   //print_r($file_content);
   $query = "";
   foreach($file_content as $sql_line) {
     $tsl = trim($sql_line);
     if (($sql_line != "") && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != "#")) {
       $query .= $sql_line;
       if(preg_match("/;\s*$/", $sql_line)) {
         $query = str_replace(";", "", "$query");
         $result = mysql_query($query);
         if (!$result && !$ignoreerrors) die(mysql_error());
         $query = "";
       }
     }
   }
  }


//paging navigation
function el_dbpagecount($result, $pageurl, $maxRows_result, $totalRows_result, $template){
global $_GET, $_SERVER;

$pn = 0;
if (isset($_GET['pn'])) {
  $pn = $_GET['pn'];
}

if (isset($_GET['tr'])) {
  $tr = $_GET['tr'];
} else {
  $tr = $totalRows_result;
}

if($tr>0){
$totalPages_result = ceil($tr/$maxRows_result)-1;

$queryString_result = "";
if (!empty($_SERVER['QUERY_STRING'])) {
  $params = explode("&", $_SERVER['QUERY_STRING']);
  $newParams = array();
  foreach ($params as $param) {
    if (stristr($param, "pn") == false && stristr($param, "tr") == false) {
      array_push($newParams, $param);
    }
  }
  if (count($newParams) != 0) {
    $queryString_result = "&" . htmlentities(implode("&", $newParams));
  }
}
$queryString_result = sprintf("&tr=%d%s", $tr, $queryString_result);

 

if(isset($pageurl)){
	$pageurl="&".$pageurl;
}else{
	$pageurl="";
}
if($tr>0){
	include $_SERVER['DOCUMENT_ROOT'].$template;
}
}
}

//output data in cicle on page from db
function el_dbrowprint($resultout, $template, $emptymess){
	if(mysql_num_rows($resultout)>0){
		$result_row=mysql_fetch_assoc($resultout);
		do{
			include $_SERVER['DOCUMENT_ROOT'].$template;
		}while($result_row=mysql_fetch_assoc($resultout));
	}else{
		echo $emptymess;
	}
}

//Recreate index(FULLTEXT) for specified table
function el_reindex($table){ 
	if(substr_count($table, 'catalog_')>0){
		$catalog_id=preg_replace('/catalog_(.*)_data/', '$1', $table);
		$showIndex=el_dbselect("SELECT field FROM catalog_prop WHERE search=1 
							   AND (type='text' OR type='textarea') AND catalog_id='".$catalog_id."'", 0, $showIndex);
		$tf=mysql_fetch_assoc($showIndex);
		$fields=array();
		$keyNames=array();
		do{
			$fields[]='field'.$tf['field'];
		}while($tf=mysql_fetch_assoc($showIndex));
		echo $fList=implode(',', $fields);
	}else{
		$showIndex=el_dbselect("SHOW COLUMNS FROM ".$table, 0, $showIndex);
		$tf=mysql_fetch_assoc($showIndex);
		$fields=array();
		$keyNames=array();
		do{
			if($tf['Type']=='text' || $tf['Type']=='longtext'){$fields[]=$tf['Field'];}
		}while($tf=mysql_fetch_assoc($showIndex));
		$fList=implode(',', $fields);
	}
	
	$textFields=el_dbselect("SHOW INDEX FROM ".$table, 0, $textFields);
	$in=mysql_fetch_assoc($textFields);
	do{
		if($in['Index_type']=='FULLTEXT'){
			$dropIndex=$in['Key_name'];
		}
	}while($in=mysql_fetch_assoc($textFields));
	el_dbselect("ALTER TABLE ".$table." DROP INDEX `".$dropIndex."`", 0, $res);
	el_dbselect("ALTER TABLE ".$table." ADD FULLTEXT ".$in['Key_name']." (".$fList.")", 0, $res);
	el_dbselect("OPTIMIZE TABLE ".$table, 0, $res);
}

?>