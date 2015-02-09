<?php


require_once('config.php');
require_once('lang/'.$lang.'.php');
global $sqldberror;


class auth {
  
//remove special chars -> SQL Injection
public static function clean($z){
    $z = strtolower($z);
    $z = preg_replace('/[^a-zA-Z0-9]+/', '', $z);
    $z = str_replace(' ', '', $z);
}


  public static function verify() {
    // open ckey database
    global $sqldberror;
    mysql_connect('localhost',"root") or die ('<div class="alert alert-danger" role="alert">cant connect to SQL (verify)</div>');

    mysql_query('use schuletest') or die ('<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.'(verify)</div></div>');


    //delete old cIDs
    $del=mysql_query('delete from session where void<'.time()) or die ("can delete old cIDs (verify)");
    //clean key
    $key=clean($_COOKIE["key"]);
    //check key
    if(strlen($key)>=256){
      $check=mysql_query('select count(cID) from session where cid like "'.$key.'"');
      $num = mysql_result ($check,0);
      if ($num>0){
        $login=true;
        $extend=time()+600;
        $new=mysql_query('update session set void='.$extend.' where cid like "'.$key.'"') or die ("cant update session");
        setcookie('key',$key,$extend);
      }
      else
        $login=false;
    }
    else{
      $login=false;
      '<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;Sitzungsschlüssel defekt.<br />Bitte neu einloggen</div></div>';
    }
    mysql_close();
    return $login;
  }

  public static function createRandomKey(){
    $keyset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $randkey = "";
    for ($i=0; $i<256; $i++)
    $randkey .= substr($keyset, rand(0, strlen($keyset)-1), 1);
    return $randkey;
  }

  public static function logon($user, $pw) {
    global $sqldberror;
    // open ckey database
    $connect=mysql_connect('localhost',"root") or die ("cant connect to SQL");

    $setdb=mysql_query('use schuletest');
    if (!$setdb) {
      die ('<br><div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.'(logon)</div></div>');
    }
    $user=clean($user);
    $loginfo=mysql_query('select password from users where uname like "'.$user.'"') or die ("Datenbankproblem oder user existiert nicht, bitte zurück (logon)");
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
