<?php
$strbody = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Documento senza titolo</title>
<style type="text/css">
body{
	margin: 0px;
	padding: 0px;
	font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif;
	font-size: 12px;
	height: 100%;
	color: #000000;
	text-align: left;
}
html{height: 100%;}
table{
	margin: 0px;
	padding: 0px;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	color: #003C79;
	text-align: left;
}

a {text-decoration: none;color: #003C79;}
a:hover{text-decoration: none;color: #6699FF;}

</style>
</head>

<body>
<div style="width: 100%; overflow: hidden;">
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 96%; margin: 0 auto;">
  <tr>
    <td align="center" valign="top"><table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
      <tr>
        <td>&nbsp;</td>
        </tr>
      <tr>
        <td height="15">&nbsp;</td>
      </tr>
      <tr>
        <td><table width="100%" border="0" cellspacing="0" cellpadding="4" style="border: 1px solid #0099FF;">

          <tr>
            <td height="25" colspan="2" bgcolor="#0099FF" class="testo14_bianco"><strong>&nbsp;:: Messaggio di errore {{site_name}} </strong> </td>
            </tr>
          <tr>
            <td width="20%">&nbsp;</td>
            <td width="80%">&nbsp;</td>
            </tr>
          <tr>
            <td valign="top"><strong>MESSAGGIO PRINCIPALE </strong></td>
            <td><pre>{{msg}}</pre></td>
          </tr>
          <tr>
            <td>Data ora </td>
            <td>'.date('d/m/Y H:i:s', mktime(date("H"),date("i"),date("s"), date("m"), date("d"), date("Y"))).'</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
          </tr>';

$vars = array("POST" => $_POST, "GET" => $_GET, "SESSION" => $_SESSION, "REQUEST" => $_REQUEST, "SERVER" => $_SERVER);
foreach($vars as $name => $ARRAY){
          
        $strbody .= '<tr>
            <td bgcolor="#F4F4F4"><strong>VARIABILI &quot;'.$name.'&quot; </strong></td>
            <td bgcolor="#F4F4F4">&nbsp;</td>
          </tr>';
        
        foreach($ARRAY as $key => $val){
                  $strbody .= '<tr>
                    <td><strong>'.$key.'</strong></td>
                    <td>'.(is_array($val) ? print_r($val, 1) : $val).'</td>
                    </tr>';
        }

}
          $strbody .= '<tr>
            <td colspan="2">&nbsp;</td>
            </tr>
        </table></td>
      </tr>

    </table></td>
  </tr>
</table>
</div>
</body>
</html>';

return $strbody;

