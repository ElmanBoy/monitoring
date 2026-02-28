<html>
<head>
<title>Информация по IP</title>
<link href="/editor/style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
if ($_GET['ip']!="")
{
        $sock=fsockopen ("whois.ripe.net",43,$errno,$errstr);
        if (!$sock)
        {
                echo ($errstr($errno)."<br>");
        }
        else
        {
                fputs ($sock,$_GET['ip']."\r\n");
                while (!feof($sock))
                {
                        echo (str_replace(":",":&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",fgets ($sock,128))."<br>");
                }
        }
        fclose ($sock);
}
?>
</body>
</html>

