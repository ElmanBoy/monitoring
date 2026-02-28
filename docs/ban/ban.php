<?
require_once("kernel.php");

//запрещаем кеширование страниц
header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
header ("Cache-Control: no-cache, must-revalidate");  
header ("Pragma: no-cache");                          

//запускаем сессию
$t=time();
session_name(sesname);
session_start();
if(($t-@$ptime)>600)
 {
  session_destroy();
 } 
session_register("idpp");
session_register("ptime");

//получаем текущую дату
$d1=date("Y-m-d H:",$t); 
 $host="";  //для добавления в таблицу statistics     
 $host_start="";

 //соеденяемся с базой данных
 $link=mysql_connect(hostname,username,password);
 //если ошибка - рисуем логотип и завершаем работу
 if (mysql_error()) 
  {
   header ("Content-type: image/jpeg");
   $im = @ImageCreate (400, 40);
   $background_color = ImageColorAllocate ($im, 0, 0, 200);
   $text_color = ImageColorAllocate ($im, 255, 255, 255);
   ImageString ($im, 1, 5, 5, "ERROR!", $text_color);
   ImageJpeg ($im);
   exit();
  }
 mysql_select_db(dbname);

  if (!session_is_registered("ids")) 
   {
    //Пользователь только что зашел на сайт
    //проверяем, есть ли кукис с idv (id_vizitor)
    if (!isset($idv)) 
     {
      //Кукиса нет.Пользователь впервые на этом сайте    
      //Добавляем в таблицу vizitors данные о пользователе и кидаем ему куку
      $s=split("\(|\)|;",$GLOBALS['HTTP_USER_AGENT']);
      $browser=$s[2];
      $os=$s[3];
      if (!isset($js)) 
       {
        //js отлючен	
        $js="N";
        $j="U";
        $c="U";
        $wh="U"; 
      }
      $query="insert into vizitors(browser,os,java,js,cookie,wh,stime) values('$browser','$os','$j','$js','$c','$wh',FROM_UNIXTIME('$t'))";
      mysql_query($query);
      $idv=mysql_insert_id();
      session_register("idv");
      $lt=$t;
      $host=" hosts=hosts+1, ";
      $host_start=1;
     }
    //пользователь раньше заходил на сайт или данные о нем только что добавили в vizitors 
    //проверяем, идет ли он через прокси. если через прокси, то пробуем получить реальный IP
    if (($t-$lt)>=86400) 
     { 
       //если пользователь зашел через сутки, то это хост
       $host=" hosts=hosts+1, ";
       $host_start=1;
     }
    
    if (isset($GLOBALS['HTTP_X_FORWARDED_FOR'])) 
     {
      $host_ip=$GLOBALS['HTTP_X_FORWARDED_FOR'];
      $proxy_ip=$GLOBALS['REMOTE_ADDR'];
     }
    else 
     {
      $host_ip=$GLOBALS['REMOTE_ADDR'];
      $proxy_ip="none";
     }
	 
    //добавляем инфу в таблицу sessions
    if (!isset($GLOBALS['REMOTE_HOST'])) 
     {
      $host_name=parse_url($_SERVER['HTTP_REFERER']);
	  $host_name=$host_name['host'];
     }
    else
     {
      $host_name=$GLOBALS['REMOTE_HOST'];
     }   
   if ($r==="") //r-referrer
     {  
      $r="none";
     }
    $query="insert into sessions (id_vizitor,host_name,host_ip,proxy_ip,referer,stime) values('$idv','$host_name','$host_ip','$proxy_ip','$r',FROM_UNIXTIME('$t'))";
    mysql_query($query);
    $ids=mysql_insert_id();
    session_register("ids");
   }
  if (!isset($pg)) 
   {
    $pg=$GLOBALS['HTTP_REFERER'];
   }
 $query="select id_pagename from pagenames where pagename='$pg'";
 $res=mysql_query($query);
 if (mysql_num_rows($res)>0) 
  {
   
   $idp=mysql_result($res,0);
  } 
 else 
  {
   $query="insert into pagenames (pagename) values('$pg')";
   mysql_query($query);
   $idp=mysql_insert_id();
  }
 //здесь выбираем время последнего посещения страницы, чтобы определить
 //это хит или нет
 $query="select UNIX_TIMESTAMP(max(stime)) from pages where id_pagename='$idp' and id_session='$ids'";
 $res=mysql_query($query);
 $row=mysql_result($res,0); 
 if (isset($row)) 
  { 
   if (($t-$row) >= delta_hit) 
     {
	$hit=" hits=hits+1, ";
     } 
    else 
     {
      $hit="";
     }
  }
 else
  {
   $hit=" hits=hits+1, ";
  }
  //обновляем statistics
 $query="select hits from statistics where stime like '$d1%'";
 $res=mysql_query($query);
 if (mysql_num_rows($res)!=0) 
  {
   $query="update statistics set ".$host.$hit."shows=shows+1 where stime like '$d1%'"; 
   mysql_query($query);
  }
 else 
  {
   $query="insert into statistics values ('$host_start','1','1',FROM_UNIXTIME('$t'))";
   mysql_query($query);
  }
 //добавляем в pages
 if (!isset($pg)) 
  {
   $pg=$GLOBALS['HTTP_REFERER'];
  } 
 //тут вставляем время на странице
 if($idpp!=="")
  {
   $ptime1=$t-$ptime;
   $query="update pages set ptime=$ptime1 where id_page='$idpp'";
   mysql_query($query);
  } 
 $query="insert into pages (id_session,id_pagename,stime) values ('$ids','$idp',FROM_UNIXTIME('$t'))";
 mysql_query($query);
 $idpp=mysql_insert_id();
 $ptime=$t;
 //добавляем в sessions.route
 $query="select route from sessions where id_session=$ids and route not like '%".$idp."|'";
 $res=mysql_query($query);
 if (mysql_num_rows($res)>0) 
  {
   $row=mysql_result($res,0);
   $route=$row.$idp."|";
   $query="update sessions set route='$route' where id_session=$ids";
   mysql_query($query); 
  }
 setcookie("idv",$idv,0x7FFFFFFF);
 setcookie("lt",$t,0x7FFFFFFF);
 //РИСУЕМ СЧЕТЧИК 
 //получаем данные из таблицы statistics
 $query="select sum(hosts),sum(hits),sum(shows) from statistics";
 $res=mysql_query($query);
 $row=mysql_fetch_row($res);
 header ("Content-type: image/png");
 $im = @ImageCreateFromPng ("banner.png");
 $background_color = ImageColorAllocate ($im, 0, 0, 200);
 $text_color = ImageColorAllocate ($im, $ban_red, $ban_green, $ban_blue);
 ImageString ($im, 1, 88-strlen($row[2])*5, 3,   $row[2], $text_color);
 ImageString ($im, 1, 88-strlen($row[1])*5, 12,  $row[1], $text_color); 
 ImageString ($im, 1, 88-strlen($row[0])*5, 21,  $row[0], $text_color);
 ImageJpeg ($im);
?>
