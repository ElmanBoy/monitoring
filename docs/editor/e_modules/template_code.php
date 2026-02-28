<?php require_once($_SERVER['DOCUMENT_ROOT'].'/Connections/dbconn.php');
$requiredUserLevel = array(1); 
include($_SERVER['DOCUMENT_ROOT']."/editor/secure/secure.php"); 
//Ќаходим раздел где примен€етс€ такой шаблон
$t=el_dbselect("SELECT path FROM template WHERE id='".$_GET['id']."'",0,$t,'row');
$r=el_dbselect("SELECT path FROM content WHERE template='".$t['path']."'",0,$r,'row');

$fd=fopen($_SERVER['DOCUMENT_ROOT']."/tmpl/temp/".$_GET['file'], 'r');
while(!feof($fd)){
	$line.=fgets($fd,4096);
}

preg_match('/<html>(.*)<body>/ims', $line, $head);
$hf=fopen($_SERVER['DOCUMENT_ROOT']."/tmpl/temp/head_".$_GET['file'], 'w');
preg_match('/<\?\s*el_meta\(\)\s*\?>/ims', $head[0], $meta);
if(strlen($meta[0])==0){
	$head[0]=preg_replace('/<\s*head\s*>/ims', "<head>\n<? el_meta() ?>\n", $head[0]);
}
fputs($hf, $head[0]);
fclose($hf);

preg_match('/<\/body>(.*)<\/html>/ims', $line, $bottom);
$bf=fopen($_SERVER['DOCUMENT_ROOT']."/tmpl/temp/bottom_".$_GET['file'], 'w');
fputs($bf, $bottom[0]);
fclose($bf);
?>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<meta http-equiv="cache-control" content="no-cache">
<?
echo $line; 
?>