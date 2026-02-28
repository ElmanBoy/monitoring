<?
//Create simple menu
$currPos=0;
function el_menut_simpleOLD($id=0, $class='', $subclass='', $count=0, $posc=0){
	global $database_dbconn,$dbconn, $path, $currPos;
	$query_menu = "SELECT id, path, name FROM cat WHERE parent = $id AND menu = 'Y' ORDER BY sort ASC";
	$menu = el_dbselect($query_menu, 0, $menu);
	$has_child=0;
	if(mysql_num_rows($menu)>0){
		if($count>0){
			echo "\n".'<ul>'."\n";// class="'.$subclass.'" id="menu'.$id.'"
		}
		$c=0;
		while($row_menu = mysql_fetch_assoc($menu)){
			if(el_hasChild($row_menu['path'])){
				$script=' ';//id="li'.$row_menu['id'].'" onmouseover="menu('.$row_menu['id'].')" onmouseout="menu('.$row_menu['id'].')"
				$has_child=1;
			}else{
				$script='';
				$has_child=0;
			}
			$clas=(@substr_count($path, $row_menu['path'])>0)?' class="'.((strlen($class)>0)?$class:'current').'"':'';
			if($path==$row_menu['path']){ 
				$currPos=($count==0)?$c:$posc;
			}
			if($count==0){
				$tagStart='<h2>'; $tagEnd='</h2>';
			}else{
				$tagStart='<h3><a href="'.$row_menu['path'].'/">'; $tagEnd='</a></h3>';
			}
			echo '<li'.$clas.$script.'>'.$tagStart.$row_menu['name'].$tagEnd;
			if($has_child==1){
				el_menut_simple($row_menu['id'], $class, $subclass, ++$count, $c);
				$count=0;
			}
			echo' </li>'."\n";
			$c++;
		}; 
		if($count>0){
			echo '</ul>'."\n";
		}
	}
}

$cacheMenuString='';
function el_menuToCache($id=0, $class='', $subclass='', $count=0, $posc=0){
	global $database_dbconn,$dbconn, $path, $currPos, $cacheMenuString;
	$query_menu = "SELECT id, path, name FROM cat WHERE parent = $id AND menu = 'Y' ORDER BY sort ASC";
	$menu = el_dbselect($query_menu, 0, $menu);
	$has_child=0;
	$total_rows=mysql_num_rows($menu);
	if($total_rows>0){
		if($count>0){
			$cacheMenuString.="\n".'<ul>'."\n";// class="'.$subclass.'" id="menu'.$id.'"
		}
		$c=0;
		while($row_menu = mysql_fetch_assoc($menu)){
			if(el_hasChild($row_menu['path'])){
				$script=' ';//id="li'.$row_menu['id'].'" onmouseover="menu('.$row_menu['id'].')" onmouseout="menu('.$row_menu['id'].')"
				$has_child=1;
			}else{
				$script='';
				$has_child=0;
			}
			//$clas=(@substr_count($path, $row_menu['path'])>0)?' class="'.((strlen($class)>0)?$class:'current').'"':'';
			if($path==$row_menu['path']){ 
				$currPos=($count==0)?$c:$posc;
			}
			if($count==0){
				$tagStart='<h2>'; $tagEnd='</h2>';
			}else{
				$tagStart='<h3><a href="'.$row_menu['path'].'/">'; $tagEnd='</a></h3>';
			}
			$cacheMenuString.='<li'.$clas.$script.'>'.$tagStart.$row_menu['name'].$tagEnd;
			if($has_child==1){
				el_menuToCache($row_menu['id'], $class, $subclass, ++$count, $c);
				$count=0;
			}
			$cacheMenuString.=' </li>'."\n";
			$c++;
		}; 
		if($count>0){
			$cacheMenuString.='</ul>'."\n";
		}
	}
}

function el_menuFromCache($id=0, $class='', $subclass=''){
	global $cacheMenuString, $currPos;
	$cacheDir=$_SERVER['DOCUMENT_ROOT'].'/editor/cache/menus';
	$cacheFile=$cacheDir.'/menu'.$id.'.html';
	if(!is_dir($cacheDir))mkdir($cacheDir, 0777);
	if(!file_exists($cacheFile)){
		el_menuToCache($id=0, $class='', $subclass='');
		file_put_contents($cacheFile, $cacheMenuString);
		include $cacheFile;
	}else{
		include $cacheFile;
	}
}

//Create Tree Menu(Parent itaems)///////////////////////////////////
$imenu=0;
function el_menutree($displaymode,$parentpath=''){//Parent items, first level only
global $database_dbconn;
global $dbconn;
global $path;
$orient="V";
mysql_select_db($database_dbconn, $dbconn);
$query_menuparent = "SELECT id, `path`, parent FROM cat WHERE `path` = '$parentpath' AND menu = 'Y'";
$menuparent = mysql_query($query_menuparent, $dbconn) or die(mysql_error());
$row_menuparent = mysql_fetch_assoc($menuparent);
$menupar=$row_menuparent['parent'];

mysql_select_db($database_dbconn, $dbconn);
$querymenutree = "SELECT * FROM cat WHERE parent='".$menupar."' AND menu = 'Y' ORDER BY sort ASC";  
$menutree = mysql_query($querymenutree, $dbconn) or die(mysql_error());
$row_menutree = mysql_fetch_assoc($menutree);
do{
$parent=$row_menutree['id'];
$child=mysql_query("SELECT * FROM cat WHERE parent='$parent'", $dbconn);
 if($orient=="V"){
  if(mysql_num_rows($child)>0){
  if($idshow[$row_menutree['id']]!="Y"){$displaymode="block";}
  
   if($displaymode=="block"){
   if($row_menutree['path']!=$path){$class="topmenu";}else{$class="topmenuover";}
  echo "<img src=\"http://".$_SERVER['SERVER_NAME']."/editor/img/mminus.gif\" border=0 id=menuimg".$row_menutree['id']." align=middle>";}
  else{
  if($row_menutree['path']!=$path){$class="topmenu";}else{$class="topmenuover";}
  echo "<img src=\"http://".$_SERVER['SERVER_NAME']."/editor/img/mplus.gif\" border=0 id=menuimg".$row_menutree['id']." align=middle>";}}else{
  if($row_menutree['path']!=$path){$class="topmenu";}else{$class="topmenuover";}
  echo "<img src=\"http://".$_SERVER['SERVER_NAME']."/editor/img/mspacer.gif\" border=0  align='middle'>";}
echo ' <a href="http://'.$_SERVER['SERVER_NAME'].$row_menutree['path'].'" class="'.$class.'">&raquo; '.$row_menutree['name'].'</a><br>';
menuchild($parent, $displaymode, $row_menutree['id']);
 }else{

 }
}while($row_menutree = mysql_fetch_assoc($menutree));
mysql_free_result($menutree);
}

function menuchild($parent, $collapse){//Child Items
global $database_dbconn;
global $dbconn;
global $imenu;

$querymenuchild = "SELECT * FROM cat WHERE parent='$parent' AND menu='Y' ORDER BY sort ASC";
$menuchild = mysql_query($querymenuchild, $dbconn) or die(mysql_error());
$row_menuchild = mysql_fetch_assoc($menuchild);
$idchild=$row_menuchild['id'];
if($idchild){//if item is exist...
$imenu++;

echo "<div style=\"display:".$collapse."; padding-left:25px\" id=\"menudiv".$row_menuchild['parent']."\" class=\"childdiv\" style='padding-left:5px;'>\n";
do{
$idchild=$row_menuchild['id'];
$parentchild=mysql_query("SELECT * FROM cat WHERE parent='$idchild'", $dbconn);
if(mysql_num_rows($parentchild)>0){
  if($collapse!="block"){$image="mplus.gif";}else{$image="mminus.gif";}
  echo "<img src=\"http://".$_SERVER['SERVER_NAME']."/editor/img/".$image."\" border=0 id=menuimg".$row_menuchild['id']." align=middle> <a href=\"http://".$_SERVER['SERVER_NAME'].$row_menuchild['path']."\"  class=\"childmenu\">".$row_menuchild['name']."</a><br>\n";}else{
echo "<img src=\"http://".$_SERVER['SERVER_NAME']."/editor/img/mvetka.gif\" border=0 align=middle> <a href=\"http://".$_SERVER['SERVER_NAME'].$row_menuchild['path']."\" class=\"childmenu\">".$row_menuchild['name']."</a><br>\n";}
menuchild($row_menuchild['id'],$table,$collapse);
}while($row_menuchild = mysql_fetch_assoc($menuchild));
mysql_free_result($menuchild);
echo "</div>\n"; 
 }
}

//Create expand menu
/*
function el_menut_expand($parent_path, $style, $display="none"){
global $database_dbconn;
global $dbconn;
global $_COOKIE, $_SERVER;
global $path;

if($parent_path!=''){
  mysql_select_db($database_dbconn, $dbconn);
  $query_menu = "SELECT * FROM cat WHERE `path` = '".$parent_path."' AND menu = 'Y'";
  $menu = mysql_query($query_menu, $dbconn) or die(mysql_error());
  $row_menu = mysql_fetch_assoc($menu);
}else{
  $row_menu['id']=0;
}
mysql_select_db($database_dbconn, $dbconn);
$query_menup = "SELECT * FROM cat WHERE parent = '".$row_menu['id']."' AND menu = 'Y'";
$menup = mysql_query($query_menup, $dbconn) or die(mysql_error());
$row_menup = mysql_fetch_assoc($menup);
echo '<ul id="'.$style.'">';
do{
  mysql_select_db($database_dbconn, $dbconn);
  $query_menuc = "SELECT * FROM cat WHERE parent = ".$row_menup['id']." AND menu = 'Y'"; 
  $menuc = mysql_query($query_menuc, $dbconn) or die(mysql_error());
  $row_menuc = mysql_fetch_assoc($menuc);
  
 if(mysql_num_rows($menuc)==0){
  
    if($path==$row_menup['path']){
      echo '
          <li class="current">'.$row_menup['name'].'</li>
        ';
    }else{
      echo '
          <li><a href="http://'.$_SERVER['SERVER_NAME'].$row_menup['path'].'">'.$row_menup['name'].'</a></li>
        ';
    }
 }else{
  if($_COOKIE['divmenu'.$row_menup['id']]=="Y"){$display="block";}else{$display="none";}
  if($_COOKIE['divmenu'.$row_menup['id']]==""){$display="block";}
  echo '
        <li id="li'.$row_menup['id'].'"><a onClick="showdiv(\''.$row_menup['id'].'\');">'.$row_menup['name'].'</a>';
  el_menut_expand_child($row_menup['id'], $style, $display, 1);
  echo '</li>';
 }
}while($row_menup = mysql_fetch_assoc($menup));
echo '<li id="last">&nbsp;</li>
</ul>
';
mysql_free_result($menup);
}


function el_menut_expand_child($parent_id, $style, $display='none', $key){
global $database_dbconn;
global $dbconn;
global $_COOKIE, $_SERVER;
global $path;

  mysql_select_db($database_dbconn, $dbconn);
  $query_menuc = "SELECT * FROM cat WHERE parent = ".$parent_id." AND menu = 'Y'"; 
  $menuc = mysql_query($query_menuc, $dbconn) or die(mysql_error());
  $row_menuc = mysql_fetch_assoc($menuc);
  
        if($_COOKIE['divmenu'.$parent_id]=="Y"){$display="block";}else{$display="none";}
        if($_COOKIE['divmenu'.$parent_id]==""){$display="none";}
        echo '
        <ul id="divmenu'.$parent_id.'" style="display:'.$display.'" />
        ';
        
  if(mysql_num_rows($menuc)>0){
    do{
      mysql_select_db($database_dbconn, $dbconn);
      $query_menuc1 = "SELECT * FROM cat WHERE parent = ".$row_menuc['id']." AND menu = 'Y'"; 
      $menuc1 = mysql_query($query_menuc1, $dbconn) or die(mysql_error());
      $row_menuc1 = mysql_fetch_assoc($menuc1);
      if($path==$row_menuc['path']){
        echo '
            <li class="child_current">'.$row_menuc['name'].'</li>
          ';
      }else{
        if(mysql_num_rows($menuc1)>0){
          echo '
            <li id="li'.$row_menuc['id'].'"><a onClick="showdiv(\''.$row_menuc['id'].'\');">'.$row_menuc['name'].'</a>
            ';
            el_menut_expand_child($row_menuc['id'], $style, $display, 1);
            echo "</li>";
        }else{
          echo '
            <li><a href="http://'.$_SERVER['SERVER_NAME'].$row_menuc['path'].'">'.$row_menuc['name'].'</a></li>
            ';
        }
      }
    }while($row_menuc = mysql_fetch_assoc($menuc));
    mysql_free_result($menuc);
  echo '<li id="last_inner">&nbsp;</li>
  </ul>';
  }
}
*/
function el_menut_expand($parent_path, $style, $display="none"){
global $database_dbconn;
global $dbconn;
global $_COOKIE, $_SERVER;
global $path;


if($parent_path!=''){
  mysql_select_db($database_dbconn, $dbconn);
  $query_menu = "SELECT * FROM cat WHERE `path` = '".$parent_path."' AND menu = 'Y' ORDER BY sort";
  $menu = mysql_query($query_menu, $dbconn) or die(mysql_error());
  $row_menu = mysql_fetch_assoc($menu);
}else{
  $row_menu['id']=0;
}
mysql_select_db($database_dbconn, $dbconn);
$query_menup = "SELECT * FROM cat WHERE parent = '".$row_menu['id']."' AND menu = 'Y' ORDER BY sort";
$menup = mysql_query($query_menup, $dbconn) or die(mysql_error());
$row_menup = mysql_fetch_assoc($menup);
//echo '<div id="'.$style.'">';
do{
  mysql_select_db($database_dbconn, $dbconn);
  $query_menuc = "SELECT * FROM cat WHERE parent = ".$row_menup['id']." AND menu = 'Y' ORDER BY sort"; 
  $menuc = mysql_query($query_menuc, $dbconn) or die(mysql_error());
  $row_menuc = mysql_fetch_assoc($menuc);
  
 if(mysql_num_rows($menuc)==0){
  
    if($path==$row_menup['path']){
      echo '
          <li><a href="#">'.$row_menup['name'].'</a></li>
        ';
    }else{
      echo '
          <li><a href="http://'.$_SERVER['SERVER_NAME'].$row_menup['path'].'">'.$row_menup['name'].'</a></li>
        ';
    }
 }else{
  if($_COOKIE['divmenu'.$row_menup['id']]=="Y"){$display="block";}else{$display="none";}
  if($_COOKIE['divmenu'.$row_menup['id']]==""){$display="block";}
  if($path==$row_menup['path']){$cl=' current';}//else{$cl='menu';}
  echo ' <li class="off'.$cl.'"><a href="http://'.$_SERVER['SERVER_NAME'].$row_menup['path'].'">'.$row_menup['name'].'</a>';
  el_menut_expand_child($row_menup['id'], $style, $display, 1);
  echo '</li>';
 }
}while($row_menup = mysql_fetch_assoc($menup));
//echo '</div>';
mysql_free_result($menup);
}


function el_menut_expand_child($parent_id, $style, $display='none', $key){
global $database_dbconn;
global $dbconn;
global $_COOKIE, $_SERVER;
global $path;

  mysql_select_db($database_dbconn, $dbconn);
  $query_menuc = "SELECT * FROM cat WHERE parent = ".$parent_id." AND menu = 'Y' ORDER BY sort"; 
  $menuc = mysql_query($query_menuc, $dbconn) or die(mysql_error());
  $row_menuc = mysql_fetch_assoc($menuc);
  
        if($_COOKIE['divmenu'.$parent_id]=="Y"){$display="block";}else{$display="none";}
        if($_COOKIE['divmenu'.$parent_id]==""){$display="none";}
        echo '
     <ul>
        ';
        
  if(mysql_num_rows($menuc)>0){
    do{
      mysql_select_db($database_dbconn, $dbconn);
      $query_menuc1 = "SELECT * FROM cat WHERE parent = ".$row_menuc['id']." AND menu = 'Y' ORDER BY sort"; 
      $menuc1 = mysql_query($query_menuc1, $dbconn) or die(mysql_error());
      $row_menuc1 = mysql_fetch_assoc($menuc1);
      if($path==$row_menuc['path']){
        echo '
           <li><a href="http://'.$_SERVER['SERVER_NAME'].$row_menuc['path'].'">'.$row_menuc['name'].'</a></li>
          ';
      }else{
        ($key>=1)?$hr='href="http://'.$_SERVER['SERVER_NAME'].$row_menuc['path'].'"':$hr='';
        if(mysql_num_rows($menuc1)>0){
          echo '
           <a '.$hr.'>'.$row_menuc['name'].'</a>
            ';// onClick="showdiv(\''.$row_menuc['id'].'\');"
            $key++;
            el_menut_expand_child($row_menuc['id'], $style, $display, $key);
            echo "</div>";
        }else{
          echo '
            <li><a href="http://'.$_SERVER['SERVER_NAME'].$row_menuc['path'].'">'.$row_menuc['name'].'</a></li>
            ';
        }
      }
    }while($row_menuc = mysql_fetch_assoc($menuc));
    mysql_free_result($menuc);
  echo '
  </ul>';
  }
}

function el_isParent($currPath, $fullPath){
	$fpArr=explode('/', $fullPath); 
	for($i=count($fpArr); $i>2; $i--){ 
		array_pop($fpArr);
		if($currPath==implode('/', $fpArr)) return true;
	}
}

//part of menu from specified path/////////////////////////////
function el_menupart($parent_path='', $parent_class='active', $curr_class='current', $template=''){
	global $database_dbconn, $parentPath;
	global $dbconn;
	global $path;
	//el_searchParent($parent_path);
	//$parent_path=($parent_path!='' && !el_hasChild($parent_path))?$parentPath:$parent_path;
	$pid=el_dbselect("SELECT id, parent FROM cat WHERE path='".$parent_path."'",0,$pid,'row');
	mysql_select_db($database_dbconn, $dbconn);
	if($pid['id']==19)$pid['id']=0;
	$query_menupart = "SELECT * FROM cat WHERE parent = '".$pid['id']."' AND menu='Y' ORDER BY sort ASC";
	$menupart = el_dbselect($query_menupart, 0, $menupart);
	$row_menupart = mysql_fetch_assoc($menupart);
	$totalRows_menupart = mysql_num_rows($menupart);
	$subClass='';
	if($totalRows_menupart>0){
		$c=0;
		do{
			$hasChild=el_hasChild($row_menupart['path'], 0, 'active');
			$subClass=($totalRows_menupart==($c+1))?' menu_last':'';
			if(el_isParent($row_menupart['path'], $path) || ($hasChild && $row_menupart['path']==$path)){
				$clas=" class='$parent_class$subClass'";
			}elseif($row_menupart['path']==$path){
				$clas=" class='$curr_class$subClass'";
			}else{
				$clas=(strlen($subClass)>0)?" class='menu_last'":'';
			}
			
			if($row_menupart['path']==''){$row_menupart['path']='http://'.$_SERVER['SERVER_NAME'];}
			
		  if($template==''){
			echo '<li><a href="'.$row_menupart['path'].'" '.$clas.'>'.$row_menupart['name'].'</a></li>'."\n";
		  }else{
		  	include $_SERVER['DOCUMENT_ROOT'].'/tmpl/menu/'.$template;
		  }
		  $c++;
		} while ($row_menupart = mysql_fetch_assoc($menupart));
	}else{
		if($pid['parent']!=0){
			$pp=el_dbselect("SELECT path FROM cat WHERE id=".$pid['parent'], 0, $pp, 'row', true); 
			el_menupart($pp['path'], $parent_class, $curr_class, $template);
		}
	}
	mysql_free_result($menupart);
}

//part of menu from specified path and specified template/////////////////////////////
function el_menupartft($parent_path='', $template='', $class='a', $altclass='active', $viewMode='div'){
global $database_dbconn;
global $dbconn;
global $path;
$pid='';
echo '<script language=javascript>
function mhr(obj){
  var ob=document.getElementById(obj);
  ob.className="'.$altclass.'";
}
function mhr1(obj){
  var ob=document.getElementById(obj);
  ob.className="'.$class.'";
}
</script>';
$pid=el_dbselect("SELECT id FROM cat WHERE path='".$parent_path."'",0, $pid,'row');
mysql_select_db($database_dbconn, $dbconn); 
($parent_path=='')?$pid['id']=0:$pid['id']=$pid['id'];
$query_menupart = "SELECT * FROM cat WHERE parent = '".$pid['id']."' AND menu='Y' ORDER BY sort ASC";
$menupart = mysql_query($query_menupart, $dbconn) or die(mysql_error());
$row_menupart = mysql_fetch_assoc($menupart);
$totalRows_menupart = mysql_num_rows($menupart);
if($totalRows_menupart>0){
  if($viewMode=='table'){
    echo "
    <TABLE id=menu cellSpacing=0 cellPadding=0>
    <TBODY>
    <TR>";
  }
  do {
    if($row_menupart['menu']!="N"){
      if($row_menupart['path']==$path){ 
        $clas=$altclass; $script='';
      }else{
        $clas=$class; $script='onMouseOver="mhr('.$row_menupart['id'].')" onMouseOut="mhr1('.$row_menupart['id'].')"';
      }
      if($row_menupart['path']==''){$row_menupart['path']='http://'.$_SERVER['SERVER_NAME'];}
      include $_SERVER['DOCUMENT_ROOT'].'/tmpl/menu/'.$template;
    }
  } while ($row_menupart = mysql_fetch_assoc($menupart));
  if($viewMode=='table'){
    echo "
    <TD class=$class>&nbsp;</TD></TR></TBODY></TABLE>";
  }
}
mysql_free_result($menupart);
}

//create bottom menu////////////////////////////////////////////
function el_bottommenu($divider){
global $database_dbconn;
global $dbconn;

mysql_select_db($database_dbconn, $dbconn);
$query_db_menu = "SELECT * FROM cat WHERE parent = 0 AND menu = 'Y' ORDER BY sort ASC";
$db_menu = mysql_query($query_db_menu, $dbconn) or die(mysql_error());
$row_db_menu = mysql_fetch_assoc($db_menu);
$totalRows_db_menu = mysql_num_rows($db_menu);
$count=0;
 do { 
 $count++;
    if($count==$totalRows_db_menu){$pdivider="";}else{$pdivider=$divider;}
    echo '<a href="http://'.$_SERVER['SERVER_NAME'].$row_db_menu['path'].'" class="blink">'.$row_db_menu['name'].'</a> '.$pdivider.' ';
 } while ($row_db_menu = mysql_fetch_assoc($db_menu)); 
mysql_free_result($db_menu);
}


//Create pop-up menu
function el_create_popup_menu(){
//$imenu=0;
$menu_vars='<SCRIPT LANGUAGE="JavaScript" SRC="http://'.$_SERVER['SERVER_NAME'].'/JSCookMenu.js"></SCRIPT>
<LINK REL="stylesheet" HREF="http://'.$_SERVER['SERVER_NAME'].'/theme.css" TYPE="text/css">
<SCRIPT LANGUAGE="JavaScript" SRC="http://'.$_SERVER['SERVER_NAME'].'/theme.js"></SCRIPT>
<SCRIPT LANGUAGE="JavaScript">var myMenu =[ _cmSplit, 
';

  function el_menuadmin1($menu_vars){//Parent items, first level only
  global $database_dbconn;
  global $dbconn;
  global $menu_vars;
  global $imenu;
  
  mysql_select_db($database_dbconn, $dbconn);
  $querymenutree = "SELECT * FROM cat WHERE parent=0 AND menu = 'Y' ORDER BY sort ASC"; 
  $menutree = mysql_query($querymenutree, $dbconn) or die(mysql_error());
  $row_menutree = mysql_fetch_assoc($menutree);
    do{
      $parent=$row_menutree['id'];
      $child=mysql_query("SELECT * FROM cat WHERE parent='$parent' AND menu = 'Y'", $dbconn);
      $childnum=mysql_num_rows($child);
      $imenu=0;
    $menu_vars.="['', '".$row_menutree['name']."', 'http://".$_SERVER['SERVER_NAME'].$row_menutree['path']."', '_self', '".$row_menutree['name']."'";

    menuadminchild1($parent, 'cat',$menu_vars, $childnum, 0, 0);
    $menu_vars.="], _cmSplit, \n";
    }while($row_menutree = mysql_fetch_assoc($menutree));
  mysql_free_result($menutree);
  return $menu_vars;
  }

    function menuadminchild1($parent, $table, $menu_vars, $childnum, $citem, $flag){//Child Items
    global $database_dbconn;
    global $dbconn;
    global $imenu;
    global $menu_vars;
    
    $querymenuchild = "SELECT * FROM cat WHERE parent='$parent' AND menu = 'Y' ORDER BY sort ASC";
    $menuchild = mysql_query($querymenuchild, $dbconn) or die(mysql_error());
    $row_menuchild = mysql_fetch_assoc($menuchild);
    $citems=mysql_num_rows($menuchild);
    $idchild1=$row_menuchild['id'];
      if($idchild1){//if item is exist...
      $menu_vars.=", \n";
      $imenu++;
       do{
        $citem++;
          $idchild=$row_menuchild['id'];
          $parentchild=mysql_query("SELECT * FROM cat WHERE parent='$idchild' AND menu = 'Y'", $dbconn);
          $childexist=mysql_fetch_assoc($parentchild);
          $childnumit=mysql_num_rows($parentchild);
          $childnum=$childnum+$childnumit;
          if(!empty($childexist['id'])){$new="";$flag=1;}else{$new="]";}
          mysql_free_result($parentchild);
        if($citem!=$citems){$dot=",";}else{$dot="";}
        if(($citem==$childnum)&&($flag>0)){$end="]";}
        //if(($citem==$childnum)&&(empty($childexist['id']))){$end="]";}
        $menu_vars.=" ['', '".$row_menuchild['name']."', 'http://".$_SERVER['SERVER_NAME'].$row_menuchild['path']."', '_self', '".$row_menuchild['name']."'".$new.$dot." \n".$end;
        menuadminchild1($row_menuchild['id'],$table, $menu_vars, $childnum, $citem, $flag);
       }while($row_menuchild = mysql_fetch_assoc($menuchild));
      mysql_free_result($menuchild);
      }
    return $menu_vars;
    }
$menu_vars.=el_menuadmin1($menu_vars);
$menu_vars.="]; </SCRIPT><DIV ID=myMenuID><SCRIPT LANGUAGE=\"JavaScript\"><!--
  cmDraw ('myMenuID', myMenu, 'hbr', cmThemePanel, 'ThemePanel');
--></SCRIPT></DIV>";
$file=fopen($_SERVER['DOCUMENT_ROOT']."/menu.inc",w);
if (fwrite($file,$menu_vars) === FALSE) {
      el_showalert("error","Не могу произвести запись в файл меню.") ;
   }
fclose($file); 
}

//show specified block////////////////////////////////////////////
function el_showblock($specpath){
global $database_dbconn;
global $dbconn;
mysql_select_db($database_dbconn, $dbconn);
$query_block = "SELECT * FROM content WHERE `path` = '$specpath'";
$block = mysql_query($query_block, $dbconn) or die(mysql_error());
$row_block = mysql_fetch_assoc($block);
echo $row_block['text'];
mysql_free_result($block);
}

//show specified block////////////////////////////////////////////

function el_showalert($mode, $alert_text, $unique_id=1){
switch ($mode){
 case "warning": $img="i_excel.gif"; break;
 case "quest": $img="i_quest.gif"; break;
 case "error": $img="i_stop.gif"; break;
 case "info": $img="i_info.gif"; break;
 }
 $uniq_path=str_replace('/', '', $_SERVER['PHP_SELF']);
 $uniq_path=str_replace('.', '', $uniq_path);
 
 if($_COOKIE['alert'.$uniq_path.$unique_id]=="Y" || $_COOKIE['alert'.$uniq_path.$unique_id]==""){$h='none'; $s='block';}else{$s='none'; $h='block';}
echo '
<script language=javascript>
function showhide_alert'.$uniq_path.$unique_id.'(){
  var h=document.getElementById("hide_alert'.$uniq_path.$unique_id.'");
  var s=document.getElementById("show_alert'.$uniq_path.$unique_id.'");
  if(s.style.display=="none"){
    s.style.display="block";
    h.style.display="none";
    document.cookie = "alert'.$uniq_path.$unique_id.'=Y; expires=Thu, 31 Dec 2020 23:59:59 GMT; path=/editor/;";
  }else{
    s.style.display="none";
    h.style.display="block";
    document.cookie = "alert'.$uniq_path.$unique_id.'=N; expires=Thu, 31 Dec 2020 23:59:59 GMT; path=/editor/;";
  }
}
</script>
<div id="hide_alert'.$uniq_path.$unique_id.'" onClick="showhide_alert'.$uniq_path.$unique_id.'()" style="display:'.$h.'">
<a href="javascript:void(0)"><img src="/editor/img/'.$img.'" align=left>&nbsp;Показать справку</a>
</div>
<table cellspacing=0 cellpadding=0 border=0 width="100%" class="help" id="show_alert'.$uniq_path.$unique_id.'" style="display:'.$s.'">
    <tr>
      <td width="7"><img height=7 alt="" src="/editor/img/inc_ltc.gif" width=7></td>
      <td background="/editor/img/inc_tline.gif"><img height=1 alt="" src="/editor/img/1.gif" width=1></td>
      <td width="7"><img height=7 alt="" src="/editor/img/inc_rtc.gif" width=7></td>
    </tr>
    <tr>
      <td width="7" background="/editor/img/inc_lline.gif"><img height=1 alt="" src="/editor/img/1.gif" width=1></td>
      <td valign=top style="background-color:#FFFFEC">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="10%" align="center" style="background-color:#FFFFEC" valign=top><img src="/editor/img/'.$img.'"></td>
      <td width="90%" style="background-color:#FFFFEC">'.$alert_text.'
      <br><br>
      <a href="javascript:void(0)" onClick="showhide_alert'.$uniq_path.$unique_id.'()">Скрыть справку</a>
      </td>
          </tr>
      </table></td>
      <td  width="7" background="/editor/img/inc_rline.gif"><img height=1 alt="" src="/editor/img/1.gif" width=1></td>
    </tr>
    <tr>
      <td width="7"><img height=7 alt="" src="/editor/img/inc_lbc.gif" width=7></td>
      <td background="/editor/img/inc_bline.gif"><img height=1 alt="" src="/editor/img/1.gif" width=1></td>
      <td width="7"><img height=7 alt="" src="/editor/img/inc_rbc.gif" width=7></td>
    </tr>
  </table>';
}

//Print all anchors from text of page/////////////////////////
function el_anchor ($string){
$pregh="|href=\"(.*)\"></A>|";
preg_match_all($pregh,$string,$tagsh); 
$preg="|<A title=\"(.*?)\" href=|";
preg_match_all($preg,$string,$tags); 
echo "<ul>";
$i=0;
  foreach ($tags[1] as $tmpcont) {
  echo "<li><a href=".$tagsh[1][$i].">".$tmpcont."</a></li>\n";
  $i++; 
  } 
echo "</ul>";
}

//Print all anchors from text of page/////////////////////////
function el_anchor_print ($url,$string){
$preg="|<A class=\"anchorlink \" title=\"(.*?)\" name|";
preg_match_all($preg,$string,$tags);
$pregh="|name=(.*?)></A>|";
preg_match_all($pregh,$string,$tagsh);
 
/*$tcount=sizeof($tags);
if($tcount==1){$end="u";}
if($tcount<5){$end="y";}
if($tcount>=5){$end="ae";}*/

//echo "<ul>";
if(strlen($tagsh[1][0])>3){echo "<span style='color:green;font-size:80%; font-weight:normal'>Найдены ".$tcount." якоря".$end.".</span>";} 
$i=0;
  foreach ($tags[1] as $tmpcont) {
  echo "<li id='li".$i."' onMouseOut='document.getElementById(\"li".$i."\").style.backgroundColor=\"#E7E7E7\"' onMouseOver='document.getElementById(\"li".$i."\").style.backgroundColor=\"#F7F7F7\"' style='cursor:pointer; color:blue;background-color:#E7E7E7'  onClick=\"kodlink('".str_replace("http://","",$url."/#".$tagsh[1][$i])."')\">".$tmpcont."</li>\n";
  $i++; 
  }
if($i==0){echo "<span style='color:red;font-size:80%; font-weight:normal'>Якоря не найдены.</span>";}
//echo "</ul>";
}

//Print horizontal menu
function el_hmenu(){
global $database_dbconn;
global $dbconn;
global $path;
echo '<table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr align="center" style="font-size:12px; font-weight:bold; font-family:Arial">';
mysql_select_db($database_dbconn, $dbconn);
$query_db_menu = "SELECT * FROM cat WHERE parent=0 AND menu='Y' ORDER BY sort ASC";
$db_menu = mysql_query($query_db_menu, $dbconn) or die(mysql_error());
$row_db_menu = mysql_fetch_assoc($db_menu);
$totalRows_db_menu = mysql_num_rows($db_menu);
$w=ceil(100/$totalRows_db_menu);
do { 
  if($row_db_menu['path']==$path){ echo '<td style="cursor:pointer" width="'.$w.'%" id="hline"><span  id="hlink">'.$row_db_menu['name'].'</span></td>';
  }else{
  echo '<a href="http://'.$_SERVER['SERVER_NAME'].$row_db_menu['path'].'" id="hlink"><td style="cursor:pointer" width="'.$w.'%" onMouseOver= id="hline"  onMouseOut= id="hline0" id="hline0">'.$row_db_menu['name'].'</td></a>';
  }
} while ($row_db_menu = mysql_fetch_assoc($db_menu));
            echo'</tr></table>';
}

//Print child menu in table
function el_hmenu_table($id_parent, $orient){
global $database_dbconn;
global $dbconn;
global $path;
if($orient=="vertical"){$linestart="<tr>"; $lineend="</tr>";}elseif($orient=="horizontal"){$linestart=""; $lineend="";}
if($id_parent>0){

mysql_select_db($database_dbconn, $dbconn);
$query_db_menup = "SELECT * FROM cat WHERE id=".$id_parent;
$db_menup = mysql_query($query_db_menup, $dbconn) or die(mysql_error());
$row_db_menup = mysql_fetch_assoc($db_menup);
if($row_db_menup['parent']>0){
$id_parent=$row_db_menup['parent'];}


mysql_select_db($database_dbconn, $dbconn);
$query_db_menu = "SELECT * FROM cat WHERE parent=".$id_parent." AND menu='Y' ORDER BY sort ASC";
$db_menu = mysql_query($query_db_menu, $dbconn) or die(mysql_error());
$row_db_menu = mysql_fetch_assoc($db_menu);
$totalRows_db_menu = mysql_num_rows($db_menu);
if($totalRows_db_menu>0){
echo '<table width="100%" border="0" cellpadding="3" cellspacing="5" align="center">
            ';
$c=0;
do { 
$c++;
  if($row_db_menu['path']==$path){ echo $linestart.'<a href="#" id="link"><td  width="10%" id="line">'.$row_db_menu['name'].'</td></a>'.$lineend;
  }else{
  echo $linestart.'<a href="http://'.$_SERVER['SERVER_NAME'].$row_db_menu['path'].'" id="link"><td width="10%" onMouseOver= id="line"  onMouseOut= id="line0" id="line0">'.$row_db_menu['name'].'</td></a>'.$lineend;
  }if(($c!=$totalRows_db_menu)&&($orient=="horizontal")){echo "<td>|</td>";}
} while ($row_db_menu = mysql_fetch_assoc($db_menu));
           
echo'</table>';} }}

//Print child menu in divs
function el_hmenu_divs($id_parent, $orient){
global $database_dbconn;
global $dbconn;
global $path;
if($orient=="vertical"){$linestart=""; $lineend="<br>";}elseif($orient=="horizontal"){$linestart=""; $lineend="";}
if($id_parent>0){

mysql_select_db($database_dbconn, $dbconn);
$query_db_menup = "SELECT * FROM cat WHERE id=".$id_parent;
$db_menup = mysql_query($query_db_menup, $dbconn) or die(mysql_error());
$row_db_menup = mysql_fetch_assoc($db_menup);
if($row_db_menup['parent']>0){
$id_parent=$row_db_menup['parent'];}

mysql_select_db($database_dbconn, $dbconn);
$query_db_menu = "SELECT * FROM cat WHERE parent=".$id_parent." AND menu='Y' ORDER BY sort ASC";
$db_menu = mysql_query($query_db_menu, $dbconn) or die(mysql_error());
$row_db_menu = mysql_fetch_assoc($db_menu);
$totalRows_db_menu = mysql_num_rows($db_menu);
if($totalRows_db_menu>0){
$c=0;
do { 
$c++;
  if($row_db_menu['path']==$path){ 
    echo $linestart.'<a href="'.$row_menu_child['path'].'" id="link"><div id="line">'.$row_db_menu['name'].'</div></a>'.$lineend;
    mysql_select_db($database_dbconn, $dbconn);
  $query_menu_child = "SELECT * FROM cat WHERE parent=".$row_db_menu['id']." AND menu='Y' ORDER BY sort ASC";
  $menu_child = mysql_query($query_menu_child, $dbconn) or die(mysql_error());
  $row_menu_child = mysql_fetch_assoc($menu_child);
  if(mysql_num_rows($menu_child)>0){
    do{
      echo $linestart.'<a href="http://'.$_SERVER['SERVER_NAME'].$row_menu_child['path'].'" id="link"><div onMouseOver= id="line"  onMouseOut= id="linec0" id="linec0">'.$row_menu_child['name'].'</div></a>'.$lineend;
    }while($row_menu_child = mysql_fetch_assoc($menu_child));
  }
  }else{
  echo $linestart.'<a href="http://'.$_SERVER['SERVER_NAME'].$row_db_menu['path'].'" id="link"><div onMouseOver= id="line"  onMouseOut= id="line0" id="line0">'.$row_db_menu['name'].'</div></a>'.$lineend;
  }if(($c!=$totalRows_db_menu)&&($orient=="horizontal")){echo " | ";}
} while ($row_db_menu = mysql_fetch_assoc($db_menu));
} }}

//Prepare cirillic date
function el_date($date){
if(substr_count($date, ' ')>0){
	$dateArr=explode(' ', $date);
	$date=$dateArr[0];
}
$year=strtok($date, "-");
$month=strtok("-");
$day=strtok("");
switch ($month) {
  case 1: $mont="января";
  break;
  case 2: $mont="февраля";
  break;
  case 3: $mont="марта";
  break;
  case 4: $mont="апреля";
  break;
  case 5: $mont="мая";
  break;
  case 6: $mont="июня";
  break;
  case 7: $mont="июля";
  break;
  case 8: $mont="августа";
  break;
  case 9: $mont="сентября";
  break;
  case 10: $mont="октября";
  break;
  case 11: $mont="ноября";
  break;
  case 12: $mont="декабря";
  break;
  }
echo $day." ".$mont." ".$year."г.";
}

function el_date1($date){
if(substr_count($date, ' ')>0){
	$dateArr=explode(' ', $date);
	$date=$dateArr[0];
}
$year=strtok($date, "-");
$month=strtok("-");
$day=strtok("");
switch ($month) {
  case 1: $mont="января";
  break;
  case 2: $mont="февраля";
  break;
  case 3: $mont="марта";
  break;
  case 4: $mont="апреля";
  break;
  case 5: $mont="мая";
  break;
  case 6: $mont="июня";
  break;
  case 7: $mont="июля";
  break;
  case 8: $mont="августа";
  break;
  case 9: $mont="сентября";
  break;
  case 10: $mont="октября";
  break;
  case 11: $mont="ноября";
  break;
  case 12: $mont="декабря";
  break;
  }
return $day." ".$mont." ".$year."г. ".$dateArr[1];
}

function el_date_capt($datear, $mode){
  $day=$datear['day'];
  $mont=$datear['mont'];
  $year=$datear['year'];
  
  if($mode=="full"){
  switch ($mont) {
    case 1: $mont="января";
    break;
    case 2: $mont="февраля";
    break;
    case 3: $mont="марта";
    break;
    case 4: $mont="апреля";
    break;
    case 5: $mont="мая";
    break;
    case 6: $mont="июня";
    break;
    case 7: $mont="июля";
    break;
    case 8: $mont="августа";
    break;
    case 9: $mont="сентября";
    break;
    case 10: $mont="октября";
    break;
    case 11: $mont="ноября";
    break;
    case 12: $mont="декабря";
    break;
    }
  echo $day." ".$mont." ".$year."г.";
  }else{  
   if($day<10){$day='0'.$day;}
   if($mont<10){$mont='0'.$mont;}
  echo $day.".".$mont.".".$year."г.";
  }
}



//Create Broadcramble///////////////////////////////
function el_broadcramble($cat_number, $c=0){
global $path;
global $database_dbconn;
global $dbconn;

mysql_select_db($database_dbconn, $dbconn);
$query_listbp = "SELECT * FROM cat WHERE id = '".$cat_number."'";
$listbp = mysql_query($query_listbp, $dbconn) or die(mysql_error());
$row_listbp = mysql_fetch_assoc($listbp);
  $arr=array();
  if(($path==$row_listbp['path'])&&(strlen($row_listbp['name'])>0)){
    $newar=$row_listbp['name'];
  }elseif(strlen($row_listbp['name'])>0){
    if($row_listbp['id']==1){
      $row_listbp['path']="http://".$_SERVER['SERVER_NAME'];
    }
    $newar="<a href='".$row_listbp['path']."'>".$row_listbp['name']."</a> &raquo; ";
  }
/*if($row_listbp['id']==1){
  $newar="<strong>ГЛАВНАЯ</strong>";
}elseif($row_listbp['id']==1 && $row_listbp['parent']==1){

}*/
  //array_push($arr, $newar);

  if($row_listbp['id']!=0){
    array_push($arr, $newar);
    $c++;
    el_broadcramble($row_listbp['parent'], $c);
  }
  rsort($arr);
  for($i=0; $i<count($arr); $i++){
    echo $arr[$i];
  }
  
}

function el_broadcramble_editor($cat_number, $c=0){
global $path, $_GET;
global $database_dbconn;
global $dbconn;

mysql_select_db($database_dbconn, $dbconn);
$query_listbp = "SELECT * FROM cat WHERE id = '".$cat_number."'";
$listbp = mysql_query($query_listbp, $dbconn) or die(mysql_error());
$row_listbp = mysql_fetch_assoc($listbp);
  $arr=array();
  if($_GET['cat']==$row_listbp['id']){
    $newar="<span title='".$row_listbp['path']."'>".$row_listbp['name']."</span>";
  }else{
    $newar="<a title='".$row_listbp['path']."' href='editor.php?cat=".$row_listbp['id']."'>".$row_listbp['name']."</a> &raquo; ";
  }

  if($row_listbp['id']!=0){
    array_push($arr, $newar);
    $c++;
    el_broadcramble_editor($row_listbp['parent'], $c);
  }
  rsort($arr);
  for($i=0; $i<count($arr); $i++){
    echo $arr[$i];
  }
  
}

function el_Printbroadcramble(){
  global $row_dbcontent;
  $cat=$row_dbcontent['cat'];
  $parent=el_dbselect("SELECT parent FROM cat WHERE id='".$cat."'", 0, $parent, 'row');
  if($parent['parent']!=0){
    echo '<div class="breadcrump">';
	el_broadcramble($cat);
	echo '</div>';
  }
}

function el_calendNews($url){
  el_calendar('news', $url);
}

function el_calendArticles($url){
  el_calendar('articles', $url);
}

function el_calendCatalog($table, $url, $year_field){
  el_calendar('catalog_'.$table.'_data', $url, 'field'.$year_field, '', '', 'one');
}


function el_calendar($table, $calendar_open_url='', $year_field='year', $month_field='mont', $day_field='day', $mode='sep'){
  global $database_dbconn, $dbconn, $_GET;
  el_strongcleanvars1();
  $cal_dayarr=array();
  $cal_montarr=array();
  $cal_yeararr=array();
  $cal_montarrOne=array();
  $cal_dayarrOne=array();
  
  (isset($_GET['year']))?$user_year=$_GET['year']:$user_year=date("Y");
  (isset($_GET['month']))?$user_mont=$_GET['month']:$user_mont=date("m");
  
  mysql_select_db($database_dbconn, $dbconn);
  $query_result = "select $year_field from $table";
  $result = mysql_query($query_result, $dbconn) or die(mysql_error());
  $row_cal = mysql_fetch_assoc($result);
  
  //mode='sep'
  if($mode=='sep'){
    do{
      if(!in_array($row_cal[$year_field], $cal_yeararr)){
        array_push($cal_yeararr, $row_cal[$year_field]);
      }
    }while($row_cal = mysql_fetch_assoc($result));
    mysql_free_result($result);
    
    mysql_select_db($database_dbconn, $dbconn);
    $query_result = "select $month_field from $table where $year_field='".$user_year."'";
    $result = mysql_query($query_result, $dbconn) or die(mysql_error());
    $row_cal = mysql_fetch_assoc($result);
    
    do{
      if(!in_array($row_cal[$month_field], $cal_montarr)){
        array_push($cal_montarr, $row_cal[$month_field]);
      }
    }while($row_cal = mysql_fetch_assoc($result));
    mysql_free_result($result);
    
    mysql_select_db($database_dbconn, $dbconn);
    $query_result = "select $day_field from $table where $year_field='".$user_year."' AND $month_field='".$user_mont."'";
    $result = mysql_query($query_result, $dbconn) or die(mysql_error());
    $row_cal = mysql_fetch_assoc($result);
    
    do{
      if(!in_array($row_cal[$day_field], $cal_dayarr)){
        array_push($cal_dayarr, $row_cal[$day_field]); 
      }
    }while($row_cal = mysql_fetch_assoc($result));
    mysql_free_result($result);
  
  //mode='one'
  }elseif($mode=='one'){
    do{
      $date=array();
      $date=explode(',', $row_cal[$year_field]);
      for($a=0; $a<count($date); $a++){
        $day[$a]=strtok($date[$a], "-");
        $month[$a]=strtok("-");
        $year[$a]=strtok("");
        if(!in_array($year[$a], $cal_yeararr)){
          $cal_yeararr[]=$year[$a];
        }
        if(!@in_array($month[$a], $cal_montarrOne[$year[$a]]) && substr_count($row_cal[$year_field], $month[$a].'-'.$year[$a])>0){
          $cal_montarrOne[$year[$a]][]=$month[$a];
          sort($cal_montarrOne[$year[$a]]);
        }
        if(!in_array($day[$a], $cal_dayarr) && substr_count($row_cal[$year_field], $day[$a].'-'.$month[$a].'-'.$year[$a])>0){
          $cal_dayarrOne[$year[$a]][$month[$a]][]=$day[$a];
          sort($cal_dayarrOne[$year[$a]][$month[$a]]);
        }
      }
    }while($row_cal = mysql_fetch_assoc($result));
    mysql_free_result($result);
  }
  sort($cal_yeararr); 
  sort($cal_montarr);
  
  include $_SERVER['DOCUMENT_ROOT']."/editor/e_modules/calendar.php";
}

//Create menu-tabs from array
function el_tabs($arr, $method, $mode, $first=''){
global $_POST, $_GET;
echo '
<script language=javascript>
function MM_displayStatusMsg(msgStr) { 
  status=msgStr;
  document.MM_returnValue = true;
}
</script>
<table height="22" border="0" cellpadding="5" cellspacing="0">
  <tr>
    <td width="33%" valign="bottom">';
$i=0;
while(list($mode_var, $title)=each($arr)){
  $i++;
  if(strlen($first)>0 && strlen($method[$mode])==0){
    $default=$first;
  }
  if($method[$mode]==$mode_var || $mode_var==$default){
    echo '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="ftab">
      <tr><td><img src="/editor/img/tab_lside_active.gif" width="3" height="21"></td>
      <td width="100%" align="center">
    '.$title.' </td>
      <td><img src="/editor/img/tab_rside_active.gif" width="3" height="21"></td></tr></table>';
  }else{
    echo '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="stab">
      <tr>
         <td><img src="/editor/img/tab_lside_inactive.gif" width="3" height="19"></td><a href="?'.$mode.'='.$mode_var.'"><td width="100%" align="center" onMouseOver="MM_displayStatusMsg(\'\');return document.MM_returnValue" onMouseOut="MM_displayStatusMsg(\'\');return document.MM_returnValue">'.$title.'</td>
         </a><td><img src="/editor/img/tab_rside_inactive.gif" width="3" height="19"></td>
      </tr>
    </table>';
  }
  if($i!=count($arr)){
    echo '</td>
    <td width="33%" height="21" valign="bottom">';
  }
}
echo '</td>
  </tr>
</table>';
}

function el_createselect($name, $params, $data, $separator, $selected=0.5){
  echo '<select name="'.$name.'" '.$params.'>
  ';
  if($selected==0.5){
    echo '<option> </option>
    ';
  }
  $opt=explode($separator, $data);
  for($i=0; $i<count($opt); $i++){
    ($selected==$i)?$sel=' selected':$sel='';
    echo '<option'.$sel.'>'.$opt[$i].'</option>
    ';
  }
  echo '</select>';
}

function el_fileSelect($name, $params, $path, $ext, $selected, $count=0){
  $path=$_SERVER['DOCUMENT_ROOT'].str_replace('//', '/', $path);
  $handle = opendir($path);
  if($count==0){
	  echo '<select name="'.$name.'" '.$params.'>
		  <option> </option>
		';
  }
 while ( false !== ($file = readdir($handle)) ) {
    if ( ($file !== ".") && ($file !== "..") ) {
       preg_match("'^(.*)\.(.*)$'i", $file, $exten);
       if(is_dir($path.'/'.$file)){
          el_fileSelect($name, $params, $path.'/'.$file.'/', $ext, $selected, ++$count); 
		  $count=0;
       }elseif ( is_file($path.'/'.$file) && strtolower($exten[2])==$ext) {
        ($selected==$file)?$sel=' selected':$sel='';
        echo '<option'.$sel.'>'.$file.'</option>
        ';
      }
    }
  }
  if($count==0){ 
  	echo '</select>';
  }
   closedir($handle);
}

function el_pageprint($var, $mode='print'){
global $row_dbcontent, $_GET;
  if(strlen($row_dbcontent[$var])>0){
    if(strlen($_GET['highlight'])>0){
		$_GET['highlight']=strip_tags(trim(urldecode($_GET['highlight'])));
		$row_dbcontent[$var]=str_replace($_GET['highlight'], 
		'<span style="background-color:yellow">'.$_GET['highlight'].'</span>', $row_dbcontent[$var]);
	}
	if(isset($_GET['id']) && $var=='text'){
      return false;
    }else{
      if($mode=='print'){
        echo $row_dbcontent[$var];
      }elseif($mode=='return'){
        return $row_dbcontent[$var];
      }
    }
  }else{
    return false;
  }
}

function el_htext($text){
global $_GET;
    if(strlen($_GET['highlight'])>0){
		$_GET['highlight']=strip_tags(trim(urldecode($_GET['highlight'])));
		$text=str_replace($_GET['highlight'], 
		'<span style="background-color:yellow">'.$_GET['highlight'].'</span>', $text);
	}
    return $text;
}


function el_infoblock($id, $mode='print'){
  global $row_dbcontent;
  $t=el_dbselect("SELECT text FROM infoblocks WHERE id='".$id."' AND (FIND_IN_SET('".$row_dbcontent['id']."', pages)>0 OR permanent=1)", 0, $t, 'row');
  if($mode=='print'){
    echo $t['text'];
  }else{
    return $t['text'];
  }
}

function el_printanons($table, $sort, $maxRows, $template, $url){
  $a=el_dbselect('SELECT * FROM ' . $table . $sort, $maxRows, $a);
  $temp=$_SERVER['DOCUMENT_ROOT']."/tmpl/".$template;
  $an=mysql_fetch_assoc($a);
  if(strlen($template)>0 && file_exists($temp)){
    do{
      include $temp;
    }while($an=mysql_fetch_assoc($a));
  }else{
    echo "Не установлен в настройках модуля или не найден шаблон отображения строк.";
  }
}

function el_printanonsFromCache($table, $sort, $maxRows, $template, $url='', $parent_id=''){
	global $lastNews;
	$sub=$cacheStr='';
	$cacheDir=$_SERVER['DOCUMENT_ROOT'].'/editor/cache/catalogs';
	$cacheFile=$cacheDir.'/anons'.$parent_id.'.html';
	if(!is_dir($cacheDir))mkdir($cacheDir, 0777);
	if(!file_exists($cacheFile)){
		if($parent_id!='' && substr_count($table, 'catalog')>0){
			$p=el_dbselect("SELECT id FROM cat WHERE parent=".intval($parent_id), 0, $p);
			if(mysql_num_rows($p)>0){
				$subSql=array();
				while($rp=mysql_fetch_assoc($p)){
					$subSql[]=' cat='.$rp['id'];
				}
				$sub=' OR '.implode(' OR ', $subSql);
			}
		}
		$order=(strlen($sort)>0)?' ORDER BY '.$sort:''; 
		$a=el_dbselect('SELECT * FROM ' . $table.$order, $maxRows, $a);//$sub., 'result', true, true
		$temp=$_SERVER['DOCUMENT_ROOT'].$template;
		$an=mysql_fetch_assoc($a); 
		if(mysql_num_rows($a)>0){
			if(strlen($template)>0 && file_exists($temp)){ 
				$c=0;
				do{
					ob_start();
					if($url==''){
						$url=''; 
						$u=el_dbselect("SELECT path FROM cat WHERE id=".intval($an['cat']), 0, $u, 'row');
						$url=$u['path'].'/';
						$set_url=1;
					}
					include($temp);
					$cacheStr.=ob_get_contents();
					ob_end_clean();
					if($set_url==1)$url='';
					$c++; 
				}while($an=mysql_fetch_assoc($a));
			}else{
			  $cacheStr.="Не установлен в настройках модуля или не найден шаблон отображения строк.";
			}
		}else{
			//$cacheStr.='Пока ничего нет.';
		} 
		file_put_contents($cacheFile, $cacheStr);
		include $cacheFile;
	}else{
		include $cacheFile;
	}
}

function el_makePreview($row_catalog, $img_field, $hsize, $vsize, $prefix='small_'){
	if(is_file($_SERVER['DOCUMENT_ROOT'].$row_catalog[$img_field])){
		$file_name_arr=explode('/', $row_catalog[$img_field]);
		$file_name=$file_name_arr[count($file_name_arr)-1];
		$ext_arr=explode('.', $file_name);
		$file_name=$ext_arr[count($ext_arr)-2];
		$file_ext=strtolower('.'.$ext_arr[count($ext_arr)-1]);
		if(!is_file($_SERVER['DOCUMENT_ROOT'].'/images/'.$prefix.$file_name.$file_ext)){
			el_resize_images($_SERVER['DOCUMENT_ROOT'].$row_catalog[$img_field], el_translit($file_name.$file_ext), $hsize, $vsize, $prefix);
			$img="/images/".el_translit($prefix.$file_name.$file_ext);
		}else{
			$img='/images/'.$prefix.$file_name.$file_ext;
		}
	}	
	return $img;
}

function el_anonsNews($maxRows, $template, $url){
  el_printanons('news', $maxRows, $template, $url);
}

function el_anonsArticles($maxRows, $template, $url){
  el_printanons('articles', $maxRows, $template, $url);
}

function el_anonsCatalog($table, $url, $maxRows, $template){
  $a=el_dbselect("SELECT * FROM catalog_".$table."_data", $maxRows, $a);
  $row_catalog=mysql_fetch_assoc($a);
  $t=el_dbselect("SELECT list, 1bgc, 2bgc FROM catalog_templates WHERE id='".$template."'", 0, $t, 'row');
  do{
    ($bgcolor==$t['1bgc'])?$bgcolor=$t['2bgc']:$bgcolor=$template_row['1bgc'];
    eval(parse_template($t['list'], $row_catalog['filename'], $bgcolor, $url));
  }while($row_catalog=mysql_fetch_assoc($a));
}

function el_pagemodule(){
global $row_dbcontent, $dbchildmenu, $catalog_id;
  if (strlen($row_dbcontent['kod'])>0){
    $modulePath=el_dbselect("SELECT path FROM modules WHERE `type`='".$row_content['kod']."'", 0, $modulePath, 'row');
	switch (substr($row_dbcontent['kod'], 0, 7)){
      case "catalog":$catalog_id=str_replace("catalog","",$row_dbcontent['kod']);
               $module=$_SERVER['DOCUMENT_ROOT']."/modules/catalog.php";
               break;
      default:       
	  if(substr($row_dbcontent['kod'], 0, 5)=='forms'){
	  	$module=$_SERVER['DOCUMENT_ROOT']."/modules/forms.php";
        $form_id=str_replace("forms","",$row_dbcontent['kod']);
	  }else{
	  	$module=$_SERVER['DOCUMENT_ROOT']."/modules/".$row_dbcontent['kod'].".php";
	  }
    }
    if(is_file($module)){
      include $module;
    }else{
      echo"<h5>Модуль не установлен.</h5>";
    }
  }
}

function el_pagemenu($cols=2){
global $totalRows_dbchildmenu, $row_dbchildmenu, $row_dbcontent, $dbchildmenu;
  if($totalRows_dbchildmenu>0){
    echo "<table class='normal_tbl' align='center' cellspacing=6 width=98%><tr><td valign=top><ul>";
    $counter_child=0;
    do{
      $counter_child++;
      if($counter_child==ceil($totalRows_dbchildmenu/($cols?$cols:1))){
        //$counter_child=0;
        $trc="</ul></td><td valign=\"top\"><ul>";
      }else{
        $trc="";
      }
      echo "<li><a href='".$row_dbchildmenu['path']."'>".$row_dbchildmenu['name']."</a></li>".$trc;
    }while($row_dbchildmenu = mysql_fetch_assoc($dbchildmenu));
    echo "</ul></td></tr></table>";
  }
}

function el_hasChild($path, $id=0, $mode='all'){
  $p=array();
  if($path=='0'){
	$p['id']=$id; 
  }else{
  	$p=el_dbselect("SELECT id FROM cat WHERE path='$path'", 0, $p, 'row');
  }
  $m=el_dbselect("SELECT id FROM cat WHERE parent='".$p['id']."'".(($mode!='all')?" AND menu='Y'":''), 0, $m); 
  if(mysql_num_rows($m)>0){
    return true;
  }else{
    return false;
  }
}

function el_writeEvent($path, $id){
  if(el_hasChild($path)){
    echo "onmouseover=\"toggleEl('".$id."')\" onmouseout=\"toggleEl('".$id."')\"";
  }
}

$parentPath='';
function el_searchParent($path, $count=0){
  global $parentPath;
  $s=el_dbselect("SELECT id, parent, path FROM cat WHERE `path`='$path'", 0, $s, 'row');// AND menu='Y'
  //if($s['parent']!=0){
    $p=el_dbselect("SELECT path FROM cat WHERE id=".$s['parent'], 0, $s, 'row');//." AND menu='Y'"
	/*if($count<2)
	el_searchParent($p['path'], $count); 
  }else{*/
    $parentPath=$p['path'];
    return true;
  //}
}


function el_popupmenuOLD($path1, $id, $lang='', $count=0){
  global $parentPath;
  if($path1!=''){
    el_searchParent($path1);
    ($lang=='')?$path=$parentPath:$path=$lang;
    $p=el_dbselect("SELECT id, parent FROM cat WHERE path='$path'", 0, $p, 'row');
    $pid=$p['id'];
    $par=$p['id'];
  }else{
    $pid=0;
    $par=19;
  } 

  $m=el_dbselect("SELECT id, name, path FROM cat WHERE parent='".$par."' AND menu='Y'", 0, $m);
  $rm=mysql_fetch_assoc($m);
  $hasChild=0;
  (el_hasChild($rm['path']))?$hasChild=1:$hasChild=0;
  ($count>0)?$co=1:$co='';
  if(mysql_num_rows($m)>0){
    do{
      ($rm['path']=='' || $rm['path']=='/')?$rm['path']='http://'.$_SERVER['SERVER_NAME']:$rm['path']=$rm['path'];
      (el_hasChild($rm['path']))?$hasChild=1:$hasChild=0;
      ($path1==$rm['path'])?$licl='class="active"':$licl='';
      if($hasChild==1){
        echo "<li $licl ><a href='".$rm['path']."'>".$rm['name']." &raquo;</a>\n";
        echo "</li>\n";
      }else{
        echo "<li $licl><a href='".$rm['path']."'>".$rm['name']."</a></li>\n";
      }
      
    }while($rm=mysql_fetch_assoc($m));
    echo "</ul>\n";
  }
}

function el_popupmenu($path1, $id, $lang='', $count=0){
  global $parentPath;
  if($path1!=''){
    el_searchParent($path1);
    ($lang=='')?$path=$parentPath:$path=$lang;
    $p=el_dbselect("SELECT id, parent FROM cat WHERE path='$path'", 0, $p, 'row');
    $pid=$p['id'];
    $par=$p['id'];
  }else{
    $pid=0;
    $par=19;
  }
  $m=el_dbselect("SELECT id, name, path FROM cat WHERE parent='0' AND  `left`=1 ORDER BY sort", 0, $m);
  $rm=mysql_fetch_assoc($m);
  if(mysql_num_rows($m)>0){
    do{
      if($rm['path']=='' || $rm['path']=='/'){
      $pa='http://'.$_SERVER['SERVER_NAME'];
    $rm['id']=19;
    }else{
      $pa=$rm['path'];
    $rm['id']=$rm['id'];
    }
    ($path1==$rm['path'])?$licl='class="active"':$licl='';
      if((el_hasChild($path1) && $path1==$rm['path']) || $rm['path']==$parentPath){ 
        echo "<li $licl ><a href='".$pa."'>".$rm['name']." &raquo;</a></li>\n";
    $c=el_dbselect("SELECT id, name, path FROM cat WHERE parent='".$par."' AND menu='Y' AND `left`=1 ORDER BY sort", 0, $c); 
    $rc=mysql_fetch_assoc($c);
    if(mysql_num_rows($c)>0){
      do{
        ($rc['path']==$path1)?$slicl='class="subactive"':$slicl='class="subitem"';
        echo "<li $slicl><a href='".$rc['path']."'>".$rc['name']."</a>\n";
      }while($rc=mysql_fetch_assoc($c));
    }
      }else{
        echo "<li $licl><a href='".$pa."'>".$rm['name']."</a></li>\n";
      }
      
    }while($rm=mysql_fetch_assoc($m));
    echo "</ul>\n";
  }
}

function el_createMenuArray($parentId=0){
	$menuArr=array();
	$m=el_dbselect("SELECT * FROM cat WHERE parent=$parentId ORDER BY sort ASC", 0, $m);
	$rm=mysql_fetch_assoc($m);
	if(mysql_num_rows($m)>0){
		do{
			$menuArr[$rm['id']]['name']=$rm['name'];
			$c=el_dbselect("SELECT * FROM cat WHERE parent=".$rm['id'], 0, $c);
			if(mysql_num_rows($c)>0){
				$menuArr[$rm['id']]['subitems']=el_createMenuArray($rm['id']);
			}
		}while($rm=mysql_fetch_assoc($m));
	}
	return $menuArr;
}

function el_array2string($array){
	$str='';
	while(list($key, $val)=each($array)){
		$str.="'$key'=>array('name'=>'$val[name]'\n";
		if($val['subitems']){
			$str.=",\n'subitems'=>array(\n".el_array2string($val['subitems'])."),\n";
		}
		$str.="),\n";
	}
	return $str;
}

function el_Menu2Cache(){
	$cacheFile=$_SERVER['DOCUMENT_ROOT'].'/editor/cache/menu/array.php';
	$menu_array=el_createMenuArray(0);
	reset($menu_array);
	$cachedata="<? \$menu_array=array(\n".el_array2string($menu_array).") ?>"; 
	@chmod($_SERVER['DOCUMENT_ROOT'].'/editor/cache/menu/', 0777);
	$cf=@fopen($cacheFile, 'w')
      or die('Не удается запись массива меню в кэш.');
    fputs($cf, $cachedata);
    fclose($cf);
    @chmod($cacheFile, 0777);
}

function el_getMenuCache($id, $class){
	$cacheFile=$_SERVER['DOCUMENT_ROOT'].'/editor/cache/menu/array.php';
	if(!file_exists($cacheFile))el_Menu2Cache();
	include_once $cacheFile;
	el_popupMenuFromCache($menu_array, $id, $class);
}

function el_popupMenuFromCache($array, $id, $class, $count=0){
	echo '<ul'.(($count==0)?' id="'.$id.'" class="'.$class.'"':'').'>';
	while(list($key, $val)=each($array)){
		echo '<li><a href="/editor/editor.php?cat='.$key.'" target="Main">'.$val['name'].'</a>';
		if($val['subitems'])el_popupMenuFromCache($val['subitems'], $id, $class, ++$count);
		echo '</li>'."\n";
	}
	echo '</ul>';
}


function el_pageSelect($path, $current, $count=0, $level=0){
  $p=el_dbselect("SELECT id, parent FROM cat WHERE path='$path'", 0, $p, 'row');
  if($path==''){
    ($count>0)?$par=$p['id']:$par=$p['parent'];
  }else{
    ($count>0)?$par=$p['id']:$par=$p['parent'];
  } 
  $m=el_dbselect("SELECT name, path FROM cat WHERE parent='".$par."' ORDER BY sort", 0, $m);
  $rm=mysql_fetch_assoc($m);
  if($count==0){
    $co='style="background-color:#EBEBEB"';
    $prename='';
  }else{
    $co='';
    $prename=str_repeat('&#8211;&nbsp;', $level);
  }
  if(mysql_num_rows($m)>0){
    do{
      ($rm['path']=='' || $rm['path']=='/')?$pat='http://'.$_SERVER['SERVER_NAME']:$pat=$rm['path'];
      ($current==$rm['path'] && $current!='')?$sel='selected':$sel='';
      echo "<option $co value='".$pat."' $sel>$prename".$rm['name']."</option>\n";
      if(el_hasChild($rm['path']) && $rm['path']!=$path){
        el_pageSelect($rm['path'], $current, ++$count, ++$level);
        $level=0;
      }
    }while($rm=mysql_fetch_assoc($m));
  }
}

function el_catalogSelect($catalog_id, $current, $cat=0, $count=0, $level=0, $parentId=0, $scriptId=''){
    $postScript='';
    if($level==0){
        $scriptId=el_genpass();
        echo '<script type="text/javascript">
  function expandTree'.$scriptId.'(obj, currId){
	  var display=$(".childCats"+currId).css("display");
	  if(display=="none"){
		  $(".childCats"+currId).show();
		  $(obj).children("img").attr("src","/editor/img/minus.gif");
	  }else{
		  $(".childCats"+currId).hide();
		  $(obj).children("img").attr("src","/editor/img/plus.gif");
	  }
	  return false;
  }
  </script>';
    }
    if($count==0){
        $c=el_dbselect("SELECT cat FROM content WHERE kod='$catalog_id' AND caption<>'Поиск по сайту' ORDER BY cat ASC", 0, $c, 'row'/*, true, true*/);
        $querycat=" AND cat.id!=".$c['cat'];
    }else{
        $querycat=" AND cat.id!=$cat";
    }
    if($catalog_id!='catalogpub')$querycat='';
    $p=el_dbselect("SELECT cat.id AS id, cat.parent AS parent, content.kod AS kod
   FROM cat, content WHERE cat.id=content.cat AND content.kod='$catalog_id' $querycat
   ORDER BY cat.id ASC", 0, $p, 'row'/*, true, true*/);//

    $par=($count>0)?$parentId:$p['parent'];
    $m=el_dbselect("SELECT cat.id AS id, cat.name AS name, cat.path AS path FROM cat, content WHERE cat.id=content.cat AND cat.parent='".$par."' AND content.kod='$catalog_id' ORDER BY sort", 0, $m, 'result'/*, true, true*/);

    if(mysql_num_rows($m)>0){
        $rm=mysql_fetch_assoc($m);
        $hasChild=false;
        do{
            $sel=($current==$rm['id'])?'checked=checked':'';
            if($rm['path']!='' && el_hasChild($rm['path'])){
                $hasChild=true;
                $plus='<a href="#" class="expandCats" id="plus'.$rm['id'].'" onclick="return expandTree'.$scriptId.'(this, '.$rm['id'].')" title="Показать/скрыть подразделы"><img src="/editor/img/plus.gif"></a> ';
            }
            echo "<div".(($level>0)?' class=\'hide childCats'.$par.'\'':'').">$plus<label for='page".$rm['id']."' $co>".str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $level)."<input type=checkbox name='pages[]' id='page".$rm['id']."' value='".$rm['id']."' $sel> ".$rm['name']."</label></div>\n";
            if($level > 0){
                if($sel=='checked=checked'){
                    $postScript='<script type="text/javascript">
		  expandTree'.$scriptId.'($("#plus'.$parentId.'"), '.$parentId.');
		  var position = $("#page'.$rm['id'].'").offset();
		  $("#catSelect").scrollTop(position.top-420);
		  </script>';
                }
            }
            if($hasChild){
                el_catalogSelect($catalog_id, $current, $rm['id'], ++$count, ++$level, $rm['id'], $scriptId);
                $level=0;
            }
        }while($rm=mysql_fetch_assoc($m));
    }
    if(strlen($postScript)>0)echo $postScript;
}


function el_allPages($path, $current, $permanent, $count=0, $level=0){
  $p=el_dbselect("SELECT id, parent FROM cat WHERE path='$path'", 0, $p, 'row');
  if($path==''){
    $par=($count>0)?$p['id']:$p['parent'];
  }else{
    $par=($count>0)?$p['id']:$p['parent'];
  }
  $m=el_dbselect("SELECT id, name, path FROM cat WHERE parent='".$par."' ORDER BY sort", 0, $m);
  $rm=mysql_fetch_assoc($m);
  if($count==0){
    $co='style="background-color:#EBEBEB; display:block; margin:0px"';
    $prename=$end='';
  }else{
    $co='';
	$end='<br>';
    $prename=str_repeat('&#8211;&nbsp;', $level);
  }
  if(mysql_num_rows($m)>0){
    do{
      if($permanent==1){
	  	$sel='checked=checked disabled=true';
	  }else{
	  	$sel=(in_array($rm['id'], $current) && $current!='')?'checked=checked':'';
	  }
      echo "$prename<label for='".$rm['id']."' $co><input type=checkbox name='pages[]' id='".$rm['id']."' value='".$rm['id']."' $sel>".$rm['name']."</label>$end\n"; 
      if(el_hasChild('0', $rm['id'])){
        el_allPages($rm['path'], $current, $permanent, ++$count, ++$level);
        $level=0;
      }
    }while($rm=mysql_fetch_assoc($m));
  }
}


function el_listParent($lang='rus'){
  global $path;
  ($lang=='eng')?$parId=149:$parId=0;
  $p=el_dbselect("SELECT id, name, path FROM cat WHERE parent='$parId' AND menu='Y' ORDER BY sort ASC, id ASC", 0, $p);
  $rm=mysql_fetch_assoc($p);
  echo "<ul>\n";
  $c=0;
  do{
    ($c==mysql_num_rows($p)-1)?$end=' id="end"':$end='';
    if($rm['path']!=''){
      (substr_count($path, $rm['path'])>0)?$cl=' class="active"':$cl=''; 
    }else{
      ($rm['path']==$path)?$cl=' class="active"':$cl='';
    }
    ($rm['path']=='' || $rm['path']=='/')?$rm['path']='http://'.$_SERVER['SERVER_NAME']:$rm['path']=$rm['path'];
    echo "<li $cl $end><a href='".$rm['path']."/'>".$rm['name']."</a></li>\n";
    $c++;
  }while($rm=mysql_fetch_assoc($p));
  echo "</ul>\n";
}

function el_getContentFromTable($id, $cat){
	$exist=el_dbselect("SELECT * FROM forms WHERE cat=$cat", 0, $formsParam);
	$rexist=mysql_fetch_assoc($exist);
	$exist_form=mysql_num_rows($exist);
	if(strlen($rexist['sorce_table'])>0){
		$src_table=$rexist['sorce_table'];
		$prop_table='form_prop';
	}else{
		$src_table='catalog_'.$rexist['catalog_id'].'_data';
		$prop_table='catalog_prop';
	}
	$prop=el_dbselect("SELECT name, field FROM `$prop_table` WHERE catalog_id='".$rexist['catalog_id']."' ORDER BY sort", 0, $prop);
	$rprop=mysql_fetch_assoc($prop);
	$data=el_dbselect("SELECT * FROM `$src_table` WHERE id='".$id."'", 0, $data);
	$rdata=mysql_fetch_assoc($data);
	
	$dataArray=array();
	do{
		$dataArray['field'.$rprop['field']]['name']=$rprop['name'];
		$dataArray['field'.$rprop['field']]['data']=$rdata['field'.$rprop['field']];
	}while($rprop=mysql_fetch_assoc($prop));
	return $dataArray;
}

//postfix russian words
function el_postfix($number, $one, $two, $five){
	$number=intval($number);
	if($number>20){
		$numArr=str_split($number);
		$number=$numArr[count($numArr)-1];
		$out=el_postfix($number, $one, $two, $five);
	}elseif($number==1){
		$out=$one;
	}elseif($number>1 && $number<5){
		$out=$two;
	}elseif($number>=5 || $number==0){
		$out=$five;
	}
	return $out;
}

function el_viewField($type, $params, $fieldName='', $defVal='', $required='', $mode='admin'){
	$script=$list_field='';
	$fieldName=($fieldName=='')?'field'.$params['field']:$fieldName;
	$req=($required=='1')?" onblur=\"checkField(this, '$type', ".$params['field'].")\"":'';
	$correct=($mode=='site' && ($type=='float' || $type=='integer' || $type=='phone'))?' onkeyup="checkNumber(this)"':'';
	$item='';
	switch ($type){
		case "textarea": $input="textarea"; 
		$prop="cols=".$params['cols']." rows=".$params['rows'];
		$output="</textarea>"; 
		break;
		case "select":
		case "option":
		$itemArr=explode(";",$params['options']);
		for($i=0; $i<count($itemArr); $i++){
			$item.='<option value="'.$itemArr[$i].'">'.$itemArr[$i].'</option>'."\n";
		}
		$output="<select name='$fieldName' id='$fieldName' size=".$params['size']."$req>\n<option></option>\n $item</select>"; 
		break;
		case "optionlist":
		$itemArr=explode(";",$params['options']);
		for($i=0; $i<count($itemArr); $i++){
			$item.='<option value="'.$itemArr[$i].'">'.$itemArr[$i].'</option>'."\n";
		}
		$output="<select name='".$fieldName."[]' id='$fieldName' size=".$params['size']." multiple$req>\n<option></option>\n $item</select>"; 
		break;
		case "list_fromdb":
		$list_field=el_dbselect("select field".$params['from_field']." from catalog_".$params['listdb']."_data ORDER BY sort ASC", 0, $list_field);
		$row_list_field=mysql_fetch_assoc($list_field);
		$itemlist='';
		do{
			$itemlist.="<option value='".$row_list_field["field".$params['from_field']]."'>".$row_list_field["field".$params['from_field']]."</option>\n";
		}while($row_list_field=mysql_fetch_assoc($list_field));
		$output="<select name='".$fieldName."[]' id='$fieldName' size=".$params['size']." multiple$req>\n<option></option>\n".$itemlist."</select>"; 
		break;
		case "comments": $input="input"; $prop=" value='1' title='Включить комментирование'";
		$output=""; $params['type']="checkbox";
		break;
		case "checkbox": $input="input"; $prop=" value='".$params['name']."'";
		$output=""; $params['type']="checkbox";
		break;
		case "radio": $input="input";$prop="";
		$output=""; 
		break;
		case "small_image": $input="input"; $prop="";
		$output="<br>Укажите местонахождение картинки для предпросмотра на Вашем компьютере для закачки на сервер"; $params['type']="file";
		break;
		case "big_image": $input="input"; $prop="";
		$output="<br>Укажите местонахождение картинки на Вашем компьютере для закачки на сервер"; $params['type']="file";
		break;
		case "file": $input="input"; $prop="";
		$output="<br>Здесь указывается местонахождение файла на Вашем компьютере для закачки на сервер";
		break;
		case "secure_file": $input="input"; $prop="";
		$output="<br>Здесь указывается местонахождение файла на Вашем компьютере для закачки на сервер.<br>Система даст новое нечитаемое название файлу и поместит в недоступное для посетителей сайта место.";
		$params['type']="file";
		break;
		case "hidden_file": $input="input"; $prop="";
		$output="<br>Здесь указывается местонахождение файла на Вашем компьютере для закачки на сервер.<br>Система поместит файл в недоступное для посетителей сайта место.";
		$params['type']="file";
		break;
		case "price": $input="input"; $prop="";
		$output=" ".$params1['currency']; 
		$params['type']='text';
		break;
		case "calendar": $input="input"; $prop=""; $params['type']="text";
		$output=" <script type=\"text/javascript\">$(function() {
		$.datepicker.setDefaults($.extend({showMonthAfterYear: false}, $.datepicker.regional['ru']));
		$(\"#sfield".$row_cat_form['field']."\").datepicker({showOn: 'button', buttonImage: '/editor/img/b_calendar.gif', buttonImageOnly: true, dateFormat: 'yy-mm-dd',	firstDay: 1, buttonText: 'Выберите дату'});
		});
		</script>";
		break;
		case "calendarext":$output='<iframe src="/editor/modules/catalog/calendar.php?field=field'.$params['field'].'Add&frame=ext_calendar" frameborder="0" style="visibility:hidden" width="285" height="210" id="ext_calendar"></iframe>';
		$input="input"; $prop="id='field".$params['field']."Add' value=''"; $params['type']="hidden";
		break;
		case "text": 
		case "float":
		case "integer":
		case "phone":
		default:$input="input"; $prop="";
		$output=""; $params['type']='text';
		break;
	}

	if($type=='option' || $type=='optionlist' || $type=='list_fromdb'){
		return $output; 
	}else{
		return $script."<$input type='".$type."' name='$fieldName' id='$fieldName' size='".$params['size']."' $prop $req $correct>".$output; 
	}
}

function el_counter(){
echo '
<script language="JavaScript" type="text/javascript"><!--
d=document;
s=screen;
l=location;
d.cookie="b=b";
c="N";
if (d.cookie) c="Y";
j = (navigator.javaEnabled()?"Y":"N");
js="Y";
n=(navigator.appName.substring(0,2)=="Mi")?0:1;
px=(n==0)?s.colorDepth:s.pixelDepth; 
wh=s.width+\'x\'+s.height+\'x\'+px;
p="";
p+="<a href=\'http://elman.ru\' target=_blank><img src=\'http://'.$_SERVER['SERVER_NAME'].'/ban/ban.php?c="+c+"&j="+j+"&wh="+wh+"&js="+js+"&r="+
escape(d.referrer)+"&pg="+escape(window.location.href)+"\' width=1 height=1 alt=\'Счетчик\' border=0 style=\'visibility:hidden\'></a>";
d.write(p);if(n==0) {d.write("<");d.write("!--"); }
//-->
</script>
<noscript>
<a href=\'http://elman.ru\' target=\'_blank\'><img src="http://'.$_SERVER['SERVER_NAME'].'/ban/ban.php" width="1" height="1" alt="Счетчик" border="0" style="visibility:hidden" /></a>
</noscript>
<script language="JavaScript1.2" type="text/javascript"><!-- 
if (n == 0) { d.write("--");d.write(">"); } 
//--></script>';
}

function el_pageput($filename){
  $f=$_SERVER['DOCUMENT_ROOT'].'/modules/'.$filename.'.php';
  if(file_exists($f)){
    include $f;
  }else{
    echo '<font color=red>Модуль не установлен.</font>';
  }
}

function el_poll($id){
  include $_SERVER['DOCUMENT_ROOT'].'/modules/poll.php';
}

//Функция для вывода баннеров на площадке $id
function el_advPlace($id){
	global $path;
	$randadv=array();
	$pl=el_dbselect("SELECT * FROM ad_places WHERE  id=".$id." AND active=1", 0, $pl);
	if(mysql_num_rows($pl)>0){
		$i=0;
		$ad=el_dbselect("SELECT * FROM ad_banners WHERE  FIND_IN_SET(".$id.", `places`)>0 AND active=1 ORDER BY sort ASC", 0, $ad);
		$adv=mysql_fetch_assoc($ad);
		if(mysql_num_rows($ad)>0){
			if(mysql_num_rows($ad)>1){
				do{
					$i++;
					$randadv[$i]['id']=$adv['id'];
					$randadv[$i]['text']=$adv['text'];
					$randadv[$i]['url']=$adv['url'];
					$randadv[$i]['file']=$adv['file'];
					$randadv[$i]['sizew']=$adv['sizew'];
					$randadv[$i]['sizeh']=$adv['sizeh'];
					$randadv[$i]['alt']=$adv['alt'];
					$randadv[$i]['count']=$adv['count'];
					$randadv[$i]['view']=$adv['view'];
					$randadv[$i]['type']=$adv['type'];
					$randadv[$i]['target']=$adv['target'];
				}while($adv=mysql_fetch_assoc($ad));
				$c=rand(1, $i);
				$adv=$randadv[$c];

			} 
			if($adv['type']=='flash'){
				echo '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" 
				codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0" 
				width="'.$adv['sizew'].'" height="'.$adv['sizeh'].'" title="'.$adv['alt'].'">
			  <param name="movie" value="/images/pictures/'.$adv['file'].'" />
			  <param name="quality" value="high" />
			  <embed src="/images/pictures/'.$adv['file'].'" quality="high"
			   pluginspage="http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash" 
			   type="application/x-shockwave-flash" width="'.$adv['sizew'].'" height="'.$adv['sizeh'].'"></embed>
				</object>';
			}elseif($adv['type']=='image'){
				echo '<a href="'.$path.'/?adredirect='.$adv['id'].'" target="'.((strlen($adv['target'])>0)?$adv['target']:'_blank').'">
				<img src="/images/pictures/'.$adv['file'].'" width="'.$adv['sizew'].'" height="'.$adv['sizeh'].'" 
				alt="'.htmlspecialchars(stripslashes($adv['alt'])).'" title="'.htmlspecialchars(stripslashes($adv['alt'])).'" border="0" align="absmiddle">'.$adv['text'].'</a>';
			}else{
				echo $adv['html'];
			}
			el_dbselect("UPDATE ad_banners SET view='".($adv['view']+1)."' WHERE id='".$adv['id']."'", 0, $res);
		}
	}
}

function el_updateDBanner(){
	global $path;
	$pl=el_dbselect("SELECT id, `interval` FROM ad_places WHERE type='dyn' AND active=1", 0, $pl);
	if(mysql_num_rows($pl)>0){
		$rpl=mysql_fetch_assoc($pl);
		$ad=el_dbselect("SELECT * FROM ad_banners WHERE FIND_IN_SET(".$rpl['id'].", `places`)>0 AND active=1", 0, $ad);
		$adv=mysql_fetch_assoc($ad);
		if(mysql_num_rows($ad)>0){
			$str='var pictures=new Array();'."\n".'var links=new Array();'."\n".'var alts=new Array();'."\n".'var interTime='.$rpl['interval'].';'."\n\n";
			$i=0;
			do{
				$str.='pictures['.$i.']="'.$adv['id'].'";'."\n";
				$str.='links['.$i.']="'.$path.'/?adredirect='.$adv['id'].'";'."\n";
				$str.='alts['.$i.']="'.$adv['alt'].'";'."\n";
				$i++;
			}while($adv=mysql_fetch_assoc($ad));
			$f=fopen($_SERVER['DOCUMENT_ROOT'].'/picture_array.js', 'w');
			fwrite($f, $str);
			fclose($f);
		}
	}
}

function el_getTags($field, $url='/alltags/'){
	$tags=$tag=array();
	if(strlen($field)>0){
		$tags=explode(',', trim($field));
		if(count($tags)>0){
			for($i=0; $i<count($tags); $i++){
					$tag[]=str_replace($tags[$i], 
											 "<a href=\"$url?tag=".urlencode(trim(strtolower($tags[$i])))."\">$tags[$i]</a>", 
											 $tags[$i]);
			}
			return $tagss=implode(', ', $tag);
		}else{
			return $tag='Тэги не заданы.';
		}
	}else{
		return $tag='Тэги не заданы.';
	}
}

function el_getAllTags($print=true, $limit=0.3, $cacheFile='tags', $url='/alltags/'){
	$cachePath=$_SERVER['DOCUMENT_ROOT'].'/editor/cache/tags/'.$cacheFile.'.htm';
	$at=array();
	$xml_file=$_SERVER['DOCUMENT_ROOT'].'/js/tagcloud.xml';
	$xml='';//"<tags>";
	if(file_exists($cachePath) && $print==true){
		include $cachePath;
	}else{
		$t=el_dbselect("SELECT field10 FROM catalog_pub_data WHERE active=1 ORDER BY field10 ASC", 0, $t);
		$rt=mysql_fetch_assoc($t);
		$words=$at=array();
		do{
			$tag=array();
			$tags=trim($rt['field10']);
			$tag=explode(',', $tags);
			ksort($tag, SORT_STRING);
			for($i=0; $i<count($tag); $i++){
				$tag[$i]=trim(cyr_strtolower($tag[$i], 'all'));
				if(!array_key_exists($tag[$i], $words)){
					$words[$tag[$i]]=1;
				}else{
					$words[$tag[$i]]++;
				}
			}
		}while($rt=mysql_fetch_assoc($t)); //print_r($words);
		$max=max($words);
		ksort($words, SORT_STRING);
		//reset($words);
		//print_r($words);
		while(list($key, $val)= each($words)){
			if(strlen($key)>1){
				if($val/$max>=1){
					$at[]='<li style=\'font-size:26px;\'><a href=\''.$url.'?tag='.trim(addslashes(urlencode(cyr_strtolower($key)))).'\'>'.ucfirst($key).'</a></li>'."\n";
				}elseif($val/$max>=0.6){
					$at[]='<li style=\'font-size:14px;\'><a href=\''.$url.'?tag='.trim(addslashes(urlencode(cyr_strtolower($key)))).'\'>'.ucfirst($key).'</a></li>'."\n";
				}elseif($val/$max>=$limit){
					$at[]='<li style=\'font-size:11px;\'><a href=\''.$url.'?tag='.trim(addslashes(urlencode(cyr_strtolower($key)))).'\'>'.ucfirst($key).'</a></li>'."\n";
				}
			}		
		}
		
		$xml.=implode('', $at);//."</tags>";
		unlink($xml_file);
		file_put_contents($xml_file, $xml);
		$out=implode(', ', $at);
		file_put_contents($cachePath, $xml);
		//el_writecache('tags/'.$cacheFile, $out);
		if($print==true){
		  include $cachePath;
		}
	}
}

function el_getComments($pagepath, $mode='none'){
	$c=el_dbselect("SELECT COUNT(id) as c FROM comments WHERE pagepath='$pagepath'", 0, $c, 'row');
	if(intval($c['c'])>0){
		switch($mode){
			case 'href': $out='<a href="'.$pagepath.'" title="">'.$c['c'].' комментар'.el_postfix(intval($c['c']), 'ий', 'ия', 'иев').'</a>'; break;
			case 'title': $out=$c['c'].' комментар'.el_postfix(intval($c['c']), 'ий', 'ия', 'иев'); break;
			case 'none':
			default: $out=$c['c']; break;
		}
		$idArr=explode('/?id=', $pagepath);
		$id=$idArr[count($idArr)-1];
		el_dbselect("UPDATE catalog_pub_data SET field16='".intval($c['c'])."' WHERE id='".intval($id)."'", 0, $res);
		return $out;
	}
}

function el_getViews($id, $mode='none'){
	$c=el_dbselect("SELECT field20 as c FROM catalog_pub_data WHERE id='$id'", 0, $c, 'row');
	switch($mode){
		case 'title': $out=$c['c'].' просмотр'.el_postfix(intval($c['c']), '', 'а', 'ов'); break;
		case 'none':
		default: $out=$c['c']; break;
	}
	return $out;
}


//Функция для создания постраничной навигации
function el_paging($pn, $currentPage, $queryString_catalog, $totalPages_catalog, $maxRows_catalog, $tr, $pnName='pn', $trName='tr'){
global $_GET; 
echo '<div class="pagenavi">';
/*if ($pn > 0) { ?>
          <a href="<?php printf("%s?pn=%d%s", $currentPage, 0, $queryString_catalog); ?>" class="prev">&lt;&lt; Начало</a>
          <?php }*/ ?>  <?php if ($pn > 0) { ?>
          <a href="<?php printf("%s?".$pnName."=%d%s", $currentPage, max(0, $pn - 1), $queryString_catalog); ?>" class="next">Предыдущая</a>
          <?php } ?>
      
      
	      <?php /* if ($pn < $totalPages_catalog) { ?>
          <a href="<?php printf("%s?pn=%d%s", $currentPage, $totalPages_catalog, $queryString_catalog); ?>" class="next">Конец &gt;&gt; </a>
          <?php } */?>  
<?
    if (($pn < $totalPages_catalog)||($pn > 0)) {
      
      $startcount=$pn-7;
      ($startcount<0)?$startcount=0:$startcount=$startcount;
      $maxcount=$pn+7;
      ($maxcount>$totalPages_catalog)?$maxcount=$totalPages_catalog:$maxcount=$maxcount; 
      ($startcount==0 && $totalPages_catalog>=15)?$maxcount=14:$maxcount=$maxcount; 
      $page=$startcount+1;

      $countpage=ceil($tr/$maxRows_catalog)-1;
       if($startcount>0){
        echo ' <a href=?'.$pnName.'='.($startcount-1).$queryString_catalog.' class="nom">....</a> ';
      }
        for($pagen=$startcount; $pagen<=$maxcount; $pagen++){
          if($countpage>=0){
            if($pn!=$pagen) {
              echo ' <a href=?'.$pnName.'='.$pagen.$queryString_catalog.' class="nom">'.$page.'</a> '; 
            }else{
              echo ' <span>'.$page.'</span> ';
            }
            $page++;
            $countpage--;
          }
        }
      if($countpage>=0 && $maxcount<$totalPages_catalog){
        echo ' <a href=?'.$pnName.'='.($maxcount+1).$queryString_catalog.' class="nom">....</a> ';
      } 
    } 
	if ($pn < $totalPages_catalog) { ?>
          <a href="<?php printf("%s?".$pnName."=%d%s", $currentPage, min($totalPages_catalog, $pn + 1), $queryString_catalog); ?>" class="prev">Следующая</a>
          <?php } ?> 
    </div>
<?
}

function el_gettitleCaption($mode){
	$str=$_SERVER['QUERY_STRING'];
	switch($str){
		case 'pupils': $title='Ученики'; break;
		case 'parents': $title='Родители'; break;
		case 'subjects': $title='Предметы'; break;
		case 'classes': $title='Классы'; break;
		case 'marks': $title='Оценки'; break;
	}
	return (strlen($title)>0)?' &rarr; '.$title:'';
}

function buildCatMenu($fields='7', $parentField='', $parentFieldValue, $count=0){ 
	$c=el_dbselect("SELECT id, field$fields FROM catalog_pub_data WHERE active=1".((intval($parentField)>0)?" AND 
					field$parentField='".$parentFieldValue."'":"")." GROUP BY field$fields", 0, $c, 'result', true/*, true*/);
	$rc=mysql_fetch_assoc($c);
	$p=mysql_num_rows($c);
		echo '
		<ul class="part_menu'.$fields.'" id="part_menu'.$count.'"'.(($_COOKIE['open'.$count]=='1')?' style="display:block"':'').'>';
		do{
			if(strlen(trim($rc['field'.$fields]))>0){
				$count++;
				$parentFieldValue=addslashes($rc['field'.$fields]);
				if($fields==7){
					$f=8; $pf=7;
				}
				if($fields==8){ 
					$f=9; $pf=8;
				}
				/*if($fields==9){
					$f=4; $pf=9; 
				}*/
				if(intval($f)>0){
					$h=el_dbselect("SELECT field$f FROM catalog_pub_data WHERE active=1".((intval($parentField)>0)?" AND 
					field$pf='".$parentFieldValue."'":"")." GROUP BY field$f", 0, $h, 'result', true/*, true*/);
					$ph=mysql_num_rows($h);
				}
					echo '<li'.(($ph>0 && intval($f)>0)?' class="haschild'.$fields.'"><img src="/images/'.(($_COOKIE['open'.($count+100)]=='1')?'minus':'plus').'.gif" class="part_icon" id="ic'.($count+100).'">':'>').'&nbsp;';
					echo '&nbsp;<a href="/parts/?sf'.$fields.'='.$rc['field'.$fields].'"'.((str_replace("'", '', $rc['field'.$fields])==$_GET['sf'.$fields])?' class="current"':'').'>'.$rc['field'.$fields].'</a>';
					
					if($ph>0)buildCatMenu($f, $pf, $parentFieldValue, $count+100);
					
					echo '</li>'."\n";
				//}
			}
		}while($rc=mysql_fetch_assoc($c));
		echo '</ul>';
	//}
}
//SELECT field8 FROM catalog_pub_data WHERE active=1 AND field7='Accent' GROUP BY field8

//Функции для отображения модулей в конструкторе дизайна
function el_menu($funcName, $funcParam){
  if(is_array($funcParam)){
    call_user_func_array($funcName, $funcParam);
  }else{
    call_user_func($funcName, $funcParam);
  }
}
function el_text($funcName, $funcParam){
  if(is_array($funcParam)){
    call_user_func_array($funcName, $funcParam);
  }else{
    call_user_func($funcName, $funcParam);
  }
}
function el_module($funcName, $funcParam){
  if(is_array($funcParam)){
    call_user_func_array($funcName, $funcParam);
  }else{
    call_user_func($funcName, $funcParam);
  }
}
function el_anons($funcName, $funcParam){
  call_user_func_array($funcName, $funcParam);
}

function el_calend($funcName, $funcParam){
  call_user_func_array($funcName, $funcParam);
}

function el_polls($funcName, $funcParam){
  call_user_func_array($funcName, $funcParam);
}
?>