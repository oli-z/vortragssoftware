<?php


require_once('config.php');
require_once('lang/'.$lang.'.php');
global $sqldberror;


class auth {
//AES descrypt
function aescrypt($encrypt, $mc_key) {
    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB),MCRYPT_RAND);
    $passcrypt = trim(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $mc_key,trim($encrypt),MCRYPT_MODE_ECB, $iv));
    $encode = base64_encode($passcrypt);
    return $encode;
}

	
function aesdecrypt($decrypt, $mc_key) {   
    $decoded = base64_decode($decrypt);
    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB),MCRYPT_RAND);
    $decrypted = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $mc_key, trim($decoded),MCRYPT_MODE_ECB, $iv));
    return $decrypted;
}
  
//remove special chars -> SQL Injection
public static function clean($z){
    $z = strtolower($z);
    $z = preg_replace('/[^a-zA-Z0-9]+/', '', $z);
    $z = str_replace(' ', '', $z);
}

  public static function ip(){
    if (! isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    else {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $ip;
  }
  
  public static function verify() {
    // open ckey database
    global $sqldberror;
    mysql_connect('localhost',"root") or die ('<div class="alert alert-danger" role="alert">cant connect to SQL (verify)</div>');

    mysql_query('use schuletest') or die ('<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.'(verify)</div></div>');

    //get IP
    $ip=ip();
    //delete old cIDs
    $del=mysql_query('delete from session where void<'.time()) or die ("can delete old cIDs (verify)");
    //clean key
    $key=clean($_COOKIE["key"]);
    $key=hash("sha512",$_COOKIE["key"].hash("sha512",$ip);
    //check key
    if(strlen($key)=256){
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

  public static funtion logout() {
    mysql_connect("localhost","root") or die ("cant connect to SQL");
    mysql_query('use schuletest') or die ('<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.' (logout)</div></div>');
    mysql_query('delete from session where cid like "'.hash("sha512",$_COOKIE["key"].hash("sha512",$ip).'"') or die ('cant delete');
    mysql_close();
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
    if(hash("sha512",hash("sha512",$pw))==$loginfo)
    {
      $chash=auth::createRandomKey();
      $duration=time()+600;
      mysql_connect("localhost","root") or die ('<div class="alert alert-danger" role="alert">cant connect to SQL</div>');
      mysql_query('use schuletest') or die ('<div class="container theme-showcase" role="main"><div class="alert alert-danger" role="alert"><span class="glyphicon glyphicon-exclamation-sign"></span>&emsp;'.$sqldberror.'</div></div>');
      $insert=mysql_query('insert into session (cid,void) values ("'.hash("sha512",$chash.hash("sha512",$ip).'",'.$duration.')') or die ('<div class="alert alert-danger" role="alert">cant insert</div>');
      mysql_close();
      setcookie('key',$chash,$duration);
      return true;
    }
    else{
      setcookie('key','lol',1);
      return false;
    }
  }
}
?>
