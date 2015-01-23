<?php


require_once('config.php');
require_once('lang/'.$lang.'.php');
global $sqldberror;


class auth {
public static function verify() {
// open ckey database
global $sqldberror;
mysql_connect('localhost',"root") or die ('<div class="alert alert-danger" role="alert">cant connect to SQL</div>');

mysql_query('use schuletest') or die ('<div class="alert alert-danger" role="alert">'.$sqldberror.'</div>');


//delete old cIDs
$del=mysql_query('delete from session where void<'.time()) or die ("can delete old cIDs");
//check key
$check=mysql_query('select count(cID) from session where cid like "'.$_COOKIE["key"].'"');
$num = mysql_result ($check,0);



if ($num>0){
$login=true;
$extend=time()+600;
$new=mysql_query('update session set void='.$extend.' where cid like "'.$_COOKIE["key"].'"') or die ("cant update session");
setcookie('key',$_COOKIE["key"],$extend);
}
else
$login=false;
mysql_close();
return $login;
}

public static function createRandomKey(){
  global $sqldberror;
$keyset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
$randkey = "";
for ($i=0; $i<256; $i++)
$randkey .= substr($keyset, rand(0, strlen($keyset)-1), 1);
return $randkey;
}

public static function logon($user, $pw) {
// open ckey database
$connect=mysql_connect('localhost',"root") or die ("cant connect to SQL");

$setdb=mysql_query('use schuletest');
if (!$setdb) {
die ($sqldberror);
}
$loginfo=mysql_query('select password from users where uname like "'.$user.'"') or die ("Datenbankproblem oder user existiert nicht, bitte zurueck");
$loginfo=mysql_fetch_row($loginfo)[0];
mysql_close();
if($pw==$loginfo)
{
return true;
}
else
return false;
}
}
?>
